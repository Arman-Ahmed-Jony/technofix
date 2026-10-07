<?php

namespace App\Support\ProductSheet;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Product;
use App\Support\CatalogCache;
use App\Support\Slugs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Imports product rows from a spreadsheet. Run it twice: first as a dry run for the preview (nothing is saved),
 * then for real. Rows are matched to existing products by product code, or by exact name when there is no code.
 * Empty cells never change an existing product; only filled cells are applied.
 */
class Importer
{
    public const MAX_ROWS = 5000;

    public const ACTIONS = ['create' => 'New', 'update' => 'Update', 'unchanged' => 'No changes', 'error' => 'Error'];

    /** @var array<string, int> lowercase name / slug / "parent > name" => category id; -1 = ambiguous name */
    protected array $categories = [];

    /** @var array<string, int> lowercase name / slug => brand id */
    protected array $brands = [];

    /** @var array<string, string> brands this run will create (lowercase name => name) */
    protected array $newBrands = [];

    /** @var array<int, array{name: string, unit: ?string}> */
    protected array $attributes = [];

    /** @var array<string, true> slugs claimed by earlier rows of this run */
    protected array $claimedSlugs = [];

    /** @var array<string, int> codes seen in this file => row number */
    protected array $seenSkus = [];

    /** @var array<string, int> lowercase product code => id, for the Related products / Accessories columns */
    protected array $linkIds = [];

    /** @var array<int, string> id => product code */
    protected array $linkCodes = [];

    /** Spreadsheet column => product relation. */
    public const LINKS = ['related' => 'relatedProducts', 'accessories' => 'accessories'];

    /**
     * @param  list<string>  $headers
     * @param  array<int, ?string>  $mapping  column index => field key, "spec:{id}" or null (ignored)
     * @param  array{update_existing?: bool, create_brands?: bool, status?: string}  $options
     */
    public function __construct(protected array $headers, protected array $mapping, protected array $options = [])
    {
        $this->options += ['update_existing' => true, 'create_brands' => true, 'status' => ProductStatus::Published->value];
        $this->loadLookups();
    }

    /**
     * @param  array<int, list<?string>>  $rows  spreadsheet row number => cells
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>, new_brands: list<string>}
     */
    public function run(array $rows, bool $apply): array
    {
        $existing = $this->existingProducts($rows);
        $this->loadLinkedCodes($rows);
        $results = [];

        foreach ($rows as $number => $cells) {
            $results[] = $this->processRow($number, $cells, $existing, $apply);
        }

        $counts = collect($results)->countBy('action');

        return [
            'summary' => [
                ...array_fill_keys(array_keys(self::ACTIONS), 0),
                ...$counts->all(),
                'warnings' => collect($results)->filter(fn ($r) => $r['warnings'])->count(),
                'total' => count($results),
            ],
            'rows' => $results,
            'new_brands' => array_values($this->newBrands),
        ];
    }

