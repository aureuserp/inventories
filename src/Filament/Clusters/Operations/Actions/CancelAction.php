<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

class CancelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/cancel.label'))
            ->color('gray')
            ->action(function (Operation $record): void {
                foreach ($record->moves as $move) {
                    $move->update([
                        'state'        => Enums\MoveState::CANCELED,
                        'received_qty' => 0,
                    ]);

                    $move->lines()->delete();
                }

                OperationResource::updateOperationState($record);

                $this->fillForm([
                    'record' => $record,
                ]);
            })
            ->visible(fn () => ! in_array($this->getRecord()->state, [
                Enums\OperationState::DONE,
                Enums\OperationState::CANCELED,
            ]));
    }
}
