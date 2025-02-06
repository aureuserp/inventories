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

class ValidateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.validate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/validate.label'))
            ->color('gray')
            ->requiresConfirmation(function (Operation $record) {
                if ($record->operationType->create_backorder !== Enums\CreateBackorder::ASK) {
                    return;
                }

                return $this->canProcessBackOrder($record);
            })
            ->modalHeading(__('inventories::filament/clusters/operations/actions/validate.modal-heading'))
            ->modalDescription(__('inventories::filament/clusters/operations/actions/validate.modal-description'))
            ->extraModalFooterActions([
                Action::make('no-backorder')
                    ->label(__('inventories::filament/clusters/operations/actions/validate.extra-modal-footer-actions.no-backorder.label'))
                    ->color('danger')
                    ->action(function (Operation $record): void {
                        $this->validate($record);

                        $this->fillForm([
                            'record' => $record,
                        ]);
                    }),
            ])
            ->action(function (Operation $record): void {
                $this->backOrder($record);

                $this->validate($record);

                $this->fillForm([
                    'record' => $record,
                ]);
            })
            ->hidden(fn () => in_array($this->getRecord()->state, [
                Enums\OperationState::DONE,
                Enums\OperationState::CANCELED,
            ]));
    }

    private function validate(Operation $record)
    {
        foreach ($record->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($record);

        foreach ($record->moves as $move) {
            if ($move->lines->isEmpty()) {
                Notification::make()
                    ->title(__('inventories::filament/clusters/operations/actions/validate.notification.warning.lines-missing.title'))
                    ->body(__('inventories::filament/clusters/operations/actions/validate.notification.warning.lines-missing.body'))
                    ->warning()
                    ->send();

                return;
            }

            foreach ($move->lines as $line) {
                if (! $line->package_id) {
                    continue;
                }

                if (! $line->result_package_id) {
                    continue;
                }

                if ($line->package_id != $line->result_package_id) {
                    continue;
                }

                $sourceQuantity = ProductQuantity::where('product_id', $line->product_id)
                    ->where('location_id', $line->source_location_id)
                    ->where('lot_id', $line->lot_id)
                    ->where('package_id', $line->package_id)
                    ->first();

                if ($sourceQuantity && $sourceQuantity->quantity != $line->qty) {
                    Notification::make()
                        ->title(__('inventories::filament/clusters/operations/actions/validate.notification.warning.partial-package.title'))
                        ->body(__('inventories::filament/clusters/operations/actions/validate.notification.warning.partial-package.body'))
                        ->warning()
                        ->send();

                    return;
                }
            }

            $isLotTracking = $move->product->tracking == Enums\ProductTracking::LOT;

            if (! $isLotTracking) {
                continue;
            }

            if ($move->lines->contains(fn ($line) => ! $line->lot_id)) {
                Notification::make()
                    ->title(__('inventories::filament/clusters/operations/actions/validate.notification.warning.lot-missing.title'))
                    ->body(__('inventories::filament/clusters/operations/actions/validate.notification.warning.lot-missing.body'))
                    ->warning()
                    ->send();

                return;
            }
        }

        foreach ($record->moves as $move) {
            $move->update([
                'state'     => Enums\MoveState::DONE,
                'is_picked' => true,
            ]);

            foreach ($move->lines()->get() as $moveLine) {
                $moveLine->update([
                    'state' => Enums\MoveState::DONE,
                ]);

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
                        $reservedQty = 0;

                        if (
                            $moveLine->sourceLocation->type == Enums\LocationType::INTERNAL
                            && ! $moveLine->sourceLocation->is_stock_location
                        ) {
                            $reservedQty = $moveLine->qty;
                        }

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

                $destinationQuantity = ProductQuantity::where('product_id', $moveLine->product_id)
                    ->where('location_id', $moveLine->destination_location_id)
                    ->where('lot_id', $moveLine->lot_id)
                    ->where('package_id', $moveLine->result_package_id)
                    ->first();

                $reservedQty = 0;

                if (
                    $moveLine->destinationLocation->type == Enums\LocationType::INTERNAL
                    && ! $moveLine->destinationLocation->is_stock_location
                ) {
                    $reservedQty = $moveLine->qty;
                }

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

                if ($moveLine->result_package_id) {
                    $moveLine->resultPackage->update([
                        'location_id' => $moveLine->destination_location_id,
                        'pack_date'   => now(),
                    ]);
                }

                if ($moveLine->lot_id) {
                    $moveLine->lot->update([
                        'location_id' => $moveLine->lot->total_quantity >= $moveLine->qty
                            ? $moveLine->destination_location_id
                            : null,
                    ]);
                }
            }
        }

        OperationResource::updateOperationState($record);

        $this->applyPushRules($record);
    }

    public function backOrder(Operation $record)
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

        $newOperation = $newOperation->refresh();

        foreach ($newOperation->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($newOperation);

        $record->update(['back_order_id' => $newOperation->id]);
    }

    public function canProcessBackOrder(Operation $record): bool
    {
        if ($record->operationType->create_backorder == Enums\CreateBackorder::NEVER) {
            return false;
        }

        return $record->moves->sum('requested_qty') > $record->moves->sum('received_qty');
    }

    public function applyPushRules(Operation $record)
    {
        $rules = [];

        foreach ($record->moves as $move) {
            $rule = $this->getPushRule($move);

            if (! $rule) {
                continue;
            }

            if (! isset($rules[$rule->id])) {
                $rules[$rule->id] = [
                    'rule'  => $rule,
                    'moves' => [$this->runPushRule($rule, $move)],
                ];

                continue;
            }

            $rules[$rule->id]['moves'][] = $this->runPushRule($rule, $move);
        }

        foreach ($rules as $rule) {
            $newOperation = Operation::create([
                'state'                   => Enums\OperationState::DRAFT,
                'origin'                  => $record->name,
                'operation_type_id'       => $rule['rule']->operation_type_id,
                'source_location_id'      => $rule['rule']->source_location_id,
                'destination_location_id' => $rule['rule']->destination_location_id,
                'scheduled_at'            => now()->addDays($rule['rule']->delay),
                'company_id'              => $rule['rule']->company_id,
                'user_id'                 => Auth::id(),
                'creator_id'              => Auth::id(),
            ]);

            foreach ($rule['moves'] as $move) {
                $move->update([
                    'operation_id' => $newOperation->id,
                    'reference'    => $newOperation->name,
                ]);
            }

            $newOperation = $newOperation->refresh();

            foreach ($newOperation->moves as $move) {
                OperationResource::updateOrCreateMoveLines($move);
            }

            OperationResource::updateOperationState($newOperation);
        }
    }

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

    public function runPushRule(Rule $rule, Move $move)
    {
        if ($rule->auto != Enums\RuleAuto::MANUAL) {
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

            if (! $foundRule) {
                continue;
            }

            return $foundRule;
        }

        return null;
    }
}