    /**
     * @param  list<?string>  $cells
     * @param  array{sku: array<string, Product>, name: array<string, list<Product>>}  $existing
     * @return array<string, mixed>
     */
    protected function processRow(int $number, array $cells, array $existing, bool $apply): array
    {
        $values = $this->mappedValues($cells);
        $specs = $values['specs'];
        unset($values['specs']);

        $errors = [];
        $warnings = [];
        $sku = $values['sku'] ?? null;

        // ─── Find the product ────────────────────────────────────────
        $product = null;
        if ($sku !== null) {
            $key = Str::lower($sku);
            if (isset($this->seenSkus[$key])) {
                $errors[] = "Product code {$sku} is also on row {$this->seenSkus[$key]}.";
            }
            $this->seenSkus[$key] ??= $number;
            $product = $existing['sku'][$key] ?? null;
        } elseif (isset($values['name'])) {
            $matches = $existing['name'][Str::lower($values['name'])] ?? [];
            if (count($matches) > 1) {
                $errors[] = 'Several products are called “'.$values['name'].'” — add the Product code to say which one.';
            }
            $product = count($matches) === 1 ? $matches[0] : null;
        }

        if ($product?->trashed()) {
            $errors[] = "Product {$product->sku} is in the trash. Restore it in the admin first.";
        } elseif ($product && ! $this->options['update_existing']) {
            return $this->result($number, 'unchanged', $product->sku, $product->name, null, [], ['Already exists — skipped (updating existing products is turned off).']);
        }

        $creating = $product === null;
        $product ??= new Product;
        $attributes = [];

        // ─── Plain text fields ───────────────────────────────────────
        foreach (['name' => 255, 'model' => 120, 'warranty' => 60, 'meta_title' => 255, 'meta_description' => 300] as $field => $max) {
            if (isset($values[$field])) {
                $attributes[$field] = Str::limit($values[$field], $max, '');
                if (mb_strlen($values[$field]) > $max) {
                    $warnings[] = Columns::header($field)." was cut to {$max} characters.";
                }
            }
        }

        if ($creating && blank($attributes['name'] ?? null)) {
            $errors[] = 'Name is required for new products.';
        }

        // ─── Category & brand ────────────────────────────────────────
        if (isset($values['category'])) {
            $categoryId = $this->categories[Str::lower($values['category'])] ?? null;
            if ($categoryId === -1) {
                $errors[] = 'More than one category is called “'.$values['category'].'”. Write it as “Parent > '.$values['category'].'”.';
            } elseif (! $categoryId) {
                $errors[] = 'Unknown category “'.$values['category'].'”.';
            } else {
                $attributes['category_id'] = $categoryId;
            }
        } elseif ($creating) {
            $errors[] = 'Category is required for new products.';
        }

        $brandToCreate = null;
        if (isset($values['brand'])) {
            $brandId = $this->brands[Str::lower($values['brand'])] ?? $this->brands[Str::slug($values['brand'])] ?? null;
            if ($brandId) {
                $attributes['brand_id'] = $brandId;
            } elseif ($this->options['create_brands']) {
                $brandToCreate = $values['brand'];
                $this->newBrands[Str::lower($values['brand'])] = $values['brand'];
                $warnings[] = 'New brand “'.$values['brand'].'” will be created.';
            } else {
                $errors[] = 'Unknown brand “'.$values['brand'].'”.';
            }
        }

        // ─── Numbers, lists and flags ────────────────────────────────
        foreach (['price', 'regular_price', 'cost_price'] as $field) {
            if (isset($values[$field])) {
                $amount = self::money($values[$field]);
                $amount === false
                    ? $errors[] = Columns::header($field).' “'.$values[$field].'” is not a number.'
                    : $attributes[$field] = $amount;
            }
        }

        if (isset($values['stock_qty'])) {
            preg_match('/^-?\d+$/', str_replace([',', ' '], '', $values['stock_qty']))
                ? $attributes['stock_qty'] = (int) str_replace([',', ' '], '', $values['stock_qty'])
                : $errors[] = 'Quantity “'.$values['stock_qty'].'” is not a whole number.';
        }

        if (isset($values['stock_status'])) {
            ($status = self::stockStatus($values['stock_status']))
                ? $attributes['stock_status'] = $status
                : $errors[] = 'Availability “'.$values['stock_status'].'” should be one of: '.collect(StockStatus::cases())->map->label()->join(', ').'.';
        }

        if (isset($values['status'])) {
            ($status = self::productStatus($values['status']))
                ? $attributes['status'] = $status
                : $errors[] = 'Status “'.$values['status'].'” should be Published or Draft.';
        }

        foreach (['is_featured', 'is_new', 'is_best_seller', 'is_hot_deal', 'emi_available'] as $field) {
            if (isset($values[$field])) {
                ($flag = self::boolean($values[$field])) === null
                    ? $errors[] = Columns::header($field).' “'.$values[$field].'” should be Yes or No.'
                    : $attributes[$field] = $flag;
            }
        }

        if (isset($values['key_features'])) {
            $features = collect(preg_split('/\r\n|\r|\n|\|/', $values['key_features']))
                ->map(fn ($f) => trim(preg_replace('/^[\s•\-\*✓✔]+/u', '', $f)))->filter()->values();
            if ($features->count() > 8) {
                $warnings[] = 'Only the first 8 key features are kept.';
            }
            $attributes['key_features'] = $features->take(8)->map(fn ($f) => Str::limit($f, 120, ''))->all();
        }

        if (isset($values['description'])) {
            $attributes['description'] = self::descriptionHtml($values['description']);
        }

        $links = [];
        foreach (self::LINKS as $field => $relation) {
            if (isset($values[$field])) {
                $ids = [];
                $unknown = [];
                foreach (self::codes($values[$field]) as $code) {
                    $id = $this->linkIds[Str::lower($code)] ?? null;
                    if (! $id) {
                        $unknown[] = $code;
                    } elseif ($id !== $product->id) {
                        $ids[] = $id;
                    }
                }
                if ($unknown) {
                    $warnings[] = Columns::header($field).': unknown product code '.implode(', ', $unknown).' — ignored.';
                }
                $links[$relation] = array_values(array_unique($ids));
            }
        }

        // ─── Colors ──────────────────────────────────────────────────
        $colors = null;
        if (isset($values['colors'])) {
            $colors = self::parseColors($values['colors']);
            if (count($colors) > 20) {
                $warnings[] = 'Colors: only the first 20 are kept.';
                $colors = array_slice($colors, 0, 20);
            }
        }

        // ─── Codes & URLs ────────────────────────────────────────────
        if ($sku !== null && $creating) {
            if (mb_strlen($sku) > 40) {
                $errors[] = 'Product code is longer than 40 characters.';
            } else {
                $attributes['sku'] = $sku;
            }
        }

        if (isset($values['slug'])) {
            $slug = Str::lower($values['slug']);
            if ($slug !== $product->slug) {
                if (! preg_match(Slugs::PATTERN, $slug)) {
                    $errors[] = 'URL “'.$values['slug'].'” may only use lowercase letters, numbers and single hyphens.';
                } elseif (isset($this->claimedSlugs[$slug]) || ! Slugs::isAvailable($slug, $creating ? null : $product)) {
                    $errors[] = 'URL “'.$slug.'” is already used by another product or category.';
                } else {
                    $attributes['slug'] = $slug;
                }
            }
        } elseif ($creating && isset($attributes['name'])) {
            $attributes['slug'] = $this->freeSlug($attributes['name']);
        }

        // ─── Whole-product rules ─────────────────────────────────────
        if ($creating) {
            $attributes['stock_status'] ??= array_key_exists('price', $attributes) && $attributes['price'] !== null ? StockStatus::InStock : StockStatus::CallForPrice;
            $attributes['status'] ??= ProductStatus::tryFrom($this->options['status']) ?? ProductStatus::Published;
            $attributes['stock_qty'] ??= 0;
        }

        $final = fn (string $field) => array_key_exists($field, $attributes) ? $attributes[$field] : $product->{$field};
        if ($final('stock_status')?->isPurchasable() && $final('price') === null) {
            $errors[] = 'Price is required for '.$final('stock_status')->label().' products (or set Availability to Call for Price).';
        }
        if (($regular = $final('regular_price')) !== null && ($price = $final('price')) !== null && $regular <= $price) {
            if (array_key_exists('regular_price', $attributes)) {
                $warnings[] = 'Regular price ignored — it must be higher than Price.';
                unset($attributes['regular_price']);
            }
        }

        // ─── Specifications ──────────────────────────────────────────
        $categoryId = $attributes['category_id'] ?? $product->category_id;
        $template = collect($categoryId ? (CatalogCache::data()['templates'][$categoryId] ?? []) : [])->keyBy('attribute_id');
        $specValues = [];
        foreach ($specs as $attributeId => $value) {
            if ($template->has($attributeId)) {
                $specValues[$attributeId] = Attribute::normalise($value, $this->attributes[$attributeId]['unit'] ?? null);
            } elseif ($categoryId) {
                $warnings[] = 'Spec “'.($this->attributes[$attributeId]['name'] ?? $attributeId).'” isn’t a field of this category — ignored.';
            }
        }

        $name = $attributes['name'] ?? $product->name;
        $categoryLabel = $categoryId ? (Exporter::categoryLabels()[$categoryId] ?? null) : null;

        if ($errors) {
            return $this->result($number, 'error', $sku ?? $product->sku, $name, $categoryLabel, $errors, $warnings, $cells);
        }

        if ($creating && ($attributes['stock_status'] ?? null) === StockStatus::CallForPrice && ! isset($values['stock_status'])) {
            $warnings[] = 'No price — shown as “Call for price”.';
        }

        // Would anything change?
        $product->fill($attributes);
        $currentSpecs = $creating ? [] : $product->specValues->pluck('value', 'attribute_id')->all();
        $changedSpecs = collect($specValues)->filter(fn ($value, $id) => ($currentSpecs[$id] ?? null) !== $value);
        $changedLinks = collect($links)->filter(fn (array $ids, string $relation) => $creating ? $ids !== [] : $product->{$relation}->modelKeys() !== $ids);
        $currentColors = $creating ? [] : $product->colors->map(fn ($c) => ['name' => $c->name, 'hex' => $c->hex, 'available' => $c->is_available])->all();
        $colorsChanged = $colors !== null && self::colorsText($colors) !== self::colorsText($currentColors);
        $codeList = fn (array $codes) => $codes ? Str::limit(implode(', ', $codes), 60) : '—';
        $changes = $creating ? [] : [
            ...collect($product->getDirty())->keys()->map(fn (string $field) => $this->describeChange($field, $product->getOriginal($field), $product->{$field}))->all(),
            ...($brandToCreate ? ['Brand: '.(CatalogCache::data()['brands'][$product->brand_id]['name'] ?? '—').' → '.$brandToCreate] : []),
            ...$changedSpecs->map(fn ($value, $id) => ($this->attributes[$id]['name'] ?? 'Spec').': '.self::short($currentSpecs[$id] ?? null).' → '.self::short($value))->values()->all(),
            ...$changedLinks->map(fn (array $ids, string $relation) => Columns::header(array_search($relation, self::LINKS, true)).': '
                .$codeList($product->{$relation}->pluck('sku')->all()).' → '.$codeList(array_map(fn (int $id) => $this->linkCodes[$id], $ids)))->values()->all(),
            ...($colorsChanged ? ['Colors: '.Str::limit(self::colorsText($currentColors) ?: '—', 60).' → '.Str::limit(self::colorsText($colors) ?: '—', 60)] : []),
        ];
        $action = $creating ? 'create' : ($changes ? 'update' : 'unchanged');

        if ($apply && $action !== 'unchanged') {
            try {
                DB::transaction(function () use ($product, $brandToCreate, $specValues, $currentSpecs, $changedLinks, $colors, $colorsChanged, $creating) {
                    if ($brandToCreate) {
                        $product->brand_id = $this->brandId($brandToCreate);
                    }
                    $product->save();
                    if ($specValues) {
                        $product->syncSpecs(array_replace($currentSpecs, $specValues)); // keys are attribute ids — don't spread
                    }
                    foreach ($changedLinks as $relation => $ids) {
                        $product->{$relation}()->detach(); // re-attach so the order follows the spreadsheet
                        $product->{$relation}()->attach($ids);
                    }
                    if ($colorsChanged || ($creating && $colors)) {
                        $product->colors()->delete();
                        foreach ($colors as $i => $color) {
                            $product->colors()->create(['name' => $color['name'], 'hex' => $color['hex'], 'is_available' => $color['available'], 'sort' => $i]);
                        }
                    }
                });
            } catch (Throwable $e) {
                report($e);

                return $this->result($number, 'error', $sku ?? $product->sku, $name, $categoryLabel, ['Could not be saved: '.Str::limit($e->getMessage(), 150)], $warnings, $cells);
            }
        }

        if (isset($attributes['slug'])) {
            $this->claimedSlugs[$attributes['slug']] = true;
        }

        return [...$this->result($number, $action, $product->sku ?? $sku, $name, $categoryLabel, [], $warnings), 'changes' => $changes];
    }

