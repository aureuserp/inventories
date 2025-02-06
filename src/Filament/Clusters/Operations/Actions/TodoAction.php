<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

class TodoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.todo';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/todo.label'))
            ->action(function (Operation $record): void {
                if (! $record->moves->count()) {
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/actions/todo.notification.warning.title'))
                        ->body(__('inventories::filament/clusters/operations/actions/todo.notification.warning.body'))
                        ->warning()
                        ->send();

                    return;
                }

                foreach ($record->moves as $move) {
                    OperationResource::updateOrCreateMoveLines($move);
                }

                OperationResource::updateOperationState($record);

                $this->fillForm([
                    'record' => $record,
                ]);

                Notification::make()
                    ->success()
                    ->title(__('inventories::filament/clusters/operations/actions/todo.notification.success.title'))
                    ->body(__('inventories::filament/clusters/operations/actions/todo.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT);
    }
}
