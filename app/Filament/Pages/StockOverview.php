<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Support\ActivityLog;
use App\Support\StockValuation;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/** What the stock is worth, by category, plus what needs attention (low, negative, no cost price). */
class StockOverview extends Page
{
    protected string $view = 'filament.pages.stock-overview';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?string $navigationLabel = 'Stock overview';

    protected static ?string $title = 'Stock overview';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'stock';

    public static function canAccess(): bool
    {
        return StaffArea::Inventory->allowed();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label('Download Excel')->icon('heroicon-o-arrow-down-tray')->color('gray')->action(fn () => $this->export()),
        ];
    }

    public function export(): BinaryFileResponse
    {
        $path = (new StockValuation)->export(tempnam(sys_get_temp_dir(), 'stock').'.xlsx');
        ActivityLog::record('exported', label: 'Stock list', type: 'settings');

        return response()->download($path, 'technofix-stock-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    protected function getViewData(): array
    {
        $valuation = new StockValuation;

        return [
            'summary' => $valuation->summary(),
            'categories' => $valuation->byCategory(),
            'low' => $valuation->low()->limit(15)->get(['id', 'name', 'sku', 'stock_qty', 'category_id']),
            'negative' => $valuation->rows()->where('qty', '<', 0)->take(15),
            'missingCost' => $valuation->rows()->where('qty', '>', 0)->whereNull('cost')->take(15),
        ];
    }
}