    /**
     * @return array<string, mixed>
     */
    protected function result(int $row, string $action, ?string $sku, ?string $name, ?string $category, array $errors, array $warnings, ?array $cells = null): array
    {
        return [
            'row' => $row,
            'action' => $action,
            'sku' => $sku,
            'name' => $name,
            'category' => $category,
            'errors' => array_values($errors),
            'warnings' => array_values(array_unique($warnings)),
            'changes' => [],
            'cells' => $cells,
        ];
    }

    /** "Price: BDT 3,400 → BDT 3,300" for the preview. */
    protected function describeChange(string $field, mixed $old, mixed $new): string
    {
        $data = CatalogCache::data();
        $format = fn (mixed $value) => match (true) {
            $value === null || $value === '' || $value === [] => '—',
            in_array($field, ['price', 'regular_price', 'cost_price'], true) => 'BDT '.number_format((int) $value), // the admin font has no ৳
            $field === 'category_id' => Exporter::categoryLabels()[$value] ?? '?',
            $field === 'brand_id' => $data['brands'][$value]['name'] ?? '?',
            $value instanceof StockStatus => $value->label(),
            $value instanceof ProductStatus => $value->getLabel(),
            is_bool($value) => $value ? 'Yes' : 'No',
            is_array($value) => count($value).' '.Str::plural('item', count($value)),
            default => self::short((string) $value),
        };

        $label = match ($field) {
            'category_id' => 'Category',
            'brand_id' => 'Brand',
            default => isset(Columns::FIELDS[$field]) ? Columns::header($field) : Str::headline($field),
        };

        return in_array($field, ['description', 'meta_description'], true) ? "{$label} updated" : "{$label}: {$format($old)} → {$format($new)}";
    }

