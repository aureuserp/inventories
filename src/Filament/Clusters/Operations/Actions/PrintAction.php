<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Models\Operation;
use Filament\Actions\ActionGroup;

class PrintAction extends ActionGroup
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.print';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actions([
            Action::make('first')
                ->label('First Action')
                ->color('primary')
                ->action(function (Operation $record) {
                }),
        ])
        ->label(__('inventories::filament/clusters/operations/actions/print.label'))
        ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT);
    }
}
