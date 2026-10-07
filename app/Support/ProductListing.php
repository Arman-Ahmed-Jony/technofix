<?php

namespace App\Support;

use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Filters, sorts and paginates a product query from the request's query string, and builds the facet
 * lists (with counts) for the filter sidebar. Facet counts are taken over the whole listing, before
 * filters are applied.
 *
 * Query parameters: brand[], stock[], min, max, f[Spec name][], sort, per_page, page.
 */
class ProductListing
{
    public const SORTS = [
        'default' => 'Default',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'newest' => 'Newest First',
        'name' => 'Name: A to Z',
    ];

    public const PER_PAGE = [20, 40, 60];

    /**
     * @param  Builder  $base  published products for the page (category, brand or search results), unordered
     * @param  list<array{attribute_id: int, name: string, unit: ?string, filterable: bool}>  $template  spec fields offered as filters
     * @return array<string, mixed>
     */
    public static function make(Request $request, Builder $base, array $template = [], bool $brandFacet = true): array
    {
        $brands = CatalogCache::data()['brands'];
        $brandIds = collect($brands)->mapWithKeys(fn ($b) => [$b['slug'] => $b['id']]);
        $specFields = collect($template)->where('filterable', true)->keyBy('name');

        $selected = [
            'brand' => array_values(array_filter((array) $request->query('brand', []), 'is_string')),
            'stock' => array_values(array_filter((array) $request->query('stock', []), 'is_string')),
            'f' => array_map(fn ($v) => array_values(array_filter((array) $v, 'is_string')), array_filter((array) $request->query('f', []), 'is_array')),
            'min' => $request->integer('min') ?: null,
            'max' => $request->integer('max') ?: null,
        ];

        $query = (clone $base)
            ->when($selected['brand'], fn (Builder $q) => $q->whereIn('brand_id', $brandIds->only($selected['brand'])->values()->all() ?: [0]))
            ->when($selected['stock'], fn (Builder $q) => $q->whereIn('stock_status', $selected['stock']))
            ->when($selected['min'], fn (Builder $q) => $q->where('price', '>=', $selected['min']))
            ->when($selected['max'], fn (Builder $q) => $q->where('price', '<=', $selected['max']));

        foreach ($selected['f'] as $name => $values) {
            $field = $specFields->get($name);

            if ($values && $field) {
                $query->whereHas('specValues', fn (Builder $q) => $q->where('attribute_id', $field['attribute_id'])->whereIn('value', self::rawValues($values, $field['unit'])));
            }
        }

        $sort = $request->query('sort');
        $sort = is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : 'default';
        self::applySort($query, $sort);

        $perPage = in_array($request->integer('per_page'), self::PER_PAGE, true) ? $request->integer('per_page') : self::PER_PAGE[0];
        $bounds = (clone $base)->toBase()->selectRaw('min(price) as low, max(price) as high')->first();

        return [
            'products' => $query->paginate($perPage)->withQueryString(),
            'sort' => $sort,
            'perPage' => $perPage,
            'selected' => $selected,
            'facets' => [
                'brands' => $brandFacet ? self::brandFacet($base, $brands) : collect(),
                'stock' => self::stockFacet($base),
                'specs' => $specFields->isNotEmpty() ? self::specFacets($base, $specFields) : collect(),
            ],
            'priceBounds' => ['min' => (int) $bounds?->low, 'max' => (int) $bounds?->high],
            'active' => self::activeFilters($request, $selected, $brands),
        ];
    }

