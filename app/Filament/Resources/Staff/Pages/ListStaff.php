<?php

namespace App\Filament\Resources\Staff\Pages;

use App\Filament\Resources\Staff\StaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaff extends ListRecords
{
    protected static string $resource = StaffResource::class;

    public function getSubheading(): ?string
    {
        return 'People who can sign in to this admin. Each person’s role decides which parts they can open.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add staff member'),
        ];
    }
}
