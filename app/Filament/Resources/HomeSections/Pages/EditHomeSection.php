<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\HomeSections\HomeSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHomeSection extends EditRecord
{
    protected static string $resource = HomeSectionResource::class;

    public function getTitle(): string
    {
        return 'Edit: '.$this->getRecord()->typeLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->modalDescription('The section disappears from the home page. To hide it for now, switch it off instead.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
