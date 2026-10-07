<?php

namespace App\Filament\Widgets;

use App\Enums\StaffArea;
use App\Filament\Resources\Products\ProductResource;
use App\Models\OrderItem;
use Filament\Widgets\Widget;

/** Best sellers of the last 30 days by units (cancelled / returned orders not counted). */
class TopProducts extends Widget
{
    protected string $view = 'filament.widgets.top-products';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $rows = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->counted()->where('created_at', '>=', now()->subDays(30)))
            ->selectRaw('product_id, MAX(name) as name, SUM(qty) as units, SUM(total) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('units')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return [
            'rows' => $rows->map(fn ($row) => [
                'name' => $row->name,
                'units' => (int) $row->units,
                'revenue' => (int) $row->revenue,
                'url' => $row->product_id ? ProductResource::getUrl('edit', ['record' => $row->product_id]) : null,
            ]),
            'max' => max(1, (int) $rows->max('units')),
        ];
    }

    public static function canView(): bool
    {
        return StaffArea::Reports->allowed();
    }
}
