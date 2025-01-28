<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource\Pages;
use Webkul\Inventory\Filament\Clusters\Products\Resources\LotResource;
use Webkul\Inventory\Filament\Clusters\Products\Resources\PackageResource;
use Webkul\Inventory\Filament\Clusters\Products\Resources\ProductResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Scrap;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\ProductSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;

class ScrapResource extends Resource
{
    protected static ?string $model = Scrap::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Operations::class;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/operations/resources/scrap.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/operations/resources/scrap.navigation.group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(Enums\ScrapState::options())
                    ->default(Enums\ScrapState::DRAFT)
                    ->disabled(),
                Forms\Components\Section::make(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.title'))
                    ->schema([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('product_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.product'))
                                            ->relationship(name: 'product', titleAttribute: 'name')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $set('lot_id', null);

                                                if ($product = Product::find($get('product_id'))) {
                                                    $set('uom_id', $product->uom_id);
                                                }
                                            })
                                            ->createOptionForm(fn (Form $form): Form => ProductResource::form($form))
                                            ->createOptionAction(fn ($action) => $action->modalWidth('6xl'))
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\TextInput::make('qty')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.quantity'))
                                            ->required()
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\Select::make('uom_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.unit'))
                                            ->relationship(
                                                'uom',
                                                'name',
                                                fn ($query) => $query->where('category_id', 1),
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->visible(fn (ProductSettings $productSettings) => $productSettings->enable_uom)
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\Select::make('lot_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.lot'))
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->relationship(
                                                name: 'lot',
                                                titleAttribute: 'name',
                                                modifyQueryUsing: fn (Builder $query, Forms\Get $get) => $query->where('product_id', $get('product_id')),
                                            )
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE)
                                            ->visible(function (TraceabilitySettings $traceabilitySettings, Forms\Get $get): bool {
                                                if (! $traceabilitySettings->enable_lots_serial_numbers) {
                                                    return false;
                                                }

                                                $product = Product::find($get('product_id'));

                                                if (! $product) {
                                                    return false;
                                                }

                                                return $product->tracking === Enums\ProductTracking::LOT;
                                            })
                                            ->createOptionForm(fn (Form $form): Form => LotResource::form($form))
                                            ->createOptionAction(function (Action $action, Forms\Get $get) {
                                                $action
                                                    ->mutateFormDataUsing(function (array $data) use ($get): array {
                                                        $data['product_id'] = $get('product_id');

                                                        return $data;
                                                    });
                                            }),
                                        Forms\Components\Select::make('tags')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.tags'))
                                            ->relationship(name: 'tags', titleAttribute: 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.name'))
                                                    ->required()
                                                    ->unique('inventories_tags'),
                                            ]),
                                    ]),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('package_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.package'))
                                            ->relationship('package', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm(fn (Form $form): Form => PackageResource::form($form))
                                            ->visible(fn (OperationSettings $operationSettings) => $operationSettings->enable_packages)
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\Select::make('partner_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.owner'))
                                            ->relationship('partner', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\Select::make('source_location_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.source-location'))
                                            ->relationship('sourceLocation', 'full_name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->default(function () {
                                                $scrapLocation = Location::where('type', Enums\LocationType::INTERNAL)
                                                    ->where('is_scrap', false)
                                                    ->first();

                                                return $scrapLocation?->id;
                                            })
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\Select::make('destination_location_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.destination-location'))
                                            ->relationship('destinationLocation', 'full_name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->default(function () {
                                                $scrapLocation = Location::where('is_scrap', true)
                                                    ->first();

                                                return $scrapLocation?->id;
                                            })
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                        Forms\Components\TextInput::make('origin')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.source-document')),
                                        Forms\Components\Select::make('company_id')
                                            ->label(__('inventories::filament/clusters/operations/resources/scrap.form.sections.general.fields.company'))
                                            ->relationship('company', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->default(Auth::user()->default_company_id)
                                            ->disabled(fn ($record): bool => $record?->state == Enums\ScrapState::DONE),
                                    ]),
                            ])
                            ->columns(2),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('closed_at')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.date'))
                    ->sortable()
                    ->date(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.reference'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.product'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lot.name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.lot'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (TraceabilitySettings $traceabilitySettings) => $traceabilitySettings->enable_lots_serial_numbers),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.package'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->visible(fn (OperationSettings $operationSettings) => $operationSettings->enable_packages),
                Tables\Columns\TextColumn::make('sourceLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.source-location'))
                    ->sortable()
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('destinationLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.scrap-location'))
                    ->sortable()
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('qty')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.quantity'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('uom.name')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.uom'))
                    ->sortable()
                    ->visible(fn (WarehouseSettings $warehouseSettings) => $warehouseSettings->enable_locations),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('inventories::filament/clusters/operations/resources/scrap.table.columns.state'))
                    ->sortable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('inventories::filament/clusters/operations/resources/scrap.table.actions.delete.notification.title'))
                                ->body(__('inventories::filament/clusters/operations/resources/scrap.table.actions.delete.notification.body')),
                        ),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewScrap::class,
            Pages\EditScrap::class,
            Pages\ManageMoves::class,
        ]);
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
            'index'  => Pages\ListScraps::route('/'),
            'edit'   => Pages\EditScrap::route('/{record}/edit'),
            'view' => Pages\ViewScrap::route('/{record}/view'),
            'edit'   => Pages\EditScrap::route('/{record}/edit'),
            'moves'   => Pages\ManageMoves::route('/{record}/moves'),
        ];
    }
}
