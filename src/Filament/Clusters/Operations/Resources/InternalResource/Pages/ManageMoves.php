<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\InternalResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\InternalResource;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;

class ManageMoves extends ManageRelatedRecords
{
    protected static string $resource = InternalResource::class;

    protected static string $relationship = 'moves';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.date'))
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('reference')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.reference'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lot.name')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.lot'))
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (TraceabilitySettings $traceabilitySettings) => $traceabilitySettings->enable_lots_serial_numbers && $this->getOwnerRecord()->tracking != Enums\ProductTracking::QTY),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.package'))
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (OperationSettings $operationSettings) => $operationSettings->enable_packages),
                Tables\Columns\TextColumn::make('sourceLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.source-location'))
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('destinationLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.destination-location'))
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('qty')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.quantity'))
                    ->sortable()
                    ->color(fn ($record) => $record->destinationLocation->type == Enums\LocationType::INTERNAL ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.state'))
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.columns.done-by'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.actions.delete.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/internal/pages/manage-moves.table.actions.delete.notification.body')),
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
