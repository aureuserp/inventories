<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Scrap;

class EditScrap extends EditRecord
{
    protected static string $resource = ScrapResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/operations/resources/scrap/pages/edit-scrap.notification.title'))
            ->body(__('inventories::filament/clusters/operations/resources/scrap/pages/edit-scrap.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\Action::make('validate')
                ->label(__('inventories::filament/clusters/operations/resources/scrap/pages/edit-scrap.header-actions.validate.label'))
                ->color('gray')
                ->action(function (Scrap $record) {
                    $locationQuantity = ProductQuantity::where('location_id', $record->source_location_id)
                        ->where('product_id', $record->product_id)
                        ->where('package_id', $record->package_id ?? null)
                        ->where('lot_id', $record->lot_id ?? null)
                        ->first();

                    if ($locationQuantity->quantity < $record->qty) {
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/scrap/pages/edit-scrap.header-actions.validate.notification.warning.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/scrap/pages/edit-scrap.header-actions.validate.notification.warning.body'))
                            ->warning()
                            ->send();

                        return;
                    }

                    $locationQuantity->update([
                        'quantity' => $locationQuantity->quantity - $record->qty,
                    ]);

                    ProductQuantity::updateOrCreate(
                        [
                            'location_id' => $record->destination_location_id,
                            'product_id'  => $record->product_id,
                            'lot_id'      => $record->lot_id,
                            'package_id'  => $record->package_id,
                        ], [
                            'quantity'                => $record->qty,
                            'inventory_diff_quantity' => -$record->qty,
                            'company_id'              => $record->company_id,
                            'creator_id'              => Auth::id(),
                            'incoming_at'             => now(),
                        ]
                    );

                    $record->update([
                        'state' => Enums\ScrapState::DONE,
                    ]);

                    $this->createMove($record, $record->qty, $record->source_location_id, $record->destination_location_id);
                })
                ->hidden(fn () => $this->getRecord()->state == Enums\ScrapState::DONE),
        ];
    }

    private function createMove($record, $quantity, $sourceLocationId, $destinationLocationId)
    {
        $move = Move::create([
            'name'                    => $record->name,
            'state'                   => Enums\MoveState::DONE,
            'product_id'              => $record->product_id,
            'source_location_id'      => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'requested_qty'           => abs($quantity),
            'requested_uom_qty'       => abs($quantity),
            'received_qty'            => abs($quantity),
            'reference'               => $record->name,
            'creator_id'              => Auth::id(),
            'company_id'              => $record->company_id,
            'scrap_id'                => $record->id,
        ]);

        $move->lines()->create([
            'state'                   => Enums\MoveState::DONE,
            'qty'                     => abs($quantity),
            'uom_qty'                 => abs($quantity),
            'is_picked'               => 1,
            'scheduled_at'            => now(),
            'product_id'              => $record->product_id,
            'result_package_id'       => $record->package_id,
            'lot_id'                  => $record->lot_id,
            'uom_id'                  => $record->uom_id,
            'source_location_id'      => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'reference'               => $move->reference,
            'company_id'              => $record->company_id,
            'creator_id'              => Auth::id(),
        ]);
    }
}
