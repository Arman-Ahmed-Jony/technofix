<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            HistoryAction::make(),
            DeleteAction::make()->modalDescription('Their account is removed. To keep their name on past orders and history, switch "Can sign in" off instead.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
