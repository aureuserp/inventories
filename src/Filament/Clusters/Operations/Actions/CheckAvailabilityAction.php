<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;
use Livewire\Component;

class CheckAvailabilityAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.check_availability';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/check-availability.label'))
            ->action(function (Operation $record, Component $livewire): void {
                foreach ($record->moves as $move) {
                    OperationResource::updateOrCreateMoveLines($move);
                }

                OperationResource::updateOperationState($record);

                $livewire->updateForm();
            })
            ->hidden(function () {
                if (! in_array($this->getRecord()->state, [Enums\OperationState::CONFIRMED, Enums\OperationState::ASSIGNED])) {
                    return true;
                }

                return ! $this->getRecord()->moves->contains(fn ($move) => in_array($move->state, [Enums\MoveState::CONFIRMED, Enums\MoveState::PARTIALLY_ASSIGNED]));
            });
    }
}
