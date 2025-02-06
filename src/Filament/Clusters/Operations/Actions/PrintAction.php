<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Models\Operation;

class PrintAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.print';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/print.label'))
            ->action(function (Operation $record): void {})
            ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT);
    }
}
