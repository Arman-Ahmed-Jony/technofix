<?php

namespace App\Filament\Resources\PcBuilderSlots\Pages;

use App\Filament\Pages\ManagePcBuilder;
use App\Filament\Resources\PcBuilderSlots\PcBuilderSlotResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPcBuilderSlots extends ListRecords
{
    protected static string $resource = PcBuilderSlotResource::class;

    protected ?string $heading = 'PC Builder slots';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('rules')
                ->label('Compatibility rules')
                ->icon('heroicon-o-shield-check')
                ->color('gray')
                ->url(ManagePcBuilder::getUrl()),
            Action::make('open')
                ->label('View builder')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(url('pc-builder'), shouldOpenInNewTab: true),
            CreateAction::make()->label('New slot'),
        ];
    }
}
