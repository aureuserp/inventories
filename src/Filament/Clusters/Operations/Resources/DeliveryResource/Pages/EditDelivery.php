<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DeliveryResource;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.notification.title'))
            ->body(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\Action::make('todo')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.todo.label'))
                ->requiresConfirmation()
                ->action(function (Operation $record) {
                    OperationResource::markAsTodo($record);
                })
                ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT),
            Actions\Action::make('validate')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.validate.label'))
                ->color('gray')
                ->action(function (Operation $record) {
                    OperationResource::validate($record);
                })
                ->hidden(fn () => $this->getRecord()->state == Enums\OperationState::DONE),
            Actions\Action::make('return')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.return.label'))
                ->color('gray')
                ->visible(fn () => $this->getRecord()->state == Enums\OperationState::DONE),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\OperationState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.delete.notification.body')),
                ),
        ];
    }

    protected function afterSave(): void
    {
        OperationResource::handleUpdate($this->getRecord());
    }
}
