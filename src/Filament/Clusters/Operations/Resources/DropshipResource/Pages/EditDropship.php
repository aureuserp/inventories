<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

class EditDropship extends EditRecord
{
    protected static string $resource = DropshipResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.notification.title'))
            ->body(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\Action::make('todo')
                ->label(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.todo.label'))
                ->requiresConfirmation()
                ->action(function (Operation $record) {
                    OperationResource::markAsTodo($record);

                    $this->fillForm();
                })
                ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT),
            Actions\Action::make('validate')
                ->label(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.validate.label'))
                ->color('gray')
                ->action(function (Operation $record) {
                    OperationResource::validate($record);

                    $this->fillForm();
                })
                ->hidden(fn () => in_array($this->getRecord()->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
            Actions\Action::make('cancelAction')
                ->label(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.cancel.label'))
                ->color('gray')
                ->action(function (Operation $record) {
                    OperationResource::cancel($record);

                    $this->fillForm();
                })
                ->visible(fn () => ! in_array($this->getRecord()->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
            Actions\Action::make('return')
                ->label(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.return.label'))
                ->color('gray')
                ->visible(fn () => $this->getRecord()->state == Enums\OperationState::DONE),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\OperationState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/dropship/pages/edit-dropship.header-actions.delete.notification.body')),
                ),
        ];
    }
}
