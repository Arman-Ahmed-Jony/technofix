<?php

namespace App\Filament\Resources\HomeSections\Pages;

use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\HomeSections\HomeSectionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeSections extends ListRecords
{
    protected static string $resource = HomeSectionResource::class;

    protected ?string $heading = 'Home page';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('banners')->label('Hero slides')->icon('heroicon-o-photo')->color('gray')->url(BannerResource::getUrl()),
            Action::make('open')->label('View home page')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(url('/'), shouldOpenInNewTab: true),
            CreateAction::make()->label('Add section'),
        ];
    }
}
