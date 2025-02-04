<?php

namespace Webkul\Inventory\Filament\Clusters\Configurations\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Inventory\Filament\Clusters\Configurations;
use Webkul\Inventory\Filament\Clusters\Configurations\Resources\ProductCategoryResource\Pages;
use Webkul\Inventory\Models\Category;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?int $navigationSort = 8;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return __('inventories::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('inventories::filament/clusters/configurations/resources/product-category.navigation.title');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.general.title'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.general.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus()
                                    ->placeholder(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.general.fields.name-placeholder'))
                                    ->extraInputAttributes(['style' => 'font-size: 1.5rem;height: 3rem;'])
                                    ->unique(ignoreRecord: true),
                                Forms\Components\Select::make('parent_id')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.general.fields.parent'))
                                    ->relationship('parent', 'full_name')
                                    ->searchable()
                                    ->preload(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.settings.title'))
                            ->schema([
                                Forms\Components\Fieldset::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.settings.fieldsets.logistics.title'))
                                    ->schema([
                                        Forms\Components\Select::make('warehouses')
                                            ->label(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.settings.fieldsets.logistics.fields.routes'))
                                            ->relationship('routes', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->multiple(),
                                    ])
                                    ->columns(1),

                                // Forms\Components\Fieldset::make(__('inventories::filament/clusters/configurations/resources/product-category.form.sections.settings.fieldsets.inventory-valuation.title'))
                                //     ->schema([
                                //     ]),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.full-name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent_path')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.parent-path'))
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.parent'))
                    ->placeholder('—')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.creator'))
                    ->placeholder('—')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                Tables\Grouping\Group::make('parent.full_name')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.groups.parent'))
                    ->collapsible(),
                Tables\Grouping\Group::make('created_at')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.groups.created-at'))
                    ->collapsible(),
                Tables\Grouping\Group::make('updated_at')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.groups.updated-at'))
                    ->date()
                    ->collapsible(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.table.filters.parent'))
                    ->relationship('parent', 'full_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/configurations/resources/product-category.table.actions.delete.notification.title'))
                            ->body(__('inventories::filament/clusters/configurations/resources/product-category.table.actions.delete.notification.body')),
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('inventories::filament/clusters/configurations/resources/product-category.table.bulk-actions.delete.notification.title'))
                            ->body(__('inventories::filament/clusters/configurations/resources/product-category.table.bulk-actions.delete.notification.body')),
                    ),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.general.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.general.entries.name'))
                                    ->weight(FontWeight::Bold)
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->icon('heroicon-o-document-text'),

                                Infolists\Components\TextEntry::make('parent.name')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.general.entries.parent'))
                                    ->icon('heroicon-o-folder')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('full_name')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.general.entries.full_name'))
                                    ->icon('heroicon-o-folder-open')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('parent_path')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.general.entries.parent_path'))
                                    ->icon('heroicon-o-arrows-right-left')
                                    ->placeholder('—'),
                            ]),

                        Infolists\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.settings.title'))
                            ->schema([
                                Infolists\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.settings.subsections.logistics.title'))
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('routes')
                                            ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.settings.subsections.logistics.entries.routes'))
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.settings.subsections.logistics.entries.route_name'))
                                                    ->icon('heroicon-o-truck'),
                                            ])
                                            ->columns(1),
                                    ])
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->collapsible(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()
                    ->schema([
                        Infolists\Components\Section::make(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.record-information.title'))
                            ->schema([
                                Infolists\Components\TextEntry::make('creator.name')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.record-information.entries.creator'))
                                    ->icon('heroicon-o-user')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.record-information.entries.created_at'))
                                    ->dateTime()
                                    ->icon('heroicon-o-calendar')
                                    ->placeholder('—'),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('inventories::filament/clusters/configurations/resources/product-category.infolist.sections.record-information.entries.updated_at'))
                                    ->dateTime()
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('—'),
                            ])
                            ->icon('heroicon-o-information-circle')
                            ->collapsible(),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        $currentRoute = request()->route()?->getName();

        if ($currentRoute === self::getRouteBaseName().'.index') {
            return SubNavigationPosition::Start;
        }

        return SubNavigationPosition::Top;
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewProductCategory::class,
            Pages\EditProductCategory::class,
            Pages\ManageProducts::class,
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
            'index'    => Pages\ListProductCategories::route('/'),
            'create'   => Pages\CreateProductCategory::route('/create'),
            'view'     => Pages\ViewProductCategory::route('/{record}'),
            'edit'     => Pages\EditProductCategory::route('/{record}/edit'),
            'products' => Pages\ManageProducts::route('/{record}/products'),
        ];
    }
}
