<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\QuantityResource\Pages;
use Webkul\Inventory\Filament\Clusters\Products\Resources\LotResource;
use Webkul\Inventory\Filament\Clusters\Products\Resources\PackageResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;

class QuantityResource extends Resource
{
    protected static ?string $model = ProductQuantity::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Operations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/operations/resources/quantity.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations/resources/quantity.navigation.group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('location_id')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.location'))
                    ->relationship(
                        name: 'location',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('type', Enums\LocationType::INTERNAL),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Forms\Components\Select::make('product_id')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->where('is_storable', true),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $set('lot_id', null);
                    }),
                Forms\Components\Select::make('lot_id')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.lot'))
                    ->relationship(
                        name: 'lot',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query, Forms\Get $get) => $query->where('product_id', $get('product_id')),
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Form $form): Form => LotResource::form($form))
                    ->createOptionAction(function (Action $action, Forms\Get $get) {
                        $action
                            ->mutateFormDataUsing(function (array $data) use ($get): array {
                                $data['product_id'] = $get('product_id');

                                return $data;
                            });
                    })
                    ->visible(function (TraceabilitySettings $traceabilitySettings, Forms\Get $get): bool {
                        if (! $traceabilitySettings->enable_lots_serial_numbers) {
                            return false;
                        }

                        $product = Product::find($get('product_id'));

                        if (! $product) {
                            return false;
                        }

                        return $product->tracking === Enums\ProductTracking::LOT;
                    }),
                Forms\Components\Select::make('package_id')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.package'))
                    ->relationship('package', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Form $form): Form => PackageResource::form($form))
                    ->visible(fn (OperationSettings $operationSettings) => $operationSettings->enable_packages),
                Forms\Components\TextInput::make('counted_quantity')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.counted-qty'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Forms\Components\DatePicker::make('scheduled_at')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.form.fields.scheduled-at'))
                    ->native(false)
                    ->default(now()->setDay(app(OperationSettings::class)->annual_inventory_day)->setMonth(app(OperationSettings::class)->annual_inventory_month)),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('location.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.location'))
                    ->searchable()
                    ->sortable()
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('storageCategory.name')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.storage-category'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.package'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (OperationSettings $operationSettings) => $operationSettings->enable_packages),
                Tables\Columns\TextColumn::make('lot.name')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.lot'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (TraceabilitySettings $traceabilitySettings) => $traceabilitySettings->enable_lots_serial_numbers),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.on-hand'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('counted_quantity')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.counted'))
                    ->sortable()
                    ->beforeStateUpdated(function ($record, $state) {
                        $record->update([
                            'inventory_quantity_set'  => true,
                            'inventory_diff_quantity' => $state - $record->quantity,
                        ]);
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/quantity.table.columns.on-hand-before-state-updated.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/quantity.table.columns.on-hand-before-state-updated.notification.body'))
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('inventory_diff_quantity')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.difference'))
                    ->sortable()
                    ->color(fn ($record) => $record->inventory_diff_quantity > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.columns.scheduled-at'))
                    ->sortable()
                    ->date(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.header-actions.create.label'))
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $product = Product::find($data['product_id']);

                        $data['location_id'] = $data['location_id'] ?? Warehouse::first()->lot_stock_location_id;

                        $data['creator_id'] = Auth::id();

                        $data['company_id'] = $product->company_id;

                        $data['inventory_quantity_set'] = true;

                        $data['inventory_diff_quantity'] = $data['counted_quantity'];

                        $data['incoming_at'] = now();

                        $data['scheduled_at'] = now()->setDay(app(OperationSettings::class)->annual_inventory_day)->setMonth(app(OperationSettings::class)->annual_inventory_month);

                        return $data;
                    })
                    ->before(function (array $data) {
                        $existingQuantity = ProductQuantity::where('location_id', $data['location_id'] ?? Warehouse::first()->lot_stock_location_id)
                            ->where('product_id', $data['product_id'])
                            ->where('package_id', $data['package_id'] ?? null)
                            ->where('lot_id', $data['lot_id'] ?? null)
                            ->exists();

                        if ($existingQuantity) {
                            Notification::make()
                                ->title(__('inventories::filament/clusters/operations/resources/quantity.table.header-actions.create.before.notification.title'))
                                ->body(__('inventories::filament/clusters/operations/resources/quantity.table.header-actions.create.before.notification.body'))
                                ->warning()
                                ->send();

                            $this->halt();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/quantity.table.header-actions.create.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/quantity.table.header-actions.create.notification.body')),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('apply')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.actions.apply.label'))
                    ->icon('heroicon-o-check')
                    ->visible(fn (ProductQuantity $record) => $record->inventory_quantity_set)
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/quantity.table.actions.apply.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/quantity.table.actions.apply.notification.body')),
                    )
                    ->action(function (ProductQuantity $record) {
                        $adjustmentLocation = Location::where('type', Enums\LocationType::INVENTORY)
                            ->where('is_scrap', false)
                            ->first();

                        $countedQuantity = $record->counted_quantity;

                        $diffQuantity = $record->inventory_diff_quantity;

                        $record->update([
                            'quantity'                => $countedQuantity,
                            'counted_quantity'        => 0,
                            'inventory_diff_quantity' => 0,
                            'inventory_quantity_set'  => false,
                        ]);

                        ProductQuantity::updateOrCreate(
                            [
                                'location_id' => $adjustmentLocation->id,
                                'product_id'  => $record->product_id,
                                'lot_id'      => $record->lot_id,
                            ], [
                                'quantity'               => -$record->product->on_hand_quantity,
                                'company_id'             => $record->company_id,
                                'creator_id'             => Auth::id(),
                                'incoming_at'            => now(),
                                'inventory_quantity_set' => false,
                            ]
                        );

                        if ($diffQuantity < 0) {
                            $sourceLocationId = $record->location_id;

                            $destinationLocationId = $adjustmentLocation->id;
                        } else {
                            $sourceLocationId = $adjustmentLocation->id;

                            $destinationLocationId = $record->location_id;
                        }

                        static::createMove($record, abs($diffQuantity), $sourceLocationId, $destinationLocationId);
                    }),
                Tables\Actions\Action::make('clear')
                    ->label(__('inventories::filament/clusters/operations/resources/quantity.table.actions.clear.label'))
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (ProductQuantity $record) => $record->inventory_quantity_set)
                    ->color('gray')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/operations/resources/quantity.table.actions.clear.notification.title'))
                            ->body(__('inventories::filament/clusters/operations/resources/quantity.table.actions.clear.notification.body')),
                    )
                    ->action(function (ProductQuantity $record) {
                        $record->update([
                            'inventory_quantity_set'  => false,
                            'counted_quantity'        => 0,
                            'inventory_diff_quantity' => 0,
                        ]);
                    }),
            ])
            ->paginated(false)
            ->modifyQueryUsing(function (Builder $query) {
                $query->whereHas('location', function (Builder $query) {
                    $query->whereIn('type', [Enums\LocationType::INTERNAL, Enums\LocationType::TRANSIT]);
                });
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ManageQuantities::route('/'),
        ];
    }

    private static function createMove($record, $currentQuantity, $sourceLocationId, $destinationLocationId)
    {
        $move = Move::create([
            'name'                    => 'Product Quantity Updated',
            'state'                   => Enums\MoveState::DONE,
            'product_id'              => $record->product_id,
            'source_location_id'      => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'requested_qty'           => abs($currentQuantity),
            'requested_uom_qty'       => abs($currentQuantity),
            'received_qty'            => abs($currentQuantity),
            'reference'               => 'Product Quantity Updated',
            'creator_id'              => Auth::id(),
            'company_id'              => $record->company_id,
        ]);

        $move->lines()->create([
            'state'                   => Enums\MoveState::DONE,
            'qty'                     => abs($currentQuantity),
            'uom_qty'                 => abs($currentQuantity),
            'is_picked'               => 1,
            'scheduled_at'            => now(),
            'operation_id'            => null,
            'product_id'              => $record->product_id,
            'result_package_id'       => $record->package_id,
            'lot_id'                  => $record->lot_id,
            'uom_id'                  => $record->product->uom_id,
            'source_location_id'      => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'reference'               => $move->reference,
            'company_id'              => $record->company_id,
            'creator_id'              => Auth::id(),
        ]);
    }
}
