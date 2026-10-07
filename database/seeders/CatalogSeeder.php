<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Support\CatalogCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catalog structure: brands, the category tree (database/data/catalog-map.php), spec fields and each
 * category's spec template. Templates start from the fields the demo products use, plus the PC Builder
 * system fields. Safe to re-run: existing records are kept as they are (matched by slug / code).
 */
class CatalogSeeder extends Seeder
{
    /** Brands shown in the home page "Official brands" strip, in order. */
    private const FEATURED_BRANDS = [
        'intel', 'asus', 'gigabyte', 'hp', 'dell', 'lenovo', 'samsung', 'hikvision',
        'dahua', 'tp-link', 'logitech', 'corsair', 'ezviz', 'zkteco', 'ugreen', 'vention',
    ];

    private const ZONE_THEMES = ['ugreen' => 'emerald', 'dtech' => 'violet', 'vention' => 'orange'];

    /** Demo field names that are really a system field in a given category. */
    private const FIELD_ALIASES = ['ram' => ['Type' => 'memory_type']];

    /** System fields added to PC Builder categories (values are filled in per product). */
    private const PC_BUILDER_FIELDS = [
        'processor' => ['tdp' => 'Power'],
        'graphics-card' => ['tdp' => 'Power', 'gpu_length' => 'Dimensions'],
        'casing' => ['max_gpu_length' => 'Dimensions'],
        'cpu-cooler' => ['cpu_socket' => 'Compatibility'],
    ];

    public function run(): void
    {
        $map = require database_path('data/catalog-map.php');

        $brands = $this->seedBrands($map);
        $this->seedCategories($map['categories'], null, $brands);
        $this->seedTemplates(require database_path('data/demo-products.php'));

        CatalogCache::flush();
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, int> slug => id
     */
    private function seedBrands(array $map): array
    {
        $ids = [];

        foreach ($map['brands'] as $slug => $name) {
            $featured = array_search($slug, self::FEATURED_BRANDS, true);
            $zone = $map['brand_zones'][$slug] ?? null;

            $ids[$slug] = Brand::firstOrCreate(['slug' => $slug], [
                'name' => $name,
                'is_featured' => $featured !== false,
                'is_zone' => $zone !== null,
                'zone_tagline' => $zone,
                'zone_theme' => self::ZONE_THEMES[$slug] ?? null,
                'sort' => $featured !== false ? $featured : 100,
                'is_active' => true,
            ])->id;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<string, int>  $brands
     */
    private function seedCategories(array $nodes, ?Category $parent, array $brands): void
    {
        foreach ($nodes as $sort => $node) {
            $category = Category::firstOrCreate(['slug' => $node['slug']], [
                'parent_id' => $parent?->id,
                'name' => $node['name'],
                'icon' => $node['icon'] ?? null,
                'sort' => $sort,
                'is_active' => true,
                'show_in_menu' => true,
            ]);

            $category->brands()->syncWithoutDetaching(
                collect($node['brands'] ?? [])->values()->mapWithKeys(fn (string $slug, int $i) => [$brands[$slug] => ['sort' => $i]])->all()
            );

            $this->seedCategories($node['children'] ?? [], $category, $brands);
        }
    }

    /**
     * One template per category that has demo products: its filter fields (filterable; the first three
     * also shown on product cards), grouped like the detailed spec tables, plus PC Builder fields.
     *
     * @param  list<array<string, mixed>>  $products
     */
    private function seedTemplates(array $products): void
    {
        $system = $this->seedSystemAttributes();
        $categories = Category::pluck('id', 'slug');

        foreach (collect($products)->groupBy('category') as $slug => $rows) {
            if (! isset($categories[$slug])) {
                continue;
            }

            $sort = 0;
            $names = $rows->flatMap(fn ($row) => array_keys($row['filters'] ?? []))->unique()->values();

            foreach ($names as $i => $name) {
                $attribute = self::attributeFor($slug, $name, $system);

                CategoryAttribute::firstOrCreate(
                    ['category_id' => $categories[$slug], 'attribute_id' => $attribute->id],
                    ['group' => $this->groupOf($rows, $name), 'sort' => $sort++, 'is_filterable' => true, 'show_on_card' => $i < 3],
                );
            }

            foreach (self::PC_BUILDER_FIELDS[$slug] ?? [] as $code => $group) {
                CategoryAttribute::firstOrCreate(
                    ['category_id' => $categories[$slug], 'attribute_id' => $system[$code]->id],
                    ['group' => $group, 'sort' => $sort++],
                );
            }
        }
    }

    /**
     * @return array<string, Attribute> code => attribute
     */
    private function seedSystemAttributes(): array
    {
        return collect(Attribute::SYSTEM)->map(fn (array $field, string $code) => Attribute::firstOrCreate(
            ['code' => $code],
            ['name' => $field[0], 'unit' => $field[1], 'help' => $field[2], 'is_system' => true],
        ))->all();
    }

    /**
     * The attribute a demo field maps to: an explicit alias, a system field with the same name, or a
     * shared generic field ("Capacity", "Type"…).
     *
     * @param  array<string, Attribute>  $system
     */
    public static function attributeFor(string $category, string $name, array $system): Attribute
    {
        $code = self::FIELD_ALIASES[$category][$name]
            ?? collect($system)->search(fn (Attribute $a) => $a->name === $name)
            ?: null;

        return $code
            ? $system[$code]
            : Attribute::firstOrCreate(['code' => Str::slug($name, '_')], ['name' => $name]);
    }

    /**
     * The detailed-spec group a field appears in (e.g. "Memory" for Memory Type), else Basic Information.
     */
    private function groupOf($rows, string $name): string
    {
        foreach ($rows as $row) {
            foreach ($row['specs'] ?? [] as $group => $specs) {
                if (array_key_exists($name, $specs)) {
                    return $group;
                }
            }
        }

        return 'Basic Information';
    }
}
