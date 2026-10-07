<?php

namespace App\Filament\Resources\PcBuilderSlots\Pages;

use App\Filament\Resources\PcBuilderSlots\PcBuilderSlotResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPcBuilderSlot extends EditRecord
{
    protected static string $resource = PcBuilderSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription('Builds that used this slot simply lose that part. To hide it for now, turn “Show on the builder” off instead.'),
        ];
    }
}