    protected static function short(?string $value): string
    {
        return $value === null || $value === '' ? '—' : Str::limit($value, 40);
    }

    /**
     * Mapped, non-empty cell values: field key => value, plus 'specs' => [attribute id => value].
     *
     * @param  list<?string>  $cells
     * @return array<string, mixed>
     */
    protected function mappedValues(array $cells): array
    {
        $values = ['specs' => []];

        foreach ($this->mapping as $index => $target) {
            $value = $cells[$index] ?? null;

            if ($target === null || $value === null || trim($value) === '') {
                continue;
            }

            if (str_starts_with($target, 'spec:')) {
                $values['specs'][(int) substr($target, 5)] = trim($value);
            } else {
                $values[$target] = in_array($target, ['description', 'key_features'], true) ? trim($value) : trim(preg_replace('/\s+/', ' ', $value));
            }
        }

        return $values;
    }

    /**
     * @param  array<int, list<?string>>  $rows
     * @return array{sku: array<string, Product>, name: array<string, list<Product>>}
     */
    protected function existingProducts(array $rows): array
    {
        $column = fn (string $field) => array_search($field, $this->mapping, true);
        $collect = fn ($index) => $index === false ? [] : collect($rows)->pluck($index)->filter()->map(fn ($v) => trim($v))->unique()->values()->all();

        $codes = fn ($query) => $query->select('products.id', 'products.sku'); // qualified: the pivot table has an id too
        $with = ['specValues', 'colors', 'relatedProducts' => $codes, 'accessories' => $codes];

        $bySku = collect($collect($column('sku')))->chunk(500)
            ->flatMap(fn (Collection $chunk) => Product::withTrashed()->with($with)->whereIn('sku', $chunk->all())->get())
            ->keyBy(fn (Product $p) => Str::lower($p->sku))
            ->all();

        $byName = collect($collect($column('name')))->chunk(500)
            ->flatMap(fn (Collection $chunk) => Product::withTrashed()->with($with)->whereIn('name', $chunk->all())->get())
            ->groupBy(fn (Product $p) => Str::lower($p->name))
            ->map(fn (Collection $group) => $group->all())
            ->all();

        return ['sku' => $bySku, 'name' => $byName];
    }

