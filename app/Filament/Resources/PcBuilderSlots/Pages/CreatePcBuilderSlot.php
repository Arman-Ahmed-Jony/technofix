<?php

namespace App\Filament\Resources\PcBuilderSlots\Pages;

use App\Filament\Resources\PcBuilderSlots\PcBuilderSlotResource;
use App\Models\PcBuilderSlot;
use Filament\Resources\Pages\CreateRecord;

class CreatePcBuilderSlot extends CreateRecord
{
    protected static string $resource = PcBuilderSlotResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'sort' => (int) PcBuilderSlot::max('sort') + 1];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
