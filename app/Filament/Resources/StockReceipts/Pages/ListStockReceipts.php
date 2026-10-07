<?php

namespace App\Filament\Resources\StockReceipts\Pages;

use App\Filament\Resources\StockReceipts\StockReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockReceipts extends ListRecords
{
    protected static string $resource = StockReceiptResource::class;

    public function getSubheading(): ?string
    {
        return 'Goods arriving from suppliers. Saving one adds the quantities to stock and keeps cost prices up to date.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Receive stock')->icon('heroicon-o-plus')];
    }
}
