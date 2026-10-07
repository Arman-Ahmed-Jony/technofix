<?php

namespace App\Filament\Resources\StockReceipts\Pages;

use App\Filament\Actions\HistoryAction;
use App\Filament\Resources\StockReceipts\StockReceiptResource;
use App\Models\StockReceipt;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockReceipt extends ViewRecord
{
    protected static string $resource = StockReceiptResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Stock-in '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            HistoryAction::make(),
            Action::make('cancel')
                ->label('Cancel stock-in')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (StockReceipt $record) => ! $record->isCancelled())
                ->requiresConfirmation()
                ->modalDescription('Entered by mistake? Its quantities are taken back out of stock. Cost prices stay as they are — fix them on the products if needed.')
                ->schema([TextInput::make('note')->label('Why (optional)')->maxLength(200)])
                ->action(function (StockReceipt $record, array $data) {
                    app(InventoryService::class)->cancel($record, $data['note'] ?? null);
                    Notification::make()->success()->title('Stock-in cancelled')->body('The quantities were taken back out of stock.')->send();
                    $this->refreshFormData(['cancelled_at']);
                }),
        ];
    }
}
