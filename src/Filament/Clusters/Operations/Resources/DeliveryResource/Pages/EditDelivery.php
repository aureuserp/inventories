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

                    $this->fillForm();
                })
                ->hidden(fn () => $this->getRecord()->state !== Enums\OperationState::DRAFT),
            Actions\Action::make('check_availability')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.check-availability.label'))
                ->action(function (Operation $record) {
                    OperationResource::checkAvailability($record);

                    $this->fillForm();
                })
                ->hidden(function () {
                    if (! in_array($this->getRecord()->state, [Enums\OperationState::CONFIRMED, Enums\OperationState::ASSIGNED])) {
                        return true;
                    }

                    return ! $this->getRecord()->moves->contains(fn ($move) => in_array($move->state, [Enums\MoveState::CONFIRMED, Enums\MoveState::PARTIALLY_ASSIGNED]));
                }),
            Actions\Action::make('validate')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.validate.label'))
                ->color('gray')
                ->requiresConfirmation(function (Operation $record) {
                    if ($record->operationType->create_backorder != Enums\CreateBackorder::ASK) {
                        return;
                    }

                    return OperationResource::canProcessBackOrder($record);
                })
                ->modalHeading(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.validate.modal-heading'))
                ->modalDescription(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.validate.modal-description'))
                ->extraModalFooterActions([
                    Actions\Action::make('no-backorder')
                        ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.validate.extra-modal-footer-actions.no-backorder.label'))
                        ->color('danger')
                        ->action(function (Operation $record) {
                            OperationResource::validate($record);

                            $this->fillForm();
                        }),
                ])
                ->action(function (Operation $record) {
                    OperationResource::backOrder($record);

                    OperationResource::validate($record);

                    $this->fillForm();
                })
                ->hidden(fn () => in_array($this->getRecord()->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
            Actions\Action::make('cancelAction')
                ->label(__('inventories::filament/clusters/operations/resources/delivery/pages/edit-delivery.header-actions.cancel.label'))
                ->color('gray')
                ->action(function (Operation $record) {
                    OperationResource::cancel($record);

                    $this->fillForm();
                })
                ->visible(fn () => ! in_array($this->getRecord()->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
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
}
