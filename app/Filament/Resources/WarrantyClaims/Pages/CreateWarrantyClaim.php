<?php

namespace App\Filament\Resources\WarrantyClaims\Pages;

use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Services\WarrantyDesk;
use Filament\Resources\Pages\CreateRecord;

class CreateWarrantyClaim extends CreateRecord
{
    protected static string $resource = WarrantyClaimResource::class;

    protected function afterCreate(): void
    {
        // "We received your product" email (if the customer gave an email) + the first line of their timeline.
        app(WarrantyDesk::class)->record($this->record, (bool) ($this->data['notify'] ?? true), created: true);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
