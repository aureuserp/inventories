<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

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
            ->action(function (Operation $record): void {
                OperationResource::return($record);

                $this->fillForm([
                    'record' => $record,
                ]);
            })
            ->visible(fn () => $this->getRecord()->state == Enums\OperationState::DONE);
    }
}
