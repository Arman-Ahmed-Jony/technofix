<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home: welcome + quick actions, key figures, sales chart, order pipeline, latest orders, best sellers,
 * leads, stock alerts and where orders come from (widgets in App\Filament\Widgets, in their $sort order).
 */
class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -10;

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }

    public function getHeading(): string
    {
        return ''; // the welcome banner is the page header
    }
}
