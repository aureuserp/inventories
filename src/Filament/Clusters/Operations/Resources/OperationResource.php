<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources;

use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint\Operators\IsRelatedToOperator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\Field\Filament\Forms\Components\ProgressStepper;
use Webkul\Field\Filament\Traits\HasCustomFields;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources;
use Webkul\Inventory\Filament\Clusters\Products\Resources\LotResource;
use Webkul\Inventory\Filament\Clusters\Products\Resources\PackageResource;
use Webkul\Inventory\Models\Rule;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Settings\OperationSettings;
use Webkul\Inventory\Settings\ProductSettings;
use Webkul\Inventory\Settings\TraceabilitySettings;
use Webkul\Inventory\Settings\WarehouseSettings;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\Inventory\Filament\Resources\PartnerResource;
use Webkul\Inventory\Filament\Resources\PartnerAddressResource;

class OperationResource extends Resource
{
    use HasCustomFields;

    protected static ?string $model = Operation::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(Enums\OperationState::options())
                    ->default(Enums\OperationState::DRAFT)
                    ->disabled(),
                Forms\Components\Section::make(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.title'))
                    ->schema([
                        Forms\Components\Select::make('partner_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.receive-from'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn (Form $form): Form => PartnerResource::form($form))
                            ->visible(fn (Forms\Get $get): bool => OperationType::find($get('operation_type_id'))?->type == Enums\OperationType::INCOMING)
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                        Forms\Components\Select::make('partner_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.contact'))
                            ->relationship('partner', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn (Form $form): Form => PartnerResource::form($form))
                            ->visible(fn (Forms\Get $get): bool => OperationType::find($get('operation_type_id'))?->type == Enums\OperationType::INTERNAL)
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                        Forms\Components\Select::make('partner_address_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.delivery-address'))
                            ->relationship('partnerAddress', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn (Form $form): Form => PartnerAddressResource::form($form))
                            ->visible(fn (Forms\Get $get): bool => OperationType::find($get('operation_type_id'))?->type == Enums\OperationType::OUTGOING)
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                        Forms\Components\Select::make('operation_type_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.operation-type'))
                            ->relationship('operationType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->getOptionLabelFromRecordUsing(function (OperationType $record) {
                                if (! $record->warehouse) {
                                    return $record->name;
                                }

                                return $record->warehouse->name.': '.$record->name;
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $operationType = OperationType::find($get('operation_type_id'));

                                $set('source_location_id', $operationType->source_location_id);
                                $set('destination_location_id', $operationType->destination_location_id);
                            })
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                        Forms\Components\Select::make('source_location_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.source-location'))
                            ->relationship('sourceLocation', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (WarehouseSettings $settings, Forms\Get $get): bool => $settings->enable_locations && OperationType::find($get('operation_type_id'))?->type != Enums\OperationType::INCOMING)
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                        Forms\Components\Select::make('destination_location_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.sections.general.fields.destination-location'))
                            ->relationship('destinationLocation', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (WarehouseSettings $settings, Forms\Get $get): bool => $settings->enable_locations && OperationType::find($get('operation_type_id'))?->type != Enums\OperationType::OUTGOING)
                            ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                    ])
                    ->columns(2),

                Forms\Components\Tabs::make()
                    ->schema([
                        Forms\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.title'))
                            ->schema([
                                static::getMovesRepeater(),
                            ]),

                        Forms\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.title'))
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.responsible'))
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(Auth::id())
                                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                                Forms\Components\Select::make('move_type')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.shipping-policy'))
                                    ->options(Enums\MoveType::class)
                                    ->default(Enums\MoveType::DIRECT)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.shipping-policy-hint-tooltip'))
                                    ->visible(fn (Forms\Get $get): bool => OperationType::find($get('operation_type_id'))?->type != Enums\OperationType::INCOMING)
                                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                                Forms\Components\DateTimePicker::make('scheduled_at')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.scheduled-at'))
                                    ->native(false)
                                    ->default(now()->format('Y-m-d H:i:s'))
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.scheduled-at-hint-tooltip'))
                                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                                Forms\Components\TextInput::make('origin')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.source-document'))
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('inventories::filament/clusters/operations/resources/operation.form.tabs.additional.fields.source-document-hint-tooltip'))
                                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
                            ])
                            ->columns(2),

                        Forms\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.form.tabs.note.title'))
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->hiddenLabel(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_favorite')
                    ->label('')
                    ->icon(fn (Operation $record): string => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn (Operation $record): string => $record->is_favorite ? 'warning' : 'gray')
                    ->action(function (Operation $record): void {
                        $record->update([
                            'is_favorite' => ! $record->is_favorite,
                        ]);
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.reference'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sourceLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.from'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (WarehouseSettings $settings): bool => $settings->enable_locations),
                Tables\Columns\TextColumn::make('destinationLocation.full_name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.to'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (WarehouseSettings $settings): bool => $settings->enable_locations),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.contact'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.responsible'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.scheduled-at'))
                    ->placeholder('—')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.deadline'))
                    ->placeholder('—')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.closed-at'))
                    ->placeholder('—')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('origin')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.source-document'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('operationType.name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.operation-type'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('company.name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.company'))
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.columns.state'))
                    ->searchable()
                    ->sortable()
                    ->badge(),
            ])
            ->groups([
                Tables\Grouping\Group::make('state')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.groups.state')),
                Tables\Grouping\Group::make('origin')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.groups.source-document')),
                Tables\Grouping\Group::make('operationType.name')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.groups.operation-type')),
                Tables\Grouping\Group::make('schedule_at')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.groups.schedule-at'))
                    ->date(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.table.groups.created-at'))
                    ->date(),
            ])
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraints(collect(static::mergeCustomTableQueryBuilderConstraints([
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('name')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.name')),
                        Tables\Filters\QueryBuilder\Constraints\SelectConstraint::make('state')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.state'))
                            ->multiple()
                            ->options(Enums\OperationState::class)
                            ->icon('heroicon-o-bars-2'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('partner')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.partner'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('user')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.responsible'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('owner')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.owner'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                        app(WarehouseSettings::class)->enable_locations
                            ? Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('sourceLocation')
                                ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.source-location'))
                                ->multiple()
                                ->selectable(
                                    IsRelatedToOperator::make()
                                        ->titleAttribute('full_name')
                                        ->searchable()
                                        ->multiple()
                                        ->preload(),
                                )
                                ->icon('heroicon-o-map-pin')
                            : null,
                        app(WarehouseSettings::class)->enable_locations
                            ? Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('destinationLocation')
                                ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.destination-location'))
                                ->multiple()
                                ->selectable(
                                    IsRelatedToOperator::make()
                                        ->titleAttribute('full_name')
                                        ->searchable()
                                        ->multiple()
                                        ->preload(),
                                )
                                ->icon('heroicon-o-map-pin')
                            : null,
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('deadline')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.deadline'))
                            ->icon('heroicon-o-calendar'),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('scheduled_at')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.scheduled-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('closed_at')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.closed-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.created-at')),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('updated_at')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.updated-at')),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('company')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.company'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-building-office'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('creator')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.table.filters.creator'))
                            ->multiple()
                            ->selectable(
                                IsRelatedToOperator::make()
                                    ->titleAttribute('name')
                                    ->searchable()
                                    ->multiple()
                                    ->preload(),
                            )
                            ->icon('heroicon-o-user'),
                    ]))->filter()->values()->all()),
            ], layout: \Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->slideOver(),
            )
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->slideOver(),
            )
            ->filtersFormColumns(2)
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => static::can('delete', $record) && $record->state !== Enums\OperationState::DONE,
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('state')
                            ->badge(),
                    ])
                    ->compact(),

                Infolists\Components\Section::make(__('inventories::filament/clusters/operations/resources/operation.infolist.sections.general.title'))
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('partner.name')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.sections.general.entries.contact'))
                                    ->icon('heroicon-o-user-group')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('operationType.name')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.sections.general.entries.operation-type'))
                                    ->icon('heroicon-o-clipboard-document-list'),

                                Infolists\Components\TextEntry::make('sourceLocation.full_name')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.sections.general.entries.source-location'))
                                    ->icon('heroicon-o-arrow-up-tray')
                                    ->visible(fn (WarehouseSettings $settings): bool => $settings->enable_locations),

                                Infolists\Components\TextEntry::make('destinationLocation.full_name')
                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.sections.general.entries.destination-location'))
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->visible(fn (WarehouseSettings $settings): bool => $settings->enable_locations),
                            ]),
                    ]),

                // Tabs Section
                Infolists\Components\Tabs::make('Details')
                    ->tabs([
                        // Operations Tab
                        Infolists\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.title'))
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('moves')
                                    ->schema([
                                        Infolists\Components\Grid::make(5)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('product.name')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.product'))
                                                    ->icon('heroicon-o-cube'),

                                                Infolists\Components\TextEntry::make('finalLocation.full_name')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.final-location'))
                                                    ->icon('heroicon-o-map-pin')
                                                    ->placeholder('—')
                                                    ->visible(fn (WarehouseSettings $settings) => $settings->enable_locations),

                                                Infolists\Components\TextEntry::make('description')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.description'))
                                                    ->icon('heroicon-o-document-text')
                                                    ->placeholder('—'),

                                                Infolists\Components\TextEntry::make('scheduled_at')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.scheduled-at'))
                                                    ->dateTime()
                                                    ->icon('heroicon-o-calendar')
                                                    ->placeholder('—'),

                                                Infolists\Components\TextEntry::make('deadline')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.deadline'))
                                                    ->dateTime()
                                                    ->icon('heroicon-o-clock')
                                                    ->placeholder('—'),

                                                Infolists\Components\TextEntry::make('productPackaging.name')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.packaging'))
                                                    ->icon('heroicon-o-gift')
                                                    ->visible(fn (ProductSettings $settings) => $settings->enable_packagings)
                                                    ->placeholder('—'),

                                                Infolists\Components\TextEntry::make('requested_qty')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.demand'))
                                                    ->icon('heroicon-o-calculator'),

                                                Infolists\Components\TextEntry::make('received_qty')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.quantity'))
                                                    ->icon('heroicon-o-scale')
                                                    ->placeholder('—'),

                                                Infolists\Components\TextEntry::make('uom.name')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.unit'))
                                                    ->icon('heroicon-o-beaker')
                                                    ->visible(fn (ProductSettings $settings) => $settings->enable_uom),

                                                Infolists\Components\IconEntry::make('is_picked')
                                                    ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.operations.entries.picked'))
                                                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                                            ]),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.additional.title'))
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('user.name')
                                            ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.additional.entries.responsible'))
                                            ->icon('heroicon-o-user')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('move_type')
                                            ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.additional.entries.shipping-policy'))
                                            ->icon('heroicon-o-truck')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('scheduled_at')
                                            ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.additional.entries.scheduled-at'))
                                            ->dateTime()
                                            ->icon('heroicon-o-calendar')
                                            ->placeholder('—'),

                                        Infolists\Components\TextEntry::make('origin')
                                            ->label(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.additional.entries.source-document'))
                                            ->icon('heroicon-o-document-text')
                                            ->placeholder('—'),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make(__('inventories::filament/clusters/operations/resources/operation.infolist.tabs.note.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->markdown()
                                    ->hiddenLabel()
                                    ->placeholder('—'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        return match ($parameters['record']?->operationType->type) {
            Enums\OperationType::INCOMING => Resources\ReceiptResource::getUrl('view', $parameters, $isAbsolute, $panel, $tenant),
            Enums\OperationType::INTERNAL => Resources\InternalResource::getUrl('view', $parameters, $isAbsolute, $panel, $tenant),
            Enums\OperationType::OUTGOING => Resources\DeliveryResource::getUrl('view', $parameters, $isAbsolute, $panel, $tenant),
            default                       => parent::getUrl('view', $parameters, $isAbsolute, $panel, $tenant),
        };
    }

    public static function getMovesRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('moves')
            ->hiddenLabel()
            ->relationship()
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.product'))
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        if ($product = Product::find($get('product_id'))) {
                            $set('product_packaging_id', $product->product_packaging_id);

                            $set('uom_id', $product->uom_id);
                        }
                    })
                    ->disabled(fn (Move $move): bool => $move->id && $move->state !== Enums\MoveState::DRAFT),
                Forms\Components\Select::make('final_location_id')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.final-location'))
                    ->relationship('finalLocation', 'full_name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (WarehouseSettings $settings) => $settings->enable_locations)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\TextInput::make('description')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.description'))
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.scheduled-at'))
                    ->default(now())
                    ->native(false)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\DateTimePicker::make('deadline')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.deadline'))
                    ->native(false)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\Select::make('product_packaging_id')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.packaging'))
                    ->relationship('productPackaging', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (ProductSettings $settings) => $settings->enable_packagings)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\TextInput::make('requested_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.demand'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->disabled(fn (Move $move): bool => $move->id && $move->state !== Enums\MoveState::DRAFT),
                Forms\Components\TextInput::make('received_qty')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.quantity'))
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required()
                    ->visible(fn (Move $move): bool => $move->id && $move->state !== Enums\MoveState::DRAFT)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED]))
                    ->suffixAction(fn ($record) => static::getMoveLinesAction($record)),
                Forms\Components\Select::make('uom_id')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.unit'))
                    ->relationship(
                        'uom',
                        'name',
                        fn ($query) => $query->where('category_id', 1),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (ProductSettings $settings) => $settings->enable_uom)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                Forms\Components\Toggle::make('is_picked')
                    ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.picked'))
                    ->default(0)
                    ->inline(false)
                    ->disabled(fn ($record): bool => in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
            ])
            ->columns(4)
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $record) {
                $product = Product::find($data['product_id']);

                $data = array_merge($data, [
                    'creator_id'              => Auth::id(),
                    'company_id'              => Auth::user()->default_company_id,
                    'warehouse_id'            => $record->destinationLocation->warehouse_id,
                    'state'                   => $record->state->value,
                    'name'                    => $product->name,
                    'procure_method'          => Enums\ProcureMethod::MAKE_TO_STOCK,
                    'uom_id'                  => $data['uom_id'] ?? $product->uom_id,
                    'requested_uom_qty'       => $data['requested_qty'],
                    'operation_type_id'       => $record->operation_type_id,
                    'received_qty'            => NULL,
                    'source_location_id'      => $record->source_location_id,
                    'destination_location_id' => $record->destination_location_id,
                    'scheduled_at'            => $record->scheduled_at ?? now(),
                    'reference'               => $record->name,
                ]);

                return $data;
            })
            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $record) {
                if (isset($data['received_qty'])) {
                    $record->fill([
                        'received_qty' => $data['received_qty'] ?? NULL,
                    ]);

                    static::updateOrCreateMoveLines($record);

                    static::updateOperationState($record->operation);
                }

                return $data;
            })
            ->deletable(fn ($record): bool => ! in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED]))
            ->addable(fn ($record): bool => ! in_array($record?->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED]));
    }

    public static function getMoveLinesAction($move): Forms\Components\Actions\Action
    {
        $columns = 2;

        if (
            app(TraceabilitySettings::class)->enable_lots_serial_numbers
            && $move->product->tracking == Enums\ProductTracking::LOT
            && $move->sourceLocation->type == Enums\LocationType::SUPPLIER
        ) {
            $columns++;
        }

        if ($move->sourceLocation->type == Enums\LocationType::INTERNAL) {
            $columns++;
        }

        if ($move->destinationLocation->type != Enums\LocationType::INTERNAL) {
            $columns--;
        }

        if (app(OperationSettings::class)->enable_packages) {
            $columns++;
        }

        if (app(ProductSettings::class)->enable_uom) {
            $columns++;
        }

        return Forms\Components\Actions\Action::make('manageLines')
            ->icon('heroicon-m-bars-4')
            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.modal-heading'))
            ->modalSubmitActionLabel('Save')
            ->visible(app(WarehouseSettings::class)->enable_locations)
            ->form([
                Forms\Components\Repeater::make('lines')
                    ->hiddenLabel()
                    ->relationship('lines')
                    ->schema([
                        Forms\Components\Select::make('quantity_id')
                            ->label(__(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.pick-from')))
                            ->options(function ($record) use ($move) {
                                if (in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])) {
                                    $nameParts = array_filter([
                                        $record->sourceLocation->full_name,
                                        $record->lot?->name,
                                        $record->package?->name,
                                    ]);

                                    return [
                                        $record->id => implode(' - ', $nameParts),
                                    ];
                                }

                                return ProductQuantity::with(['location', 'lot', 'package'])
                                    ->where('product_id', $move->product_id)
                                    ->whereHas('location', function (Builder $query) use ($move) {
                                        $query->where('id', $move->source_location_id)
                                            ->orWhere('parent_id', $move->source_location_id);
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($quantity) {
                                        $nameParts = array_filter([
                                            $quantity->location->full_name,
                                            $quantity->lot?->name,
                                            $quantity->package?->name,
                                        ]);

                                        return [$quantity->id => implode(' - ', $nameParts)];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                                if (in_array($record?->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])) {
                                    $component->state($record->id);

                                    return;
                                }

                                $productQuantity = ProductQuantity::with(['location', 'lot', 'package'])
                                    ->where('product_id', $record?->product_id)
                                    ->where('location_id', $record?->source_location_id)
                                    ->where('lot_id', $record?->lot_id ?? null)
                                    ->where('package_id', $record?->package_id ?? null)
                                    ->first();

                                $component->state($productQuantity?->id);
                            })
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $productQuantity = ProductQuantity::find($get('quantity_id'));

                                $set('lot_id', $productQuantity?->lot_id);

                                $set('result_package_id', $productQuantity?->package_id);

                                $set('qty', $productQuantity?->quantity);
                            })
                            ->visible($move->sourceLocation->type == Enums\LocationType::INTERNAL)
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                        Forms\Components\Select::make('lot_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.lot'))
                            ->relationship(
                                name: 'lot',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('product_id', $move->product_id),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED]))
                            ->disableOptionWhen(fn () => ! $move->operationType->use_existing_lots)
                            ->createOptionForm(fn (Form $form): Form => LotResource::form($form))
                            ->createOptionAction(function (Action $action) use ($move) {
                                $action->visible($move->operationType->use_create_lots)
                                    ->mutateFormDataUsing(function (array $data) use ($move) {
                                        $data['product_id'] = $move->product_id;

                                        return $data;
                                    });
                            })
                            ->visible(fn (TraceabilitySettings $settings): bool => $settings->enable_lots_serial_numbers
                                && $move->product->tracking == Enums\ProductTracking::LOT
                                && $move->sourceLocation->type == Enums\LocationType::SUPPLIER
                            ),
                        Forms\Components\Select::make('destination_location_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.location'))
                            ->relationship(
                                name: 'destinationLocation',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('type', '<>', Enums\LocationType::VIEW)
                                    ->where(function ($query) use ($move) {
                                        $query->where('id', $move->destination_location_id)
                                            ->orWhere('parent_id', $move->destination_location_id);
                                    }),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->default($move->destination_location_id)
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('result_package_id', null);
                            })
                            ->visible($move->destinationLocation->type == Enums\LocationType::INTERNAL)
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                        Forms\Components\Select::make('result_package_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.package'))
                            ->relationship(
                                name: 'resultPackage',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, Forms\Get $get, $record) => $query
                                    ->where(function ($query) use ($get, $record) {
                                        $query->where('location_id', $get('destination_location_id'))
                                            ->orWhere('id', $record?->package_id)
                                            ->orWhereNull('location_id');
                                    }),
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn (Form $form): Form => PackageResource::form($form))
                            ->createOptionAction(function (Action $action) use ($move) {
                                $action->mutateFormDataUsing(function (array $data) use ($move) {
                                    $data['company_id'] = $move->company_id;

                                    return $data;
                                });
                            })
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED]))
                            ->visible(fn (OperationSettings $settings) => $settings->enable_packages),
                        Forms\Components\TextInput::make('qty')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.quantity'))
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                        Forms\Components\Select::make('uom_id')
                            ->label(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.fields.uom'))
                            ->relationship(
                                'uom',
                                'name',
                                fn ($query) => $query->where('category_id', 1),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (ProductSettings $settings) => $settings->enable_uom)
                            ->disabled(fn (): bool => in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel(__('inventories::filament/clusters/operations/resources/operation.form.tabs.operations.fields.lines.add-line'))
                    ->columns($columns)
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Move $move): array {
                        if (isset($data['quantity_id'])) {
                            $productQuantity = ProductQuantity::find($data['quantity_id']);

                            $data['lot_id'] = $productQuantity?->lot_id;

                            $data['package_id'] = $productQuantity?->package_id;
                        }

                        $data['reference'] = $move->reference;
                        $data['state'] = $move->state;
                        $data['uom_qty'] = $data['qty'];
                        $data['scheduled_at'] = $move->scheduled_at;
                        $data['operation_id'] = $move->operation_id;
                        $data['move_id'] = $move->id;
                        $data['source_location_id'] = $move->source_location_id;
                        $data['uom_id'] ??= $move->uom_id;
                        $data['creator_id'] = Auth::id();
                        $data['product_id'] = $move->product_id;
                        $data['company_id'] = $move->company_id;

                        return $data;
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        if (isset($data['quantity_id'])) {
                            $productQuantity = ProductQuantity::find($data['quantity_id']);

                            $data['lot_id'] = $productQuantity?->lot_id;

                            $data['package_id'] = $productQuantity?->package_id;
                        }

                        return $data;
                    })
                    ->deletable(fn (): bool => ! in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED]))
                    ->addable(fn (): bool => ! in_array($move->state, [Enums\MoveState::DONE, Enums\MoveState::CANCELED])),
            ])
            ->modalWidth('6xl')
            ->mountUsing(function (Forms\ComponentContainer $form, Move $record) {
                $form->fill([]);
            })
            ->action(function (Forms\Set $set, array $data, Move $record): void {
                $totalQty = $record->lines()->sum('qty');

                $record->fill([
                    'received_qty' => $totalQty,
                ]);

                static::updateOrCreateMoveLines($record);

                $set('received_qty', $totalQty);
            });
    }

    public static function getPresetTableViews(): array
    {
        return [
            'todo_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.todo'))
                ->favorite()
                ->icon('heroicon-s-clipboard-document-list')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('state', [Enums\OperationState::DONE, Enums\OperationState::CANCELED])),
            'my_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.my'))
                ->favorite()
                ->icon('heroicon-s-user')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', Auth::id())),
            'favorite_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.starred'))
                ->favorite()
                ->icon('heroicon-s-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_favorite', true)),
            'draft_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.draft'))
                ->favorite()
                ->icon('heroicon-s-pencil-square')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('state', Enums\OperationState::DRAFT)),
            'waiting_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.waiting'))
                ->favorite()
                ->icon('heroicon-s-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('state', Enums\OperationState::CONFIRMED)),
            'ready_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.ready'))
                ->favorite()
                ->icon('heroicon-s-play-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('state', Enums\OperationState::ASSIGNED)),
            'done_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.done'))
                ->favorite()
                ->icon('heroicon-s-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('state', Enums\OperationState::DONE)),
            'canceled_receipts' => PresetView::make(__('inventories::filament/clusters/operations/resources/operation.tabs.canceled'))
                ->icon('heroicon-s-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('state', Enums\OperationState::CANCELED)),
        ];
    }

    public static function markAsTodo(Operation $record)
    {
        if (! $record->moves->count()) {
            Notification::make()
                ->success()
                ->title(__('inventories::filament/clusters/operations/resources/operation.header-actions.todo.notification.warning.title'))
                ->body(__('inventories::filament/clusters/operations/resources/operation.header-actions.todo.notification.warning.body'))
                ->warning()
                ->send();

            return;
        }

        static::checkAvailability($record);

        Notification::make()
            ->success()
            ->title(__('inventories::filament/clusters/operations/resources/operation.header-actions.todo.notification.success.title'))
            ->body(__('inventories::filament/clusters/operations/resources/operation.header-actions.todo.notification.success.body'))
            ->success()
            ->send();
    }

    public static function checkAvailability(Operation $record)
    {
        foreach ($record->moves as $move) {
            static::updateOrCreateMoveLines($move);
        }

        static::updateOperationState($record);
    }

    public static function validate(Operation $record)
    {
        static::checkAvailability($record);

        foreach ($record->moves as $move) {
            if ($move->lines->isEmpty()) {
                Notification::make()
                    ->title(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.lines-missing.title'))
                    ->body(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.lines-missing.body'))
                    ->warning()
                    ->send();

                return;
            }

            foreach ($move->lines as $line) {
                if (! $line->package_id) {
                    continue;
                }

                if (! $line->result_package_id) {
                    continue;
                }

                if ($line->package_id != $line->result_package_id) {
                    continue;
                }

                $sourceQuantity = ProductQuantity::where('product_id', $line->product_id)
                    ->where('location_id', $line->source_location_id)
                    ->where('lot_id', $line->lot_id)
                    ->where('package_id', $line->package_id)
                    ->first();

                if ($sourceQuantity && $sourceQuantity->quantity != $line->qty) {
                    Notification::make()
                        ->title(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.partial-package.title'))
                        ->body(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.partial-package.body'))
                        ->warning()
                        ->send();

                    return;
                }
            }

            $isLotTracking = $move->product->tracking == Enums\ProductTracking::LOT;

            if (! $isLotTracking) {
                continue;
            }

            if ($move->lines->contains(fn ($line) => ! $line->lot_id)) {
                Notification::make()
                    ->title(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.lot-missing.title'))
                    ->body(__('inventories::filament/clusters/operations/resources/operation.header-actions.validate.notification.warning.lot-missing.body'))
                    ->warning()
                    ->send();

                return;
            }
        }

        foreach ($record->moves as $move) {
            $move->update([
                'state'     => Enums\MoveState::DONE,
                'is_picked' => true,
            ]);

            foreach ($move->lines()->get() as $moveLine) {
                $moveLine->update([
                    'state' => Enums\MoveState::DONE,
                ]);

                $sourceQuantity = ProductQuantity::where('product_id', $moveLine->product_id)
                    ->where('location_id', $moveLine->source_location_id)
                    ->where('lot_id', $moveLine->lot_id)
                    ->where('package_id', $moveLine->package_id)
                    ->first();

                if ($sourceQuantity) {
                    $remainingQty = $sourceQuantity->quantity - $moveLine->qty;

                    if ($remainingQty == 0) {
                        $sourceQuantity->delete();
                    } else {
                        $reservedQty = 0;

                        if (
                            $moveLine->sourceLocation->type == Enums\LocationType::INTERNAL
                            && ! $moveLine->sourceLocation->is_stock_location
                        ) {
                            $reservedQty = $moveLine->qty;
                        }

                        $sourceQuantity->update([
                            'quantity'                => $remainingQty,
                            'reserved_quantity'       => $sourceQuantity->reserved_quantity - $reservedQty,
                            'inventory_diff_quantity' => $sourceQuantity->inventory_diff_quantity + $moveLine->qty,
                        ]);
                    }
                } else {
                    ProductQuantity::create([
                        'product_id'              => $moveLine->product_id,
                        'location_id'             => $moveLine->source_location_id,
                        'lot_id'                  => $moveLine->lot_id,
                        'package_id'              => $moveLine->package_id,
                        'quantity'                => -$moveLine->qty,
                        'inventory_diff_quantity' => $moveLine->qty,
                        'company_id'              => $moveLine->sourceLocation->company_id,
                        'creator_id'              => Auth::id(),
                        'incoming_at'             => now(),
                    ]);
                }

                $destinationQuantity = ProductQuantity::where('product_id', $moveLine->product_id)
                    ->where('location_id', $moveLine->destination_location_id)
                    ->where('lot_id', $moveLine->lot_id)
                    ->where('package_id', $moveLine->result_package_id)
                    ->first();

                $reservedQty = 0;

                if (
                    $moveLine->destinationLocation->type == Enums\LocationType::INTERNAL
                    && ! $moveLine->destinationLocation->is_stock_location
                ) {
                    $reservedQty = $moveLine->qty;
                }

                if ($destinationQuantity) {  
                    $destinationQuantity->update([
                        'quantity'                => $destinationQuantity->quantity + $moveLine->qty,
                        'reserved_quantity'       => $destinationQuantity->reserved_quantity + $reservedQty,
                        'inventory_diff_quantity' => $destinationQuantity->inventory_diff_quantity - $moveLine->qty,
                    ]);
                } else {
                    ProductQuantity::create([
                        'product_id'              => $moveLine->product_id,
                        'location_id'             => $moveLine->destination_location_id,
                        'package_id'              => $moveLine->result_package_id,
                        'lot_id'                  => $moveLine->lot_id,
                        'quantity'                => $moveLine->qty,
                        'reserved_quantity'       => $reservedQty,
                        'inventory_diff_quantity' => -$moveLine->qty,
                        'incoming_at'             => now(),
                        'creator_id'              => Auth::id(),
                        'company_id'              => $moveLine->destinationLocation->company_id,
                    ]);
                }

                if ($moveLine->result_package_id) {
                    $moveLine->resultPackage->update([
                        'location_id' => $moveLine->destination_location_id,
                        'pack_date'   => now(),
                    ]);
                }

                if ($moveLine->lot_id) {
                    $moveLine->lot->update([
                        'location_id' => $moveLine->lot->total_quantity >= $moveLine->qty
                            ? $moveLine->destination_location_id
                            : null,
                    ]);
                }
            }
        }

        static::updateOperationState($record);

        static::applyPushRules($record);
    }

    public static function cancel(Operation $record)
    {
        foreach ($record->moves as $move) {
            $move->update([
                'state' => Enums\MoveState::CANCELED,
                'received_qty' => 0,
            ]);

            $move->lines()->delete();
        }

        static::updateOperationState($record);
    }

    public static function backOrder(Operation $record)
    {
        if (! static::canProcessBackOrder($record)) {
            return;
        }

        $operation = $record->replicate()->fill([
            'state' => Enums\OperationState::DRAFT,
            'origin' => $record->name,
            'user_id' => Auth::id(),
            'creator_id' => Auth::id(),
        ]);

        $operation->save();

        foreach ($record->moves as $move) {
            if ($move->requested_qty <= $move->received_qty) {
                continue;
            }

            $newMove = $move->replicate()->fill([
                'operation_id' => $operation->id,
                'reference'    => $operation->name,
                'state' => Enums\MoveState::DRAFT,
                'requested_qty' => $move->requested_qty - $move->received_qty,
                'requested_uom_qty' => $move->requested_qty - $move->received_qty,
            ]);

            $newMove->save();
        }

        static::checkAvailability($operation->refresh());

        $record->update(['back_order_id' => $operation->id]);
    }

    public static function updateOrCreateMoveLines(Move $record)
    {
        $lines = $record->lines()->orderBy('created_at')->get();

        $remainingQty = $record->received_qty ?? $record->requested_qty;

        $updatedLines = collect();

        $availableQuantity = 0;

        $isSupplierSource = $record->sourceLocation->type === Enums\LocationType::SUPPLIER;

        $productQuantities = collect();

        if (! $isSupplierSource) {
            $productQuantities = ProductQuantity::with(['location', 'lot', 'package'])
                ->where('product_id', $record->product_id)
                //Todo: Fix this to handle nesting
                ->whereHas('location', function (Builder $query) use ($record) {
                    $query->where('id', $record->source_location_id)
                        ->orWhere('parent_id', $record->source_location_id);
                })
                ->when(
                    $record->sourceLocation->type != Enums\LocationType::SUPPLIER
                    && $record->product->tracking == Enums\ProductTracking::LOT,
                    fn ($query) => $query->whereNotNull('lot_id')
                )
                ->get();
        }

        foreach ($lines as $line) {
            $currentLocationQty = null;

            if (! $isSupplierSource) {
                $currentLocationQty = $productQuantities
                    ->where('location_id', $line->source_location_id)
                    ->where('lot_id', $line->lot_id)
                    ->where('package_id', $line->package_id)
                    ->first()?->quantity ?? 0;

                if ($currentLocationQty <= 0) {
                    $line->delete();

                    continue;
                }
            }

            if ($remainingQty > 0) {
                $newQty = $isSupplierSource
                    ? $newQty = min($line->qty, $remainingQty)
                    : min($line->qty, $currentLocationQty, $remainingQty);

                if ($newQty != $line->qty) {
                    $line->update([
                        'qty'     => $newQty,
                        'uom_qty' => $newQty,
                        'state'   => Enums\MoveState::ASSIGNED,
                    ]);
                }

                $updatedLines->push($line->source_location_id.'-'.$line->lot_id.'-'.$line->package_id);

                $remainingQty -= $newQty;

                $availableQuantity += $newQty;
            } else {
                $line->delete();
            }
        }

        if ($remainingQty > 0) {
            if ($isSupplierSource) {
                $record->lines()->create([
                    'qty'                     => $remainingQty,
                    'uom_qty'                 => $remainingQty,
                    'source_location_id'      => $record->source_location_id,
                    'state'                   => Enums\MoveState::ASSIGNED,
                    'reference'               => $record->reference,
                    'picking_description'     => $record->description_picking,
                    'is_picked'               => $record->is_picked,
                    'scheduled_at'            => $record->scheduled_at,
                    'operation_id'            => $record->operation_id,
                    'product_id'              => $record->product_id,
                    'uom_id'                  => $record->uom_id,
                    'destination_location_id' => $record->destination_location_id,
                    'company_id'              => $record->company_id,
                    'creator_id'              => Auth::id(),
                ]);

                $availableQuantity += $remainingQty;
            } else {
                foreach ($productQuantities as $productQuantity) {
                    if ($remainingQty <= 0) {
                        break;
                    }

                    if ($updatedLines->contains($productQuantity->location_id.'-'.$productQuantity->lot_id.'-'.$productQuantity->package_id)) {
                        continue;
                    }

                    if ($productQuantity->quantity <= 0) {
                        continue;
                    }

                    $newQty = min($productQuantity->quantity, $remainingQty);

                    $availableQuantity += $newQty;

                    $record->lines()->create([
                        'qty'                     => $newQty,
                        'uom_qty'                 => $newQty,
                        'lot_name'                => $productQuantity->lot?->name,
                        'lot_id'                  => $productQuantity->lot_id,
                        'package_id'              => $productQuantity->package_id,
                        'result_package_id'       => $newQty == $productQuantity->quantity ? $productQuantity->package_id : null,
                        'source_location_id'      => $productQuantity->location_id,
                        'state'                   => Enums\MoveState::ASSIGNED,
                        'reference'               => $record->reference,
                        'picking_description'     => $record->description_picking,
                        'is_picked'               => $record->is_picked,
                        'scheduled_at'            => $record->scheduled_at,
                        'operation_id'            => $record->operation_id,
                        'product_id'              => $record->product_id,
                        'uom_id'                  => $record->uom_id,
                        'destination_location_id' => $record->destination_location_id,
                        'company_id'              => $record->company_id,
                        'creator_id'              => Auth::id(),
                    ]);

                    $remainingQty -= $newQty;
                }
            }
        }

        $requestedQty = $record->requested_qty;

        if ($availableQuantity <= 0) {
            $record->update([
                'state'        => Enums\MoveState::CONFIRMED,
                'received_qty' => NULL,
            ]);

            $record->lines()->update([
                'state' => Enums\MoveState::CONFIRMED,
            ]);
        } elseif ($availableQuantity < $requestedQty) {
            $record->update([
                'state'        => Enums\MoveState::PARTIALLY_ASSIGNED,
                'received_qty' => $availableQuantity,
            ]);

            $record->lines()->update([
                'state' => Enums\MoveState::PARTIALLY_ASSIGNED,
            ]);
        } else {
            $record->update([
                'state'        => Enums\MoveState::ASSIGNED,
                'received_qty' => $availableQuantity,
            ]);
        }

        return $record;
    }

    private static function updateOperationState(Operation $record)
    {
        $record->refresh();

        if (in_array($record->state, [Enums\OperationState::DONE, Enums\OperationState::CANCELED])) {
            return;
        }

        if ($record->moves->every(fn ($move) => $move->state === Enums\MoveState::CONFIRMED)) {
            $record->update(['state' => Enums\OperationState::CONFIRMED]);
        } elseif ($record->moves->every(fn ($move) => $move->state === Enums\MoveState::DONE)) {
            $record->update(['state' => Enums\OperationState::DONE]);
        } elseif ($record->moves->every(fn ($move) => $move->state === Enums\MoveState::CANCELED)) {
            $record->update(['state' => Enums\OperationState::CANCELED]);
        } elseif ($record->moves->contains(fn ($move) => $move->state === Enums\MoveState::ASSIGNED ||
            $move->state === Enums\MoveState::PARTIALLY_ASSIGNED
        )) {
            $record->update(['state' => Enums\OperationState::ASSIGNED]);
        }
    }

    public static function canProcessBackOrder(Operation $record): bool
    {
        if ($record->operationType->create_backorder == Enums\CreateBackorder::NEVER) {
            return false;
        }

        return $record->moves->sum('requested_qty') > $record->moves->sum('received_qty');
    }

    public static function applyPushRules(Operation $record)
    {
        $rules = [];

        foreach ($record->moves as $move) {
            $rule = static::getPushRule($move);

            if (! $rule) {
                continue;
            }

            if (! isset($rules[$rule->id])) {
                $rules[$rule->id] = [
                    'rule' => $rule,
                    'moves' => [static::runPushRule($rule, $move)],
                ];

                continue;
            }

            $rules[$rule->id]['moves'][] = static::runPushRule($rule, $move);
        }

        foreach ($rules as $rule) {
            $operation = Operation::create([
                'state'                   => Enums\OperationState::DRAFT,
                'origin'                  => $record->name,
                'operation_type_id'       => $rule['rule']->operation_type_id,
                'source_location_id'      => $rule['rule']->source_location_id,
                'destination_location_id' => $rule['rule']->destination_location_id,
                'scheduled_at'            => now()->addDays($rule['rule']->delay),
                'company_id'              => $rule['rule']->company_id,
                'user_id'                 => Auth::id(),
                'creator_id'              => Auth::id(),
            ]);

            foreach ($rule['moves'] as $move) {
                $move->update([
                    'operation_id' => $operation->id,
                    'reference'    => $operation->name,
                ]);
            }

            static::checkAvailability($operation->refresh());
        }
    }

    public static function getPushRule(Move $move, array $filters = [])
    {
        $foundRule = null;

        $location = $move->destinationLocation;

        $filters['action'] = [Enums\RuleAction::PUSH, Enums\RuleAction::PULL_PUSH];

        while (! $foundRule && $location) {
            $filters['source_location_id'] = $location->id;

            $foundRule = static::searchPushRule(
                $move->productPackaging,
                $move->product,
                $move->warehouse,
                $filters
            );

            $location = $location->parent;
        }

        return $foundRule;
    }

    public static function runPushRule(Rule $rule, Move $move)
    {
        if ($rule->auto != Enums\RuleAuto::MANUAL) {
            return;
        }

        $newMove = $move->replicate()->fill([
            'state' => Enums\MoveState::DRAFT,
            'reference' => null,
            'requested_qty' => $move->received_qty,
            'requested_uom_qty' => $move->received_qty,
            'origin' => $move->origin ?? $move->operation->name ?? '/',
            'operation_id' => null,
            'source_location_id' => $move->destination_location_id,
            'destination_location_id' => $rule->destination_location_id,
            'final_location_id' => $move->final_location_id,
            'rule_id' => $rule->id,
            'scheduled_at' => $move->scheduled_at->addDays($rule->delay),
            'company_id' => $rule->company_id,
            'operation_type_id' => $rule->operation_type_id,
            'propagate_cancel' => $rule->propagate_cancel,
            'warehouse_id' => $rule->warehouse_id,
            'procure_method' => Enums\ProcureMethod::MAKE_TO_ORDER,
        ]);

        $newMove->save();

        if ($newMove->shouldBypassReservation()) {
            $newMove->update([
                'procure_method' => Enums\ProcureMethod::MAKE_TO_STOCK,
            ]);
        }

        if (! $newMove->sourceLocation->shouldBypassReservation()) {
            $move->moveDestinations()->attach($newMove->id);
        }

        return $newMove;
    }

    public static function searchPushRule($productPackaging, $product, $warehouse, array $filters)
    {
        if ($warehouse) {
            $filters['warehouse_id'] = $warehouse->id;
        }

        $routeSources = [
            [$productPackaging, 'routes'],
            [$product, 'routes'],
            [$product?->category, 'routes'],
            [$warehouse, 'routes']
        ];

        foreach ($routeSources as [$source, $relationName]) {
            if (! $source || ! $source->{$relationName}) {
                continue;
            }

            $routeIds = $source->{$relationName}->pluck('id');
            
            if ($routeIds->isEmpty()) {
                continue;
            }

            $foundRule = Rule::whereIn('route_id', $routeIds)
                ->where($filters)
                ->orderBy('route_sort', 'asc')
                ->orderBy('sort', 'asc')
                ->first();

            if (! $foundRule) {
                continue;
            }

            return $foundRule;
        }

        return null;
    }
}