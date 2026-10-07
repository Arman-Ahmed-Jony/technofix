<?php

namespace App\Filament\Actions;

use App\Enums\StaffArea;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

/**
 * "Adjust stock": add, remove or set a counted quantity, always with a reason — written to the stock ledger.
 */
class AdjustStockAction
{
    public static function make(): Action
    {
        return Action::make('adjustStock')
            ->label('Adjust stock')
            ->icon('heroicon-o-adjustments-vertical')
            ->color('gray')
            ->visible(fn () => StaffArea::Inventory->allowed())
            ->modalHeading(fn (Product $record) => 'Adjust stock — '.$record->name)
            ->modalDescription(fn (Product $record) => 'In stock now: '.(int) $record->stock_qty.'. New goods from a supplier go in Inventory → Stock-in instead (it keeps cost prices right).')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Save adjustment')
            ->schema([
                ToggleButtons::make('mode')
                    ->label('What happened')
                    ->options(['remove' => 'Take out', 'add' => 'Add', 'set' => 'Counted — set quantity'])
                    ->icons(['remove' => 'heroicon-m-minus', 'add' => 'heroicon-m-plus', 'set' => 'heroicon-m-clipboard-document-check'])
                    ->default('remove')
                    ->inline()
                    ->live()
                    ->required(),
                TextInput::make('qty')
                    ->label(fn (Get $get) => $get('mode') === 'set' ? 'Quantity counted' : 'How many')
                    ->integer()
                    ->minValue(fn (Get $get) => $get('mode') === 'set' ? 0 : 1)
                    ->maxValue(100000)
                    ->required(),
                Select::make('reason')
                    ->options(StockMovement::REASONS)
                    ->default(fn (Get $get) => $get('mode') === 'set' ? 'count' : 'damaged')
                    ->selectablePlaceholder(false)
                    ->required(),
                TextInput::make('note')->maxLength(200)->placeholder('Optional — e.g. box crushed in delivery'),
            ])
            ->action(function (Product $record, array $data) {
                $change = app(InventoryService::class)->adjust($record, $data['mode'], (int) $data['qty'], $data['reason'], $data['note'] ?? null);
                $record->refresh();

                Notification::make()->success()
                    ->title($change === 0 ? 'No change — the count matches' : 'Stock '.($change > 0 ? '+' : '').$change)
                    ->body('In stock now: '.(int) $record->stock_qty)
                    ->send();
            });
    }
}
