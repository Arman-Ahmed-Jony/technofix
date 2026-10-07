<?php

namespace App\Filament\Resources\Banners\Pages;

use App\Filament\Resources\Banners\BannerResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')->label('View home page')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')->url(url('/'), shouldOpenInNewTab: true),
            CreateAction::make()->label('New banner'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'hero' => Tab::make('Hero slides')->modifyQueryUsing(fn (Builder $query) => $query->where('placement', 'hero')),
            'announcement' => Tab::make('Announcement bar')->modifyQueryUsing(fn (Builder $query) => $query->where('placement', 'announcement')),
        ];
    }
}
