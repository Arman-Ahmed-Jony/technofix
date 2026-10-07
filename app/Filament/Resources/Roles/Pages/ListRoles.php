<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    protected static string $resource = RoleResource::class;

    public function getSubheading(): ?string
    {
        return 'A role is a set of admin areas. Give each staff member the role that fits their job; the Owner role can do everything and can’t be changed.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New role'),
        ];
    }
}
