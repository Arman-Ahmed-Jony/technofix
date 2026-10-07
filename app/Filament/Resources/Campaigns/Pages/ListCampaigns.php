<?php

namespace App\Filament\Resources\Campaigns\Pages;

use App\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCampaigns extends ListRecords
{
    protected static string $resource = CampaignResource::class;

    protected ?string $heading = 'Flash sales & Happy Hour';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')->label('View offers page')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(route('offers'), shouldOpenInNewTab: true),
            CreateAction::make()->label('New flash sale'),
        ];
    }
}