    /**
     * Product codes used in the Related products / Accessories columns.
     *
     * @param  array<int, list<?string>>  $rows
     */
    protected function loadLinkedCodes(array $rows): void
    {
        $codes = collect(array_keys(self::LINKS))
            ->map(fn (string $field) => array_search($field, $this->mapping, true))
            ->filter(fn ($index) => $index !== false)
            ->flatMap(fn (int $index) => collect($rows)->pluck($index)->filter()->flatMap(fn ($value) => self::codes($value)))
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return;
        }

        $products = $codes->chunk(500)->flatMap(fn (Collection $chunk) => Product::query()->whereIn('sku', $chunk->all())->get(['id', 'sku']));

        $this->linkIds = $products->mapWithKeys(fn (Product $p) => [Str::lower($p->sku) => $p->id])->all();
        $this->linkCodes = $products->pluck('sku', 'id')->all();
    }

    /** "TF-01002, TF-01003 | TF-01004" → ['TF-01002', 'TF-01003', 'TF-01004'] */
    /**
     * "Black #111111, White, Lilac (sold out)" → [['name' => 'Black', 'hex' => '#111111', 'available' => true], …]
     *
     * @return list<array{name: string, hex: ?string, available: bool}>
     */
    public static function parseColors(string $value): array
    {
        $colors = [];
        foreach (array_filter(array_map('trim', preg_split('/[,;\n]+/', $value))) as $part) {
            $available = ! preg_match('/\((sold\s*out|out\s*of\s*stock|unavailable)\)/i', $part);
            $hex = preg_match('/#[0-9a-f]{6}\b|#[0-9a-f]{3}\b/i', $part, $m) ? Str::upper($m[0]) : null;
            $name = Str::limit(trim(preg_replace(['/\((sold\s*out|out\s*of\s*stock|unavailable)\)/i', '/#[0-9a-f]{3,6}\b/i', '/\s+/'], ['', '', ' '], $part)), 40, '');
            if ($name !== '' && ! collect($colors)->contains(fn ($c) => Str::lower($c['name']) === Str::lower($name))) {
                $colors[] = ['name' => $name, 'hex' => $hex, 'available' => $available];
            }
        }

        return $colors;
    }

    /** The reverse of parseColors() (also what the export writes). */
    public static function colorsText(array $colors): string
    {
        return collect($colors)->map(fn (array $c) => trim($c['name'].($c['hex'] ? ' '.Str::upper($c['hex']) : '').($c['available'] ? '' : ' (sold out)')))->join(', ');
    }

    public static function codes(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,;|]+/', $value))));
    }

    protected function loadLookups(): void
    {
        $data = CatalogCache::data();

        foreach ($data['categories'] as $id => $category) {
            $name = Str::lower($category['name']);
            $this->categories[$name] = isset($this->categories[$name]) && $this->categories[$name] !== $id ? -1 : $id;
            $this->categories[$category['slug']] = $id;

            if ($category['parent_id'] && isset($data['categories'][$category['parent_id']])) {
                $this->categories[Str::lower($data['categories'][$category['parent_id']]['name'].' > '.$category['name'])] = $id;
            }
        }

        foreach ($data['brands'] as $id => $brand) {
            $this->brands[Str::lower($brand['name'])] = $id;
            $this->brands[$brand['slug']] = $id;
        }

        $this->attributes = Attribute::query()->get(['id', 'name', 'unit'])
            ->mapWithKeys(fn (Attribute $a) => [$a->id => ['name' => $a->name, 'unit' => $a->unit]])
            ->all();
    }

    protected function brandId(string $name): int
    {
        $key = Str::lower($name);

        if (! isset($this->brands[$key])) {
            $slug = Str::slug($name) ?: 'brand';
            for ($base = $slug, $i = 2; Brand::where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            $this->brands[$key] = Brand::create(['name' => $name, 'slug' => $slug, 'is_active' => true, 'sort' => 100])->id;
        }

        return $this->brands[$key];
    }

    protected function freeSlug(string $name): string
    {
        $base = Slugs::unique($name);
        $slug = $base;

        for ($i = 2; isset($this->claimedSlugs[$slug]); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    // ─── Value parsing ───────────────────────────────────────────────

    /** "৳15,500" / "Tk 15500.00" / "15500" → 15500; "" → null; not a number → false. */
    public static function money(string $value): int|false|null
    {
        $clean = preg_replace('/(৳|tk\.?|bdt|taka|\s|,)/iu', '', $value);

        if ($clean === '' || $clean === '-') {
            return null;
        }

        return preg_match('/^\d+(\.\d+)?$/', $clean) ? (int) round((float) $clean) : false;
    }

    public static function boolean(string $value): ?bool
    {
        return match (Str::lower(trim($value))) {
            'yes', 'y', 'true', '1', 'on', '✓', '✔', 'x' => true,
            'no', 'n', 'false', '0', 'off', '' => false,
            default => null,
        };
    }

    public static function stockStatus(string $value): ?StockStatus
    {
        $key = Str::of($value)->lower()->replaceMatches('/[^a-z]/', '')->toString();

        return match ($key) {
            'instock', 'available', 'yes', 'stock' => StockStatus::InStock,
            'outofstock', 'stockout', 'soldout', 'no', 'unavailable' => StockStatus::OutOfStock,
            'preorder' => StockStatus::PreOrder,
            'upcoming', 'comingsoon' => StockStatus::Upcoming,
            'callforprice', 'call', 'tba' => StockStatus::CallForPrice,
            default => null,
        };
    }

    public static function productStatus(string $value): ?ProductStatus
    {
        return match (Str::lower(trim($value))) {
            'published', 'publish', 'active', 'live', 'yes', 'visible', 'on' => ProductStatus::Published,
            'draft', 'hidden', 'inactive', 'no', 'off' => ProductStatus::Draft,
            default => null,
        };
    }

    /** Plain text → paragraphs; HTML is kept as it is (it's sanitised when shown). */
    public static function descriptionHtml(string $value): string
    {
        if ($value !== strip_tags($value)) {
            return $value;
        }

        return collect(preg_split('/\R\s*\R/', trim($value)))
            ->map(fn ($paragraph) => '<p>'.preg_replace('/\R/', '<br>', e(trim($paragraph))).'</p>')
            ->join('');
    }
}