    public static function applySort(Builder $query, string $sort): Builder
    {
        // Call-for-price items (no price) always go last when sorting by price.
        return match ($sort) {
            'price_asc' => $query->orderByRaw('price is null')->orderBy('price')->orderBy('id'),
            'price_desc' => $query->orderByRaw('price is null')->orderByDesc('price')->orderBy('id'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            default => $query->orderByRaw(Catalog::purchasableFirstSql())->orderByDesc('is_featured')->orderByDesc('sold_count')->orderByDesc('id'),
        };
    }

    /**
     * @param  array<int, array{id: int, name: string, slug: string, active: bool}>  $brands
     * @return Collection<int, array{value: string, label: string, count: int}>
     */
    protected static function brandFacet(Builder $base, array $brands): Collection
    {
        return (clone $base)->toBase()
            ->whereNotNull('brand_id')
            ->groupBy('brand_id')
            ->selectRaw('brand_id, count(*) as total')
            ->get()
            ->filter(fn ($row) => isset($brands[$row->brand_id]))
            ->map(fn ($row) => ['value' => $brands[$row->brand_id]['slug'], 'label' => $brands[$row->brand_id]['name'], 'count' => (int) $row->total])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, array{value: string, label: string, count: int}>
     */
    protected static function stockFacet(Builder $base): Collection
    {
        $counts = (clone $base)->toBase()->groupBy('stock_status')->selectRaw('stock_status, count(*) as total')->pluck('total', 'stock_status');

        // Enum order, not alphabetical.
        return collect(StockStatus::cases())
            ->filter(fn (StockStatus $status) => isset($counts[$status->value]))
            ->map(fn (StockStatus $status) => ['value' => $status->value, 'label' => $status->label(), 'count' => (int) $counts[$status->value]])
            ->values();
    }

    /**
     * Spec facets keyed by field name. Fields with fewer than two distinct values are left out.
     *
     * @param  Collection<string, array{attribute_id: int, name: string, unit: ?string}>  $fields
     * @return Collection<string, Collection<int, array{value: string, label: string, count: int}>>
     */
    protected static function specFacets(Builder $base, Collection $fields): Collection
    {
        $rows = ProductSpec::query()->toBase()
            ->whereIn('attribute_id', $fields->pluck('attribute_id')->all())
            ->whereIn('product_id', (clone $base)->toBase()->select('products.id'))
            ->groupBy('attribute_id', 'value')
            ->selectRaw('attribute_id, value, count(*) as total')
            ->get()
            ->groupBy('attribute_id');

        return $fields
            ->map(fn (array $field) => collect($rows->get($field['attribute_id'], []))
                ->map(function ($row) use ($field) {
                    $label = Attribute::display($row->value, $field['unit']);

                    return ['value' => $label, 'label' => $label, 'count' => (int) $row->total];
                })
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values())
            ->filter(fn (Collection $values) => $values->count() > 1);
    }

    /**
     * Facet values carry their unit ("650 W"); stored values may be bare numbers.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    protected static function rawValues(array $values, ?string $unit): array
    {
        if (! $unit) {
            return $values;
        }

        return array_values(array_unique([
            ...$values,
            ...array_map(fn (string $v) => preg_replace('/\s*'.preg_quote($unit, '/').'$/i', '', $v), $values),
        ]));
    }

    /**
     * Chips for the currently applied filters, each with a URL that removes it.
     *
     * @param  array<string, mixed>  $selected
     * @param  array<int, array{name: string, slug: string}>  $brands
     * @return list<array{label: string, url: string}>
     */
    protected static function activeFilters(Request $request, array $selected, array $brands): array
    {
        $query = Arr::except($request->query(), 'page');
        $names = collect($brands)->pluck('name', 'slug');
        $chips = [];

        $without = function (string $path, ?string $value = null) use ($query, $request): string {
            $next = $query;

            if ($value === null) {
                Arr::forget($next, $path);
            } else {
                Arr::set($next, $path, array_values(array_diff((array) Arr::get($next, $path, []), [$value])));
            }

            return $request->url().(($qs = Arr::query($next)) ? '?'.$qs : '');
        };

        foreach ($selected['brand'] as $brand) {
            $chips[] = ['label' => $names[$brand] ?? $brand, 'url' => $without('brand', $brand)];
        }

        foreach ($selected['stock'] as $stock) {
            $chips[] = ['label' => StockStatus::tryFrom($stock)?->label() ?? $stock, 'url' => $without('stock', $stock)];
        }

        foreach ($selected['f'] as $key => $values) {
            foreach ($values as $value) {
                $chips[] = ['label' => "{$key}: {$value}", 'url' => $without("f.{$key}", $value)];
            }
        }

        if ($selected['min'] || $selected['max']) {
            $label = 'Price: '.($selected['min'] ? '৳'.number_format($selected['min']) : '৳0').' – '.($selected['max'] ? '৳'.number_format($selected['max']) : 'any');
            $chips[] = ['label' => $label, 'url' => $request->url().(($qs = Arr::query(Arr::except($query, ['min', 'max']))) ? '?'.$qs : '')];
        }

        return $chips;
    }
}
