<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource;

class ViewScrap extends ViewRecord
{
    protected static string $resource = ScrapResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->getRecord()->state == Enums\ScrapState::DONE)
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/resources/scrap/pages/view-scrap.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/scrap/pages/view-scrap.header-actions.delete.notification.body')),
                ),
        ];
    }
}
