<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\ScrapResource;
use Webkul\Inventory\Models\Product;

class CreateScrap extends CreateRecord
{
    protected static string $resource = ScrapResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('inventories::filament/clusters/operations/resources/scrap/pages/create-scrap.title');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uom_id'] ??= Product::find($data['product_id'])->uom_id;

        $data['state'] ??= Enums\ScrapState::DRAFT;

        $data['creator_id'] = Auth::id();

        $data['company_id'] ??= Auth::user()->default_company_id;

        return $data;
    }
}
