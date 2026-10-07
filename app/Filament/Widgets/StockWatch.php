<?php

namespace App\Filament\Widgets;

use App\Enums\ProductStatus;
use App\Enums\StaffArea;
use App\Enums\StockStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Settings\OrderSettings;
use Filament\Widgets\Widget;

/** Published "In stock" products whose quantity is running out (orders count stock down). */
class StockWatch extends Widget
{
    /** "Low stock" = this many or fewer (Admin → Settings → Stock & orders). */
    public static function low(): int
    {
        return app(OrderSettings::class)->low_stock_at;
    }

    protected string $view = 'filament.widgets.stock-watch';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $query = Product::query()
            ->where('status', ProductStatus::Published)
            ->where('stock_status', StockStatus::InStock)
            ->where('stock_qty', '<=', self::low());

        return [
            'count' => (clone $query)->count(),
            'products' => $query->orderBy('stock_qty')->orderBy('name')->limit(5)->get(['id', 'name', 'stock_qty', 'category_id'])->map(fn (Product $product) => [
                'name' => $product->name,
                'qty' => (int) $product->stock_qty,
                'url' => ProductResource::getUrl('edit', ['record' => $product]),
            ]),
            'outOfStock' => Product::query()->where('status', ProductStatus::Published)->where('stock_status', StockStatus::OutOfStock)->count(),
            'allUrl' => ProductResource::getUrl('index'),
        ];
    }

    public static function canView(): bool
    {
        return StaffArea::Catalog->allowed();
    }
}
