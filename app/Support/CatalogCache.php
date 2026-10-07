<?php

namespace App\Support;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryAttribute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;

/**
 * Cached catalog structure: category tree (with inherited icons and menu brands), spec templates and brands.
 * Read on every storefront request, so it lives in the cache until a category, brand or spec field changes
 * (the models call flush() when saved or deleted).
 */
class CatalogCache
{
    public const KEY = 'catalog.structure.v1';

    /**
     * @return array{
     *     categories: array<int, array{id: int, parent_id: ?int, name: string, slug: string, icon: string, depth: int, active: bool, menu: bool, children: list<int>, brands: list<int>}>,
     *     slugs: array<string, int>,
     *     templates: array<int, list<array{attribute_id: int, name: string, code: string, unit: ?string, group: string, filterable: bool, card: bool}>>,
     *     brands: array<int, array{id: int, name: string, slug: string, active: bool}>,
     * }
     */
    public static function data(): array
    {
        return once(fn () => Cache::rememberForever(self::KEY, fn () => self::build()));
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
        Once::flush(); // drop the per-request copy too (queue workers and tests run many "requests" per process)
    }

    /**
     * @return array<string, mixed>
     */
    protected static function build(): array
    {
        $menuBrands = DB::table('brand_category')->orderBy('sort')->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->pluck('brand_id')->map(fn ($id) => (int) $id)->all());

        $categories = [];
        // tree_path order guarantees a parent is always seen before its children.
        foreach (Category::query()->orderBy('tree_path')->get() as $category) {
            $parentIcon = $category->parent_id ? ($categories[$category->parent_id]['icon'] ?? null) : null;

            $categories[$category->id] = [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon ?: ($parentIcon ?: 'category'),
                'depth' => $category->depth,
                'active' => $category->is_active,
                'menu' => $category->show_in_menu,
                'children' => [],
                'brands' => $menuBrands[$category->id] ?? [],
            ];

            if ($category->parent_id && isset($categories[$category->parent_id])) {
                $categories[$category->parent_id]['children'][] = $category->id;
            }
        }

        $templates = CategoryAttribute::query()
            ->join('attributes', 'attributes.id', '=', 'category_attribute.attribute_id')
            ->orderBy('category_attribute.sort')
            ->orderBy('category_attribute.id')
            ->get(['category_attribute.*', 'attributes.name', 'attributes.code', 'attributes.unit'])
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->map(fn ($row) => [
                'attribute_id' => (int) $row->attribute_id,
                'name' => $row->name,
                'code' => $row->code,
                'unit' => $row->unit,
                'group' => $row->group,
                'filterable' => (bool) $row->is_filterable,
                'card' => (bool) $row->show_on_card,
            ])->values()->all())
            ->all();

        $brands = Brand::query()->orderBy('name')->get(['id', 'name', 'slug', 'is_active'])
            ->mapWithKeys(fn (Brand $brand) => [$brand->id => ['id' => $brand->id, 'name' => $brand->name, 'slug' => $brand->slug, 'active' => $brand->is_active]])
            ->all();

        return [
            'categories' => $categories,
            'slugs' => collect($categories)->mapWithKeys(fn ($c) => [$c['slug'] => $c['id']])->all(),
            'templates' => $templates,
            'brands' => $brands,
            // PC Builder system fields: code => attribute id
            'system_fields' => Attribute::query()->where('is_system', true)->pluck('id', 'code')->map(fn ($id) => (int) $id)->all(),
        ];
    }
}
