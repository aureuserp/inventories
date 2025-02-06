<?php

namespace Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Chatter\Filament\Actions\ChatterAction;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/products/resources/product/pages/edit-product.notification.title'))
            ->body(__('inventories::filament/clusters/products/resources/product/pages/edit-product.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterAction::make()
                ->setResource(static::$resource),
            Actions\Action::make('updateQuantity')
                ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.label'))
                ->modalHeading(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.modal-heading'))
                ->form(fn (Product $record): array => [
                    Forms\Components\TextInput::make('quantity')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.form.fields.on-hand-qty'))
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default($record->on_hand_quantity),
                ])
                ->modalSubmitActionLabel(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.update-quantity.modal-submit-action-label'))
                ->beforeFormFilled(function (
                    OperationSettings $operationSettings,
                    TraceabilitySettings $traceabilitySettings,
                    WarehouseSettings $warehouseSettings,
                    Product $record
                ) {
                    if (
                        $operationSettings->enable_packages
                        || $warehouseSettings->enable_locations
                        || (
                            $traceabilitySettings->enable_lots_serial_numbers
                            && $record->tracking != Enums\ProductTracking::QTY
                        )
                    ) {
                        return redirect()->to(ProductResource::getUrl('quantities', ['record' => $record]));
                    }
                })
                ->action(function (Product $record, array $data): void {
                    $previousQuantity = $record->on_hand_quantity;

                    if ($previousQuantity == $data['quantity']) {
                        return;
                    }

                    $warehouse = Warehouse::first();

                    $adjustmentLocation = Location::where('type', Enums\LocationType::INVENTORY)
                        ->where('is_scrap', false)
                        ->first();

                    $currentQuantity = $data['quantity'] - $previousQuantity;

                    if ($currentQuantity < 0) {
                        $sourceLocationId = $data['location_id'] ?? $warehouse->lot_stock_location_id;

                        $destinationLocationId = $adjustmentLocation->id;
                    } else {
                        $sourceLocationId = $data['location_id'] ?? $adjustmentLocation->id;

                        $destinationLocationId = $warehouse->lot_stock_location_id;
                    }

                    $productQuantity = ProductQuantity::where('product_id', $record->id)
                        ->where('location_id', $data['location_id'] ?? $warehouse->lot_stock_location_id)
                        ->first();

                    if ($productQuantity) {
                        $productQuantity->update(['quantity' => $data['quantity']]);
                    } else {
                        $productQuantity = ProductQuantity::create([
                            'product_id'        => $record->id,
                            'company_id'        => $record->company_id,
                            'location_id'       => $data['location_id'] ?? $warehouse->lot_stock_location_id,
                            'package_id'        => $data['package_id'] ?? null,
                            'lot_id'            => $data['lot_id'] ?? null,
                            'quantity'          => $data['quantity'],
                            'reserved_quantity' => 0,
                            'incoming_at'       => now(),
                            'creator_id'        => Auth::id(),
                        ]);
                    }

                    ProductResource::createMove($productQuantity, $currentQuantity, $sourceLocationId, $destinationLocationId);
                }),
            Actions\Action::make('printLabels')
                ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.label'))
                ->color('gray')
                ->form([
                    Forms\Components\TextInput::make('quantity')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.quantity'))
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100),
                    Forms\Components\Radio::make('format')
                        ->label(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format'))
                        ->options([
                            'dymo'       => __('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format-options.dymo'),
                            '2x7_price'  => __('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format-options.2x7_price'),
                            '4x7_price'  => __('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format-options.4x7_price'),
                            '4x12'       => __('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format-options.4x12'),
                            '4x12_price' => __('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.print-labels.form.fields.format-options.4x12_price'),
                        ])
                        ->default('2x7_price')
                        ->required(),
                ])
                ->action(function (array $data, $record) {
                    $pdf = PDF::loadView('inventories::filament.clusters.products.actions.print-labels', [
                        'product'  => $record,
                        'quantity' => $data['quantity'],
                        'format'   => $data['format'],
                    ]);

                    $paperSize = match ($data['format']) {
                        'dymo'  => [0, 0, 252.2, 144],
                        default => 'a4',
                    };

                    $pdf->setPaper($paperSize, 'portrait');

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'product-labels.pdf');
                }),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.delete.notification.title'))
                        ->body(__('inventories::filament/clusters/products/resources/product/pages/edit-product.header-actions.delete.notification.body')),
                ),
        ];
    }
}
