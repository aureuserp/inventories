<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\DropshipResource;

class ViewDropship extends ViewRecord
{
    protected static string $resource = DropshipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\OperationState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/resources/dropship/pages/view-dropship.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/dropship/pages/view-dropship.header-actions.delete.notification.body')),
                ),
        ];
    }
}
