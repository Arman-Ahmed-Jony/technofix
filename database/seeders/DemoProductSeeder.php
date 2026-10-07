<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Demo products (database/data/demo-products.php) so the storefront has something to show until the
 * owner adds the real catalog. Run after CatalogSeeder. Not run in production by DatabaseSeeder;
 * existing products (matched by slug) are left alone.
 */
class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'slug');
        $brands = Brand::pluck('id', 'slug');
        $brandNames = Brand::pluck('name', 'slug');
        $system = Attribute::where('is_system', true)->get()->keyBy('code')->all();

        foreach (require database_path('data/demo-products.php') as $index => $row) {
            $slug = Str::slug($row['name']);

            if (! isset($categories[$row['category']])) {
                continue;
            }

            $toSpecs = fn (array $fields) => collect($fields)->mapWithKeys(function (string $value, string $name) use ($row, $system) {
                $attribute = CatalogSeeder::attributeFor($row['category'], $name, $system);

                return [$attribute->id => [$attribute, Attribute::normalise($value, $attribute->unit)]];
            });
            $builder = $toSpecs($row['builder'] ?? []);

            // Existing demo products only get PC Builder fields they don't have yet.
            if ($existing = Product::withTrashed()->where('slug', $slug)->first()) {
                $this->addMissingSpecs($existing, $builder);

                continue;
            }

            $flags = $row['flags'] ?? [];
            $stock = StockStatus::from($row['stock'] ?? StockStatus::InStock->value);
            $specs = $toSpecs($row['filters'] ?? [])->union($builder);
            $templateNames = $specs->map(fn (array $spec) => $spec[0]->name)->merge(array_keys($row['filters'] ?? []))->all();

            $product = new Product([
                'name' => $row['name'],
                'slug' => $slug,
                'sku' => 'TF-'.str_pad((string) (1001 + $index), 5, '0', STR_PAD_LEFT),
                'model' => $row['model'] ?? null,
                'brand_id' => $brands[$row['brand']] ?? null,
                'category_id' => $categories[$row['category']],
                'key_features' => $row['key_features'] ?? [],
                'description' => $this->description($row, $brandNames[$row['brand']] ?? Str::headline($row['brand'])),
                'extra_specs' => $this->extraSpecs($row['specs'] ?? [], $templateNames),
                'price' => $row['price'] ?? null,
                'regular_price' => $row['regular_price'] ?? null,
                'stock_status' => $stock,
                'stock_qty' => $stock === StockStatus::InStock ? 10 : 0,
                'warranty' => $row['warranty'] ?? null,
                'emi_available' => $row['emi'] ?? false,
                'is_featured' => in_array('featured', $flags, true),
                'is_new' => in_array('new', $flags, true),
                'is_best_seller' => in_array('best', $flags, true),
                'is_hot_deal' => in_array('deal', $flags, true),
                'status' => ProductStatus::Published,
            ]);

            // Newer-looking dates for "new" products, so "Newest first" sorting makes sense.
            $product->created_at = $product->updated_at = now()->subDays(in_array('new', $flags, true) ? $index % 7 : 30 + $index);
            $product->save();

            ProductSpec::insert($specs->map(fn (array $spec, int $attributeId) => [
                'product_id' => $product->id,
                'attribute_id' => $attributeId,
                'value' => $spec[1],
            ])->values()->all());
        }
    }

    /**
     * @param  Collection<int, array{0: Attribute, 1: string}>  $specs
     */
    private function addMissingSpecs(Product $product, $specs): void
    {
        $have = $product->specValues()->pluck('attribute_id')->all();

        foreach ($specs->except($have) as $attributeId => [, $value]) {
            ProductSpec::create(['product_id' => $product->id, 'attribute_id' => $attributeId, 'value' => $value]);
        }
    }

    /**
     * Detailed spec rows that aren't already template fields.
     *
     * @param  array<string, array<string, string>>  $groups
     * @param  list<string>  $templateNames
     * @return list<array{group: string, label: string, value: string}>
     */
    private function extraSpecs(array $groups, array $templateNames): array
    {
        $rows = [];

        foreach ($groups as $group => $specs) {
            foreach ($specs as $label => $value) {
                if (! in_array($label, $templateNames, true)) {
                    $rows[] = ['group' => $group, 'label' => $label, 'value' => $value];
                }
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function description(array $row, string $brand): string
    {
        $features = collect($row['key_features'] ?? [])->map(fn (string $f) => Str::lcfirst($f))->join(', ', ' and ');
        $warranty = $row['warranty'] ?? null;

        return '<p>The <strong>'.e($row['name']).'</strong> is a dependable choice from '.e($brand).'. '
            .($features ? 'It offers '.e($features).'.' : '').'</p>'
            .'<p>Every '.e($brand).' product at TechnoFix is 100% genuine and comes with official warranty support'
            .($warranty ? ' ('.e($warranty).')' : '').'. Order online with cash on delivery anywhere in Bangladesh, '
            .'or visit our store for expert advice.</p>';
    }
}
