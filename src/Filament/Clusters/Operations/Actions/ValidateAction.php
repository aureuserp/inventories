<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Rule;
use Livewire\Component;

class ValidateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.validate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('inventories::filament/clusters/operations/actions/validate.label'))
            ->color('gray')
            ->requiresConfirmation(function (Operation $record) {
                return $record->operationType->create_backorder === Enums\CreateBackorder::ASK
                    && $this->canProcessBackOrder($record);
            })
            ->configureModal($this->getRecord())
            ->action(function (Operation $record, Component $livewire): void {
                $this->processBackOrder($record);

                $this->performValidation($record);

                $livewire->updateForm();
            })
            ->hidden(fn () => in_array($this->getRecord()->state, [
                Enums\OperationState::DONE,
                Enums\OperationState::CANCELED,
            ]));
    }

    protected function configureModal(Operation $record): self
    {
        if (
            $record->operationType->create_backorder === Enums\CreateBackorder::ASK 
            && $this->canProcessBackOrder($record)
        ) {
            $this->modalHeading(__('inventories::filament/clusters/operations/actions/validate.modal-heading'))
                ->modalDescription(__('inventories::filament/clusters/operations/actions/validate.modal-description'))
                ->extraModalFooterActions([
                    Action::make('no-backorder')
                        ->label(__('inventories::filament/clusters/operations/actions/validate.extra-modal-footer-actions.no-backorder.label'))
                        ->color('danger')
                        ->action(function (Operation $record, Component $livewire): void {
                            $this->handleNoBackorder($record);

                            $livewire->updateForm();
                        }),
                ]);
        }

        return $this;
    }

    protected function handleNoBackorder(Operation $record): void
    {
        $this->performValidation($record);
    }

    protected function handleValidationWithBackOrder(Operation $record): void
    {
        $this->processBackOrder($record);

        $this->performValidation($record);
    }

    /**
     * Perform the validation steps on the operation.
     */
    private function performValidation(Operation $record): void
    {
        // (Re)create move lines and update operation state before validation.
        foreach ($record->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($record);

        // Validate moves and notify on warnings.
        foreach ($record->moves as $move) {
            if (! $this->validateMoveLines($move)) {
                return;
            }
        }

        // Update each move and its lines, adjusting quantities.
        foreach ($record->moves as $move) {
            $this->finalizeMove($move);
        }

        OperationResource::updateOperationState($record);

        $this->applyPushRules($record);
    }

    /**
     * Validate a move's lines.
     *
     * @return bool Returns false if a validation warning is triggered.
     */
    private function validateMoveLines($move): bool
    {
        if ($move->lines->isEmpty()) {
            $this->sendNotification(
                'inventories::filament/clusters/operations/actions/validate.notification.warning.lines-missing.title',
                'inventories::filament/clusters/operations/actions/validate.notification.warning.lines-missing.body',
                'warning'
            );

            return false;
        }

        foreach ($move->lines as $line) {
            if ($line->package_id && $line->result_package_id && $line->package_id == $line->result_package_id) {
                $sourceQuantity = ProductQuantity::where('product_id', $line->product_id)
                    ->where('location_id', $line->source_location_id)
                    ->where('lot_id', $line->lot_id)
                    ->where('package_id', $line->package_id)
                    ->first();

                if ($sourceQuantity && $sourceQuantity->quantity != $line->qty) {
                    $this->sendNotification(
                        'inventories::filament/clusters/operations/actions/validate.notification.warning.partial-package.title',
                        'inventories::filament/clusters/operations/actions/validate.notification.warning.partial-package.body',
                        'warning'
                    );

                    return false;
                }
            }
        }

        $isLotTracking = $move->product->tracking == Enums\ProductTracking::LOT;

        if ($isLotTracking && $move->lines->contains(fn ($line) => ! $line->lot_id)) {
            $this->sendNotification(
                'inventories::filament/clusters/operations/actions/validate.notification.warning.lot-missing.title',
                'inventories::filament/clusters/operations/actions/validate.notification.warning.lot-missing.body',
                'warning'
            );

            return false;
        }

        return true;
    }

    /**
     * Send a notification with the given title, body and type.
     */
    private function sendNotification(string $titleKey, string $bodyKey, string $type = 'info'): void
    {
        Notification::make()
            ->title(__($titleKey))
            ->body(__($bodyKey))
            ->{$type}()
            ->send();
    }

    /**
     * Finalize a move by marking it and its lines as done and updating quantities.
     */
    private function finalizeMove($move): void
    {
        $move->update([
            'state'     => Enums\MoveState::DONE,
            'is_picked' => true,
        ]);

        foreach ($move->lines()->get() as $moveLine) {
            $this->finalizeMoveLine($moveLine);
        }
    }

    /**
     * Finalize a move line by updating its state and adjusting quantities.
     */
    private function finalizeMoveLine($moveLine): void
    {
        $moveLine->update(['state' => Enums\MoveState::DONE]);

        // Process source quantity
        $sourceQuantity = ProductQuantity::where('product_id', $moveLine->product_id)
            ->where('location_id', $moveLine->source_location_id)
            ->where('lot_id', $moveLine->lot_id)
            ->where('package_id', $moveLine->package_id)
            ->first();

        if ($sourceQuantity) {
            $remainingQty = $sourceQuantity->quantity - $moveLine->qty;

            if ($remainingQty == 0) {
                $sourceQuantity->delete();
            } else {
                $reservedQty = $this->calculateReservedQty($moveLine->sourceLocation, $moveLine->qty);
                $sourceQuantity->update([
                    'quantity'                => $remainingQty,
                    'reserved_quantity'       => $sourceQuantity->reserved_quantity - $reservedQty,
                    'inventory_diff_quantity' => $sourceQuantity->inventory_diff_quantity + $moveLine->qty,
                ]);
            }
        } else {
            ProductQuantity::create([
                'product_id'              => $moveLine->product_id,
                'location_id'             => $moveLine->source_location_id,
                'lot_id'                  => $moveLine->lot_id,
                'package_id'              => $moveLine->package_id,
                'quantity'                => -$moveLine->qty,
                'inventory_diff_quantity' => $moveLine->qty,
                'company_id'              => $moveLine->sourceLocation->company_id,
                'creator_id'              => Auth::id(),
                'incoming_at'             => now(),
            ]);
        }

        // Process destination quantity
        $destinationQuantity = ProductQuantity::where('product_id', $moveLine->product_id)
            ->where('location_id', $moveLine->destination_location_id)
            ->where('lot_id', $moveLine->lot_id)
            ->where('package_id', $moveLine->result_package_id)
            ->first();

        $reservedQty = $this->calculateReservedQty($moveLine->destinationLocation, $moveLine->qty);

        if ($destinationQuantity) {
            $destinationQuantity->update([
                'quantity'                => $destinationQuantity->quantity + $moveLine->qty,
                'reserved_quantity'       => $destinationQuantity->reserved_quantity + $reservedQty,
                'inventory_diff_quantity' => $destinationQuantity->inventory_diff_quantity - $moveLine->qty,
            ]);
        } else {
            ProductQuantity::create([
                'product_id'              => $moveLine->product_id,
                'location_id'             => $moveLine->destination_location_id,
                'package_id'              => $moveLine->result_package_id,
                'lot_id'                  => $moveLine->lot_id,
                'quantity'                => $moveLine->qty,
                'reserved_quantity'       => $reservedQty,
                'inventory_diff_quantity' => -$moveLine->qty,
                'incoming_at'             => now(),
                'creator_id'              => Auth::id(),
                'company_id'              => $moveLine->destinationLocation->company_id,
            ]);
        }

        // Update package and lot if applicable.
        if ($moveLine->result_package_id && $moveLine->resultPackage) {
            $moveLine->resultPackage->update([
                'location_id' => $moveLine->destination_location_id,
                'pack_date'   => now(),
            ]);
        }

        if ($moveLine->lot_id && $moveLine->lot) {
            $moveLine->lot->update([
                'location_id' => $moveLine->lot->total_quantity >= $moveLine->qty
                    ? $moveLine->destination_location_id
                    : null,
            ]);
        }
    }

    /**
     * Calculate reserved quantity for a location.
     */
    private function calculateReservedQty($location, $qty): int
    {
        if ($location->type === Enums\LocationType::INTERNAL && ! $location->is_stock_location) {
            return $qty;
        }

        return 0;
    }

    /**
     * Process back order for the operation.
     */
    public function processBackOrder(Operation $record): void
    {
        if (! $this->canProcessBackOrder($record)) {
            return;
        }

        $newOperation = $record->replicate()->fill([
            'state'      => Enums\OperationState::DRAFT,
            'origin'     => $record->name,
            'user_id'    => Auth::id(),
            'creator_id' => Auth::id(),
        ]);

        $newOperation->save();

        foreach ($record->moves as $move) {
            if ($move->requested_qty <= $move->received_qty) {
                continue;
            }

            $newMove = $move->replicate()->fill([
                'operation_id'      => $newOperation->id,
                'reference'         => $newOperation->name,
                'state'             => Enums\MoveState::DRAFT,
                'requested_qty'     => $move->requested_qty - $move->received_qty,
                'requested_uom_qty' => $move->requested_qty - $move->received_qty,
            ]);

            $newMove->save();
        }

        $newOperation->refresh();

        foreach ($newOperation->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($newOperation);

        $record->update(['back_order_id' => $newOperation->id]);
    }

    /**
     * Check if a back order can be processed.
     */
    public function canProcessBackOrder(Operation $record): bool
    {
        if ($record->operationType->create_backorder === Enums\CreateBackorder::NEVER) {
            return false;
        }

        return $record->moves->sum('requested_qty') > $record->moves->sum('received_qty');
    }

    /**
     * Apply push rules for the operation.
     */
    public function applyPushRules(Operation $record): void
    {
        $rules = [];

        foreach ($record->moves as $move) {
            if ($move->origin_returned_move_id) {
                continue;
            }
            
            $rule = $this->getPushRule($move);
            
            if (! $rule) {
                continue;
            }

            $ruleId = $rule->id;

            $pushedMove = $this->runPushRule($rule, $move);

            if (! isset($rules[$ruleId])) {
                $rules[$ruleId] = [
                    'rule'  => $rule,
                    'moves' => [$pushedMove],
                ];
            } else {
                $rules[$ruleId]['moves'][] = $pushedMove;
            }
        }

        foreach ($rules as $ruleData) {
            $this->createPushOperation($record, $ruleData['rule'], $ruleData['moves']);
        }
    }

    /**
     * Create a new operation based on a push rule and assign moves to it.
     */
    private function createPushOperation(Operation $record, Rule $rule, array $moves): void
    {
        $newOperation = Operation::create([
            'state'                   => Enums\OperationState::DRAFT,
            'origin'                  => $record->name,
            'operation_type_id'       => $rule->operation_type_id,
            'source_location_id'      => $rule->source_location_id,
            'destination_location_id' => $rule->destination_location_id,
            'scheduled_at'            => now()->addDays($rule->delay),
            'company_id'              => $rule->company_id,
            'user_id'                 => Auth::id(),
            'creator_id'              => Auth::id(),
        ]);

        foreach ($moves as $move) {
            $move->update([
                'operation_id' => $newOperation->id,
                'reference'    => $newOperation->name,
            ]);
        }

        $newOperation->refresh();

        foreach ($newOperation->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($newOperation);
    }

    /**
     * Traverse up the location tree to find a matching push rule.
     */
    public function getPushRule(Move $move, array $filters = [])
    {
        $foundRule = null;

        $location = $move->destinationLocation;

        $filters['action'] = [Enums\RuleAction::PUSH, Enums\RuleAction::PULL_PUSH];

        while (! $foundRule && $location) {
            $filters['source_location_id'] = $location->id;

            $foundRule = $this->searchPushRule(
                $move->productPackaging,
                $move->product,
                $move->warehouse,
                $filters
            );

            $location = $location->parent;
        }

        return $foundRule;
    }

    /**
     * Run a push rule on a move.
     */
    public function runPushRule(Rule $rule, Move $move)
    {
        if ($rule->auto !== Enums\RuleAuto::MANUAL) {
            return;
        }

        $newMove = $move->replicate()->fill([
            'state'                   => Enums\MoveState::DRAFT,
            'reference'               => null,
            'requested_qty'           => $move->received_qty,
            'requested_uom_qty'       => $move->received_qty,
            'origin'                  => $move->origin ?? $move->operation->name ?? '/',
            'operation_id'            => null,
            'source_location_id'      => $move->destination_location_id,
            'destination_location_id' => $rule->destination_location_id,
            'final_location_id'       => $move->final_location_id,
            'rule_id'                 => $rule->id,
            'scheduled_at'            => $move->scheduled_at->addDays($rule->delay),
            'company_id'              => $rule->company_id,
            'operation_type_id'       => $rule->operation_type_id,
            'propagate_cancel'        => $rule->propagate_cancel,
            'warehouse_id'            => $rule->warehouse_id,
            'procure_method'          => Enums\ProcureMethod::MAKE_TO_ORDER,
        ]);

        $newMove->save();

        if ($newMove->shouldBypassReservation()) {
            $newMove->update([
                'procure_method' => Enums\ProcureMethod::MAKE_TO_STOCK,
            ]);
        }

        if (! $newMove->sourceLocation->shouldBypassReservation()) {
            $move->moveDestinations()->attach($newMove->id);
        }

        return $newMove;
    }

    /**
     * Search for a push rule based on the provided filters.
     */
    public function searchPushRule($productPackaging, $product, $warehouse, array $filters)
    {
        if ($warehouse) {
            $filters['warehouse_id'] = $warehouse->id;
        }

        $routeSources = [
            [$productPackaging, 'routes'],
            [$product, 'routes'],
            [$product?->category, 'routes'],
            [$warehouse, 'routes'],
        ];

        foreach ($routeSources as [$source, $relationName]) {
            if (! $source || ! $source->{$relationName}) {
                continue;
            }

            $routeIds = $source->{$relationName}->pluck('id');

            if ($routeIds->isEmpty()) {
                continue;
            }

            $foundRule = Rule::whereIn('route_id', $routeIds)
                ->where($filters)
                ->orderBy('route_sort', 'asc')
                ->orderBy('sort', 'asc')
                ->first();

            if ($foundRule) {
                return $foundRule;
            }
        }

        return null;
    }
}
