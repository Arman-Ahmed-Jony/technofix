<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Settings\OrderSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * What the stock on hand is worth (Admin → Inventory → Stock overview): at cost price and at today's selling price,
 * by category, plus low / negative stock and products without a cost price. Only positive quantities count.
 */
class StockValuation
{
    private ?Collection $rows = null;

    /**
     * @return Collection<int, array{id: int, name: string, sku: ?string, category: string, brand: string, qty: int, cost: ?int, price: ?int, cost_value: ?int, retail_value: int, published: bool}>
     */
    public function rows(): Collection
    {
        return $this->rows ??= Product::query()
            ->where('stock_qty', '!=', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'category_id', 'brand_id', 'stock_qty', 'cost_price', 'price', 'status'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category_name ?: '—',
                'brand' => $p->brand_name ?: '—',
                'qty' => (int) $p->stock_qty,
                'cost' => $p->cost_price ? (int) $p->cost_price : null,
                'price' => $p->price ? (int) $p->price : null,
                'cost_value' => $p->cost_price ? max(0, (int) $p->stock_qty) * (int) $p->cost_price : null,
                'retail_value' => max(0, (int) $p->stock_qty) * (int) $p->price,
                'published' => $p->status === ProductStatus::Published,
            ]);
    }

    /**
     * @return array{products: int, units: int, cost_value: int, retail_value: int, missing_cost: int, low: int, negative: int}
     */
    public function summary(): array
    {
        $inStock = $this->rows()->where('qty', '>', 0);

        return [
            'products' => $inStock->count(),
            'units' => $inStock->sum('qty'),
            'cost_value' => (int) $inStock->sum('cost_value'),
            'retail_value' => (int) $inStock->sum('retail_value'),
            'missing_cost' => $inStock->whereNull('cost')->count(),
            'low' => $this->low()->count(),
            'negative' => $this->rows()->where('qty', '<', 0)->count(),
        ];
    }

    /**
     * @return Collection<int, array{name: string, products: int, units: int, cost_value: int, retail_value: int}>
     */
    public function byCategory(): Collection
    {
        return $this->rows()->where('qty', '>', 0)->groupBy('category')
            ->map(fn (Collection $rows, string $name) => [
                'name' => $name,
                'products' => $rows->count(),
                'units' => $rows->sum('qty'),
                'cost_value' => (int) $rows->sum('cost_value'),
                'retail_value' => (int) $rows->sum('retail_value'),
            ])
            ->sortByDesc('retail_value')
            ->values();
    }

    /** Published products running low (in stock, at or under the "low stock" setting). */
    public function low(): Builder
    {
        return Product::query()->published()->where('stock_status', 'in_stock')->where('stock_qty', '<=', app(OrderSettings::class)->low_stock_at)->orderBy('stock_qty')->orderBy('name');
    }

    public function export(string $path): string
    {
        $options = new Options;
        $options->setColumnWidthForRange(48, 1, 1);
        $options->setColumnWidthForRange(16, 2, 10);
        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Stock')->setSheetView((new SheetView)->setFreezeRow(2));
        $writer->addRow(Row::fromValues(['Product', 'Code', 'Category', 'Brand', 'Qty', 'Cost price (BDT)', 'Value at cost (BDT)', 'Price (BDT)', 'Value at price (BDT)'],
            (new Style)->setFontBold()->setBackgroundColor('DCEBF7')));

        foreach ($this->rows() as $r) {
            $writer->addRow(Row::fromValues([$r['name'], $r['sku'], $r['category'], $r['brand'], $r['qty'], $r['cost'], $r['cost_value'], $r['price'], $r['retail_value']]));
        }

        $s = $this->summary();
        $writer->addRow(Row::fromValues(['Total', null, null, null, $s['units'], null, $s['cost_value'], null, $s['retail_value']], (new Style)->setFontBold()));
        $writer->close();

        return $path;
    }
}
