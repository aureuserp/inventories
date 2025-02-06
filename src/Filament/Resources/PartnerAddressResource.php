<?php

namespace Webkul\Inventory\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Partner\Enums\AddressType;
use Webkul\Partner\Models\Partner;

class PartnerAddressResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Radio::make('type')
                ->hiddenLabel()
                ->options([
                    AddressType::INVOICE->value  => __('inventories::filament/resources/partner-address.form.address-type.invoice'),
                    AddressType::DELIVERY->value => __('inventories::filament/resources/partner-address.form.address-type.delivery'),
                    AddressType::OTHER->value    => __('inventories::filament/resources/partner-address.form.address-type.other'),
                ])
                ->default(AddressType::INVOICE->value)
                ->inline()
                ->columnSpan(2),
            Forms\Components\Select::make('partner_id')
                ->label(__('inventories::filament/resources/partner-address.form.partner'))
                ->relationship('partner', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->columnSpan(2)
                ->createOptionForm(fn (Form $form): Form => PartnerResource::form($form)),
            Forms\Components\TextInput::make('name')
                ->label(__('inventories::filament/resources/partner-address.form.name'))
                ->required(),
            Forms\Components\TextInput::make('email')
                ->label(__('inventories::filament/resources/partner-address.form.email'))
                ->email(),
            Forms\Components\TextInput::make('phone')
                ->label(__('inventories::filament/resources/partner-address.form.phone'))
                ->tel(),
            Forms\Components\Select::make('country_id')
                ->label(__('inventories::filament/resources/partner-address.form.country'))
                ->relationship(name: 'country', titleAttribute: 'name')
                ->afterStateUpdated(fn (Forms\Set $set) => $set('state_id', null))
                ->searchable()
                ->preload()
                ->live(),
            Forms\Components\Select::make('state_id')
                ->label(__('inventories::filament/resources/partner-address.form.state'))
                ->relationship(
                    name: 'state',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Forms\Get $get, Builder $query) => $query->where('country_id', $get('country_id')),
                )
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('street1')
                ->label(__('inventories::filament/resources/partner-address.form.street1')),
            Forms\Components\TextInput::make('street2')
                ->label(__('inventories::filament/resources/partner-address.form.street2')),
            Forms\Components\TextInput::make('city')
                ->label(__('inventories::filament/resources/partner-address.form.city')),
            Forms\Components\TextInput::make('zip')
                ->label(__('inventories::filament/resources/partner-address.form.zip')),
            Forms\Components\Hidden::make('creator_id')
                ->default(Auth::user()->id),
        ])
            ->columns(2);
    }
}
