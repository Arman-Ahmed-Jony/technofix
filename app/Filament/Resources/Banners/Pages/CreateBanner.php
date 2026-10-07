<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Models\Banner;
use Filament\Resources\Pages\CreateRecord;

class CreateBanner extends CreateRecord
{
    protected static string $resource = BannerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'sort' => (int) Banner::where('placement', $data['placement'])->max('sort') + 1];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', ['tab' => $this->getRecord()->placement]);
    }
}
