<?php

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    public function getSubheading(): ?string
    {
        return 'Every change to a product’s quantity: orders, cancellations, stock-ins and staff adjustments (with their reason).';
    }
}
