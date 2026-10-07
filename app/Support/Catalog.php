<?php

namespace App\Support;

use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * Storefront read-side of the catalog. Categories come from CatalogCache as Fluent objects
 * ($category->name, ->url, ->icon, ->children…); products are Eloquent queries.
 */
class Catalog
{
    // ─── Categories ─────────────────────────────────────────────────

    /** An active category (whose ancestors are all active too), or null. */
    public function category(string $slug): ?Fluent
    {
        $id = CatalogCache::data()['slugs'][$slug] ?? null;

        return $id ? $this->categoryById($id) : null;
    }

    public function categoryById(int $id): ?Fluent
    {
        $categories = CatalogCache::data()['categories'];
        $row = $categories[$id] ?? null;

        for ($node = $row; $node; $node = $categories[$node['parent_id']] ?? null) {
            if (! $node['active']) {
                return null;
            }
        }

        return $row ? $this->fluent($row) : null;
    }

    /**
     * @return Collection<int, Fluent> root first, $category last
     */
    public function ancestors(Fluent $category): Collection
    {
        $trail = collect([$category]);

        while ($category->parent_id && ($category = $this->categoryById($category->parent_id))) {
            $trail->prepend($category);
        }

        return $trail;
    }

    /**
     * @return Collection<int, Fluent> active children, in order
     */
    public function children(Fluent $category): Collection
    {
        $categories = CatalogCache::data()['categories'];

        return collect($category->children)
            ->filter(fn (int $id) => $categories[$id]['active'] ?? false)
            ->map(fn (int $id) => $this->fluent($categories[$id]))
            ->values();
    }

    /**
     * The category and all of its active descendants.
     *
     * @return list<int>
     */
    public function descendantIds(Fluent|array $category): array
    {
        $categories = CatalogCache::data()['categories'];
        $ids = [$category['id']];

        foreach ($category['children'] as $childId) {
            if ($categories[$childId]['active'] ?? false) {
                $ids = [...$ids, ...$this->descendantIds($categories[$childId])];
            }
        }

        return $ids;
    }

    /**
     * Mega menu tree: top-level categories with their children and sub-links
     * (grandchildren, or brand links where brands are attached to the category).
     *
     * @return Collection<int, Fluent>
     */
    public function menu(): Collection
    {
        $inMenu = fn (Collection $items) => $items->filter(fn (Fluent $c) => $c->menu)->values();

        return $inMenu(collect(CatalogCache::data()['categories'])
            ->filter(fn (array $c) => $c['depth'] === 0 && $c['active'])
            ->map(fn (array $c) => $this->fluent($c)))
            ->map(fn (Fluent $top) => new Fluent([
                'name' => $top->name,
                'slug' => $top->slug,
                'url' => $top->url,
                'icon' => $top->icon,
                'brandLinks' => $this->brandLinks($top),
                'children' => $inMenu($this->children($top))->map(fn (Fluent $child) => new Fluent([
                    'name' => $child->name,
                    'url' => $child->url,
                    'icon' => $child->icon,
                    'links' => ($grandchildren = $inMenu($this->children($child)))->isNotEmpty()
                        ? $grandchildren->map(fn (Fluent $leaf) => new Fluent(['name' => $leaf->name, 'url' => $leaf->url]))
                        : $this->brandLinks($child),
                ])),
            ]));
    }

    /**
     * @return Collection<int, Fluent>
     */
    protected function brandLinks(Fluent $category): Collection
    {
        $brands = CatalogCache::data()['brands'];

        return collect($category->brands)
            ->filter(fn (int $id) => $brands[$id]['active'] ?? false)
            ->map(fn (int $id) => new Fluent([
                'name' => $brands[$id]['name'],
                'url' => self::categoryBrandUrl($category['slug'], $brands[$id]['slug']),
            ]))
            ->values();
    }

    /** "/motherboard/gigabyte" */
    public static function categoryBrandUrl(string $categorySlug, string $brandSlug): string
    {
        return route('catalog.brand', [$categorySlug, $brandSlug]);
    }

