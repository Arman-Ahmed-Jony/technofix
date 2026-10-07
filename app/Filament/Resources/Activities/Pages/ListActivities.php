<?php

namespace App\Filament\Resources\Activities\Pages;

use App\Filament\Resources\Activities\ActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected static ?string $title = 'Activity log';

    public function getSubheading(): ?string
    {
        return 'Who changed what in the admin — prices, stock, orders, settings, staff — and when. Kept for a year. Stock movements caused by orders are on each product’s Stock history.';
    }
}
