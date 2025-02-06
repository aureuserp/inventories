<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ReturnAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.return';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/return.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (Operation $record, Component $livewire) {
                $newRecord = $this->processReturn($record);
                
                $livewire->updateForm();

                return redirect()->to(OperationResource::getUrl('edit', ['record' => $newRecord]));
            })
            ->visible(fn () => $this->getRecord()->state == Enums\OperationState::DONE);
    }

    /**
     * Process return for the operation.
     */
    public function processReturn(Operation $record): Operation
    {
        $newOperation = $record->replicate()->fill([
            'state'                   => Enums\OperationState::DRAFT,
            'origin'                  => 'Return of '.$record->name,
            'operation_type_id'       => $record->operationType->returnOperationType?->id ?? $record->operation_type_id,
            'source_location_id'      => $record->destination_location_id,
            'destination_location_id' => $record->source_location_id,
            'user_id'                 => Auth::id(),
            'creator_id'              => Auth::id(),
        ]);

        $newOperation->save();

        foreach ($record->moves as $move) {
            $newMove = $move->replicate()->fill([
                'operation_id'            => $newOperation->id,
                'reference'               => $newOperation->name,
                'state'                   => Enums\MoveState::DRAFT,
                'requested_qty'           => $move->requested_qty,
                'requested_uom_qty'       => $move->requested_uom_qty,
                'source_location_id'      => $move->destination_location_id,
                'destination_location_id' => $move->source_location_id,
                'origin_returned_move_id' => $move->id,
            ]);

            $newMove->save();
        }

        $newOperation->refresh();

        foreach ($newOperation->moves as $move) {
            OperationResource::updateOrCreateMoveLines($move);
        }

        OperationResource::updateOperationState($newOperation);

        $record->update(['return_id' => $newOperation->id]);

        return $newOperation;
    }
}