    /**
     * Brands with published products in a category (and its sub-categories), as chips linking to their
     * category + brand pages.
     *
     * @return Collection<int, Fluent>
     */
    public function categoryBrands(Fluent $category, ?Brand $except = null): Collection
    {
        $brands = CatalogCache::data()['brands'];

        return Product::query()->published()
            ->whereIn('category_id', $this->descendantIds($category))
            ->whereNotNull('brand_id')
            ->when($except, fn (Builder $q) => $q->where('brand_id', '!=', $except->id))
            ->distinct()
            ->pluck('brand_id')
            ->filter(fn ($id) => $brands[$id]['active'] ?? false)
            ->map(fn ($id) => new Fluent([
                'name' => $brands[$id]['name'],
                'url' => self::categoryBrandUrl($category->slug, $brands[$id]['slug']),
                'icon' => $category->icon,
            ]))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function fluent(array $row): Fluent
    {
        return new Fluent([...$row, 'url' => url($row['slug'])]);
    }

    // ─── Products ───────────────────────────────────────────────────

    /** Published products, with what product cards need eager-loaded. */
    public function products(): Builder
    {
        return Product::query()->published()->forCards();
    }

    public function product(string $slug): ?Product
    {
        return $this->products()->where('slug', $slug)->first();
    }

    /**
     * Published products for the given ids, in the same order; unknown ids are skipped.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function productsByIds(array $ids): Collection
    {
        if (! $ids) {
            return collect();
        }

        $found = $this->products()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn (int $id) => $found->get($id))->filter()->values();
    }

    /** Published products in a category and all of its descendants. */
    public function productsIn(Fluent $category): Builder
    {
        return $this->products()->whereIn('category_id', $this->descendantIds($category));
    }

    /**
     * Product count and lowest price for a category (including sub-categories).
     *
     * @return array{count: int, from: ?int}
     */
    public function stats(Fluent $category): array
    {
        $row = Product::query()->published()
            ->whereIn('category_id', $this->descendantIds($category))
            ->selectRaw('count(*) as aggregate_count, min(price) as aggregate_from')
            ->toBase()
            ->first();

        return ['count' => (int) $row->aggregate_count, 'from' => $row->aggregate_from !== null ? (int) $row->aggregate_from : null];
    }

    /**
     * @param  'featured'|'new'|'best'|'deal'  $flag
     * @return EloquentCollection<int, Product>
     */
    public function flagged(string $flag, int $limit = 12): EloquentCollection
    {
        $column = ['featured' => 'is_featured', 'new' => 'is_new', 'best' => 'is_best_seller', 'deal' => 'is_hot_deal'][$flag];

        return $this->products()->where($column, true)
            ->when($flag === 'featured', fn ($query) => $query->orderByRaw('featured_sort is null')->orderBy('featured_sort')) // staff order
            ->orderByDesc('updated_at')->orderByDesc('id')->limit($limit)->get();
    }

    /**
     * Hand-picked related products first, topped up with products from the same parent category
     * (same category first).
     *
     * @return EloquentCollection<int, Product>
     */
    public function related(Product $product, int $limit = 8): EloquentCollection
    {
        $picked = $product->relatedProducts()->published()->forCards()->limit($limit)->get();
        $category = $this->categoryById($product->category_id);

        if ($picked->count() >= $limit || ! $category) {
            return $picked;
        }

        $pool = ($category->parent_id ? $this->categoryById($category->parent_id) : null) ?? $category;

        return $picked->concat($this->productsIn($pool)
            ->whereKeyNot([$product->getKey(), ...$picked->modelKeys()])
            ->orderByRaw('category_id = ? desc', [$product->category_id])
            ->orderByRaw(self::purchasableFirstSql())
            ->orderByDesc('id')
            ->limit($limit - $picked->count())
            ->get());
    }

    /**
     * Hand-picked accessories that are on sale.
     *
     * @return EloquentCollection<int, Product>
     */
    public function accessories(Product $product, int $limit = 12): EloquentCollection
    {
        return $product->accessories()->published()->forCards()->limit($limit)->get();
    }

    // ─── Search ─────────────────────────────────────────────────────

    /**
     * Every word must appear in the product name, model, brand or category (LIKE-based; fine for a few
     * thousand products — switch to MySQL FULLTEXT when the catalog grows).
     */
    public function search(string $query): Builder
    {
        $terms = collect(preg_split('/\s+/', Str::lower(trim($query))))->filter()->take(8);

        if ($terms->isEmpty()) {
            return $this->products()->whereRaw('1 = 0');
        }

        $data = CatalogCache::data();

        return $this->products()->where(function (Builder $q) use ($terms, $data) {
            foreach ($terms as $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $brandIds = collect($data['brands'])->filter(fn ($b) => Str::contains(Str::lower($b['name']), $term))->keys()->all();
                $categoryIds = collect($data['categories'])->filter(fn ($c) => Str::contains(Str::lower($c['name']), $term))->keys()->all();

                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->when($brandIds, fn (Builder $w) => $w->orWhereIn('brand_id', $brandIds))
                    ->when($categoryIds, fn (Builder $w) => $w->orWhereIn('category_id', $categoryIds)));
            }
        });
    }

    /**
     * @return Collection<int, Fluent>
     */
    public function searchCategories(string $query): Collection
    {
        $q = Str::lower(trim($query));

        if ($q === '') {
            return collect();
        }

        return collect(CatalogCache::data()['categories'])
            ->filter(fn (array $c) => Str::contains(Str::lower($c['name']), $q))
            ->map(fn (array $c) => $this->categoryById($c['id']))
            ->filter()
            ->values();
    }

    // ─── Brands ─────────────────────────────────────────────────────

    public function brand(string $slug): ?Brand
    {
        return Brand::active()->where('slug', $slug)->first();
    }

    /**
     * Brands with an "Exclusive Zone" card on the home page (max 3).
     *
     * @return EloquentCollection<int, Brand>
     */
    public function brandZones(): EloquentCollection
    {
        return Brand::active()->where('is_zone', true)->orderBy('sort')->orderBy('name')->limit(3)->get();
    }

    /**
     * @return EloquentCollection<int, Brand>
     */
    public function featuredBrands(): EloquentCollection
    {
        return Brand::active()->where('is_featured', true)->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * Categories a brand has published products in, each linking to that category filtered by the brand.
     *
     * @return Collection<int, Fluent>
     */
    public function brandCategories(Brand $brand): Collection
    {
        return Product::query()->published()->where('brand_id', $brand->id)
            ->distinct()->pluck('category_id')
            ->map(fn ($id) => $this->categoryById((int) $id))
            ->filter()
            ->sortBy('name')
            ->map(fn (Fluent $c) => new Fluent([...$c->toArray(), 'url' => self::categoryBrandUrl($c->slug, $brand->slug)]))
            ->values();
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /** ORDER BY expression putting products that can be bought now first. */
    public static function purchasableFirstSql(): string
    {
        $values = collect(StockStatus::cases())->filter->isPurchasable()->map(fn (StockStatus $s) => "'{$s->value}'")->join(',');

        return "case when stock_status in ({$values}) then 0 else 1 end";
    }
}
