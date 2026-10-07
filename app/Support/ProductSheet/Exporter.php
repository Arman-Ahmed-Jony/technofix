<?php

namespace App\Support\ProductSheet;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Product;
use App\Support\CatalogCache;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes products to an .xlsx file in the layout the importer reads back (edit in Excel → import = bulk update).
 * Without products it is an empty template for the chosen categories.
 */
class Exporter
{
    /**
     * @param  list<int>|null  $categoryIds  limit to these categories (null = whole catalog)
     */
    public function __construct(protected ?array $categoryIds = null, protected bool $withProducts = true) {}

    /** Writes the file and returns the number of products in it. */
    public function writeTo(string $path): int
    {
        $specs = $this->specAttributes();
        $duplicateNames = $specs->countBy('name')->filter(fn (int $n) => $n > 1)->keys()->all();
        $headers = [
            ...array_map(fn (array $field) => $field[0], Columns::FIELDS),
            ...$specs->map(fn (Attribute $a) => Columns::specHeader($a, in_array($a->name, $duplicateNames, true)))->all(),
        ];

        // One width per column (overlapping ranges make Excel "repair" the file).
        $widths = array_fill(1, count($headers), 16);
        $widths[$this->column('name')] = 48;
        $widths[$this->column('category')] = $widths[$this->column('brand')] = $widths[$this->column('model')] = 24;
        $widths[$this->column('key_features')] = $widths[$this->column('description')] = 44;

        $options = new Options;
        foreach ($widths as $column => $width) {
            $options->setColumnWidthForRange($width, $column, $column);
        }

        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Products')->setSheetView((new SheetView)->setFreezeRow(2));
        $writer->addRow(Row::fromValues($headers, (new Style)->setFontBold()->setBackgroundColor('DCEBF7')));

        $count = 0;

        if ($this->withProducts) {
            $wrap = (new Style)->setShouldWrapText();
            $columnStyles = [$this->column('key_features') - 1 => $wrap, $this->column('description') - 1 => $wrap];

            $this->products()->chunkById(200, function (Collection $products) use ($writer, $specs, $columnStyles, &$count) {
                foreach ($products as $product) {
                    $writer->addRow(Row::fromValuesWithStyles($this->rowFor($product, $specs), null, $columnStyles));
                    $count++;
                }
            });
        }

        $this->writeInstructions($writer);
        $writer->close();

        return $count;
    }

    /**
     * @param  Collection<int, Attribute>  $specs
     * @return list<mixed>
     */
    protected function rowFor(Product $product, Collection $specs): array
    {
        $yes = fn (bool $value) => $value ? 'Yes' : 'No';
        $values = $product->specValues->pluck('value', 'attribute_id');

        $row = [
            'sku' => $product->sku,
            'name' => $product->name,
            'category' => self::categoryLabels()[$product->category_id] ?? '',
            'brand' => $product->brand_name,
            'model' => $product->model,
            'price' => $product->price,
            'regular_price' => $product->regular_price,
            'cost_price' => $product->cost_price,
            'stock_status' => ($product->stock_status ?? StockStatus::InStock)->label(),
            'stock_qty' => $product->stock_qty,
            'warranty' => $product->warranty,
            'status' => ($product->status ?? ProductStatus::Published)->getLabel(),
            'is_featured' => $yes($product->is_featured),
            'is_new' => $yes($product->is_new),
            'is_best_seller' => $yes($product->is_best_seller),
            'is_hot_deal' => $yes($product->is_hot_deal),
            'emi_available' => $yes($product->emi_available),
            'key_features' => implode("\n", $product->key_features ?? []),
            'description' => $product->description,
            'related' => $product->relatedProducts->pluck('sku')->join(', '),
            'accessories' => $product->accessories->pluck('sku')->join(', '),
            'colors' => Importer::colorsText($product->colors->map(fn ($c) => ['name' => $c->name, 'hex' => $c->hex, 'available' => $c->is_available])->all()),
            'slug' => $product->slug,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
        ];

        return [
            ...array_map(fn (string $field) => $row[$field], array_keys(Columns::FIELDS)), // header order
            ...$specs->map(fn (Attribute $a) => isset($values[$a->id]) ? Attribute::display($values[$a->id], $a->unit) : null)->all(),
        ];
    }

    protected function products()
    {
        $codes = fn ($query) => $query->select('products.id', 'products.sku'); // qualified: the pivot table has an id too

        return Product::query()
            ->with(['specValues', 'colors', 'relatedProducts' => $codes, 'accessories' => $codes])
            ->when($this->categoryIds !== null, fn ($q) => $q->whereIn('category_id', $this->categoryIds ?: [0]));
    }

    /**
     * Spec fields of the categories in scope, in category-tree and template order.
     *
     * @return Collection<int, Attribute>
     */
    protected function specAttributes(): Collection
    {
        $data = CatalogCache::data();
        $ids = collect($data['categories'])
            ->keys()
            ->when($this->categoryIds !== null, fn ($keys) => $keys->intersect($this->categoryIds))
            ->flatMap(fn (int $categoryId) => array_column($data['templates'][$categoryId] ?? [], 'attribute_id'))
            ->unique()
            ->values();

        $attributes = Attribute::whereKey($ids)->get()->keyBy('id');

        return $ids->map(fn (int $id) => $attributes[$id] ?? null)->filter()->values();
    }

    /**
     * A category name for the spreadsheet: just the name, or "Parent > Name" when the name isn't unique.
     *
     * @return array<int, string>
     */
    public static function categoryLabels(): array
    {
        return once(function () {
            $categories = CatalogCache::data()['categories'];
            $counts = collect($categories)->countBy(fn ($c) => mb_strtolower($c['name']));

            return collect($categories)->map(function (array $c) use ($categories, $counts) {
                if ($counts[mb_strtolower($c['name'])] === 1 || ! $c['parent_id']) {
                    return $c['name'];
                }

                return ($categories[$c['parent_id']]['name'] ?? '').' > '.$c['name'];
            })->all();
        });
    }

    /** 1-based column number of a fixed field. */
    protected function column(string $field): int
    {
        return array_search($field, array_keys(Columns::FIELDS), true) + 1;
    }

    protected function writeInstructions(Writer $writer): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent()->setName('How to use');
        $sheet->setColumnWidth(24, 1);
        $sheet->setColumnWidth(110, 2);
        $bold = (new Style)->setFontBold();

        $writer->addRow(Row::fromValues(['TechnoFix product sheet'], (new Style)->setFontBold()->setFontSize(14)));
        foreach ([
            'Fill in or edit the "Products" sheet, save, then upload it in Admin → Catalog → Products → Import.',
            'Rows are matched to existing products by Product code (or by exact name when the code is empty). Other rows become new products.',
            'Empty cells never change an existing product — only filled cells are updated.',
            'Keep the header row. Columns can be in any order; you can delete columns you don\'t need.',
            'Nothing is saved until you check the preview and press Import.',
        ] as $line) {
            $writer->addRow(Row::fromValues(['', $line]));
        }

        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues(['Column', 'What to enter'], $bold));
        foreach (Columns::FIELDS as [$header, , $help]) {
            $writer->addRow(Row::fromValues([$header, $help]));
        }
        $writer->addRow(Row::fromValues([
            Columns::SPEC_PREFIX.'…',
            'Specification values, e.g. "Spec: Chipset" = Intel B760. Only fields that belong to the product\'s category are used (see Admin → Catalog → Categories). Units are optional: "650" or "650W".',
        ]));
    }
}
