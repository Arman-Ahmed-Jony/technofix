<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RedirectsWhenMoved;
use App\Services\StockAlerts;
use App\Services\StockService;
use App\Support\Catalog;
use App\Support\CatalogCache;
use App\Support\Deals;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as Accessor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Brand, category name/icon and spec templates are read from CatalogCache (no extra queries per card).
 * Accessors keep the property names the storefront views use: url, brand_name, category_name, icon,
 * image, stock, flags, emi, discount, discount_percent. Prices: `price` is the normal price (admin), `sale_price` is what
 * the customer pays now (campaign deals included), `list_price` the struck-through price.
 */
#[Fillable([
    'name', 'slug', 'sku', 'model', 'brand_id', 'category_id', 'key_features', 'description', 'extra_specs',
    'price', 'regular_price', 'cost_price', 'stock_status', 'stock_qty', 'warranty', 'emi_available',
    'is_featured', 'is_new', 'is_best_seller', 'is_hot_deal', 'status', 'meta_title', 'meta_description',
])]
class Product extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, RedirectsWhenMoved, SoftDeletes;

    /** Set automatically (featured order) — not a staff edit worth logging. */
    public array $activityIgnore = ['featured_sort'];

    protected function casts(): array
    {
        return [
            'key_features' => 'array',
            'extra_specs' => 'array',
            'stock_status' => StockStatus::class,
            'status' => ProductStatus::class,
            'price' => 'integer',
            'regular_price' => 'integer',
            'cost_price' => 'integer',
            'stock_qty' => 'integer',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
            'emi_available' => 'boolean',
            'is_featured' => 'boolean',
            'featured_sort' => 'integer',
            'is_new' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_hot_deal' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->sku = $product->sku ?: static::nextSku();
        });

        // Featured order (Products → Arrange featured): newly featured products go to the end; un-featured lose their place.
        static::saving(function (Product $product) {
            if (! $product->is_featured) {
                $product->featured_sort = null;
            } elseif ($product->featured_sort === null) {
                $product->featured_sort = (int) static::withTrashed()->max('featured_sort') + 1;
            }
        });

        // Back to "In stock" / "Pre-order" → email the customers who asked to be told (App\Services\StockAlerts).
        static::updated(function (Product $product) {
            if ($product->wasChanged('stock_status') && $product->stock->isPurchasable()
                && ! (StockStatus::tryFrom((string) $product->getRawOriginal('stock_status'))?->isPurchasable() ?? false)) {
                StockAlerts::productAvailable($product);
            }
        });

        // Quantity edited by staff (form, table, import) → stock ledger. Orders change stock through StockService.
        static::updated(function (Product $product) {
            if ($product->wasChanged('stock_qty')) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'change' => $product->stock_qty - (int) $product->getOriginal('stock_qty'),
                    'balance' => $product->stock_qty,
                    'type' => 'adjustment',
                    'user_id' => auth('web')->id(), // staff guard, whatever the current default guard is
                ]);

                if (! $product->wasChanged('stock_status')) {
                    StockService::syncStatus($product);
                }
            }
        });
    }

    /** After query-level flag changes (bulk "Set a flag"): newly featured go to the end, un-featured lose their place. */
    public static function syncFeaturedOrder(): void
    {
        static::withTrashed()->where('is_featured', false)->whereNotNull('featured_sort')->update(['featured_sort' => null]);

        $next = (int) static::withTrashed()->max('featured_sort');
        static::withTrashed()->where('is_featured', true)->whereNull('featured_sort')->orderBy('id')->pluck('id')
            ->each(function ($id) use (&$next) {
                static::withTrashed()->whereKey($id)->update(['featured_sort' => ++$next]);
            });
    }

    /** "TF-01092"-style product code for new products. */
    public static function nextSku(): string
    {
        $number = (static::withTrashed()->max('id') ?? 0) + 1001;

        while (static::withTrashed()->where('sku', $sku = 'TF-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT))->exists()) {
            $number++;
        }

        return $sku;
    }

    // ─── Relations & scopes ─────────────────────────────────────────

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function specValues(): HasMany
    {
        return $this->hasMany(ProductSpec::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    /** Customer reviews (App\Services\ProductFeedback); rating_avg / rating_count summarise the approved ones. */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    /** Optional color versions (the customer picks one); empty = the product has no color choice. */
    public function colors(): HasMany
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort')->orderBy('id');
    }

    /**
     * The color for an order line: the one asked for (any spelling of its name), else the first available one;
     * null when the product has no colors. Customers can't pick a sold-out color (staff can).
     *
     * @throws \InvalidArgumentException with a message for the customer
     */
    public function pickColor(?string $wanted, bool $allowSoldOut = false): ?string
    {
        $colors = $this->colors;
        if ($colors->isEmpty()) {
            return null;
        }

        $wanted = trim((string) $wanted);
        if ($wanted === '') {
            return $colors->firstWhere('is_available', true)?->name ?? throw new \InvalidArgumentException("{$this->name} is sold out in every color.");
        }

        $color = $colors->first(fn (ProductColor $c) => mb_strtolower($c->name) === mb_strtolower($wanted))
            ?? throw new \InvalidArgumentException("{$this->name} isn't available in {$wanted}. Please choose another color.");

        if (! $color->is_available && ! $allowSoldOut) {
            throw new \InvalidArgumentException("Sorry, {$this->name} in {$color->name} is sold out. Please choose another color.");
        }

        return $color->name;
    }

    /** Hand-picked alternatives ("You may also like"), in the order they were added. */
    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_id')
            ->withPivotValue('type', 'related')
            ->orderByPivot('id');
    }

    /** Hand-picked add-ons ("Accessories for this product"), in the order they were added. */
    public function accessories(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_id')
            ->withPivotValue('type', 'accessory')
            ->orderByPivot('id');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', ProductStatus::Published);
    }

    /** Everything a product card needs, in two extra queries for the whole list. */
    #[Scope]
    protected function forCards(Builder $query): void
    {
        $query->with(['specValues', 'media', 'colors']);
    }

    // ─── Media ──────────────────────────────────────────────────────

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        // Optional picture made for link previews (Facebook, WhatsApp…); without it the first photo is used.
        $this->addMediaCollection('social')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->format('webp')->fit(Fit::Contain, 200, 200)->background('ffffff')->performOnCollections('images');
        $this->addMediaConversion('card')->format('webp')->fit(Fit::Contain, 480, 480)->background('ffffff')->performOnCollections('images');
        $this->addMediaConversion('large')->format('webp')->fit(Fit::Max, 1200, 1200)->performOnCollections('images');
        // Link previews (Facebook, WhatsApp): 1.91:1 JPG — WebP isn't shown everywhere.
        $this->addMediaConversion('share')->format('jpg')->fit(Fit::Contain, 1200, 630)->background('ffffff')->quality(82)
            ->performOnCollections('images', 'social');
    }

    /**
     * The product as the browser cart keeps it (also what tracking reads: brand and category go to the ad platforms).
     *
     * @return array{id: int, name: string, price: ?int, url: string, brand: string, category: string}
     */
    public function cartItem(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'price' => $this->sale_price, 'url' => $this->url, 'brand' => $this->brand_name, 'category' => $this->category_name];
    }

    /**
     * Picture for link previews: the optional social picture, else the first photo; null = the site's default
     * (no photo yet).
     */
    public function shareImage(): ?string
    {
        if ($social = $this->getFirstMedia('social')) {
            return $social->hasGeneratedConversion('share') ? $social->getUrl('share') : $social->getUrl();
        }

        $media = $this->getFirstMedia('images');

        return match (true) {
            $media === null => null,
            $media->hasGeneratedConversion('share') => $media->getUrl('share'),
            default => $media->getUrl('large'),
        };
    }

    /**
     * @return list<array{thumb: string, card: string, large: string}>
     */
    public function gallery(): array
    {
        return $this->getMedia('images')
            ->map(fn (Media $media) => ['thumb' => $media->getUrl('thumb'), 'card' => $media->getUrl('card'), 'large' => $media->getUrl('large')])
            ->all();
    }

    // ─── View accessors ─────────────────────────────────────────────

    protected function url(): Accessor
    {
        return Accessor::get(fn () => url($this->slug));
    }

    protected function image(): Accessor
    {
        return Accessor::get(function () {
            $media = $this->getFirstMedia('images');

            return $media ? $media->getUrl('card') : null;
        });
    }

    protected function brandName(): Accessor
    {
        return Accessor::get(fn () => CatalogCache::data()['brands'][$this->brand_id]['name'] ?? '');
    }

    protected function brandSlug(): Accessor
    {
        return Accessor::get(fn () => CatalogCache::data()['brands'][$this->brand_id]['slug'] ?? null);
    }

    protected function categoryName(): Accessor
    {
        return Accessor::get(fn () => CatalogCache::data()['categories'][$this->category_id]['name'] ?? '');
    }

    protected function icon(): Accessor
    {
        return Accessor::get(fn () => CatalogCache::data()['categories'][$this->category_id]['icon'] ?? 'package');
    }

    protected function stock(): Accessor
    {
        return Accessor::get(fn () => $this->stock_status ?? StockStatus::InStock);
    }

    protected function emi(): Accessor
    {
        return Accessor::get(fn () => $this->emi_available);
    }

    /** @return Accessor<list<string>, never> */
    protected function flags(): Accessor
    {
        return Accessor::get(fn () => array_keys(array_filter([
            'featured' => $this->is_featured,
            'new' => $this->is_new,
            'best' => $this->is_best_seller,
            'deal' => $this->is_hot_deal,
        ])));
    }

    /**
     * The running campaign deal for this product, if it beats the normal price.
     *
     * @return array{price: int, ends_at: Carbon, campaign: string, badge: string, campaign_id: int}|null
     */
    public function deal(): ?array
    {
        $deal = $this->price !== null ? Deals::for($this->id) : null;

        return $deal && $deal['price'] < $this->price ? $deal : null;
    }

    /** What the customer pays now: the deal price during a campaign, else the price. Use this on the storefront and for orders. */
    protected function salePrice(): Accessor
    {
        return Accessor::get(fn () => $this->deal()['price'] ?? $this->price);
    }

    /** The struck-through "was" price, or null. */
    protected function listPrice(): Accessor
    {
        return Accessor::get(function () {
            $was = $this->deal() ? max((int) $this->price, (int) $this->regular_price) : (int) $this->regular_price;

            return $this->sale_price !== null && $was > $this->sale_price ? $was : null;
        });
    }

    protected function discount(): Accessor
    {
        return Accessor::get(fn () => $this->list_price ? $this->list_price - $this->sale_price : 0);
    }

    protected function discountPercent(): Accessor
    {
        return Accessor::get(fn () => $this->discount ? (int) round($this->discount / $this->list_price * 100) : 0);
    }

    public function categoryUrl(): string
    {
        return url(CatalogCache::data()['categories'][$this->category_id]['slug'] ?? '');
    }

    /** "/motherboard/gigabyte", or the category page when the product has no brand. */
    public function categoryBrandUrl(): string
    {
        $category = CatalogCache::data()['categories'][$this->category_id]['slug'] ?? null;

        return $category && $this->brand_slug ? Catalog::categoryBrandUrl($category, $this->brand_slug) : $this->categoryUrl();
    }

    /** Sanitised description HTML (safe to echo unescaped). */
    public function descriptionHtml(): string
    {
        return filled($this->description) ? RichContentRenderer::make($this->description)->toHtml() : '';
    }

    // ─── Specifications ─────────────────────────────────────────────

    /**
     * The category's spec template rows.
     *
     * @return list<array{attribute_id: int, name: string, code: string, unit: ?string, group: string, filterable: bool, card: bool}>
     */
    public function template(): array
    {
        return CatalogCache::data()['templates'][$this->category_id] ?? [];
    }

    /**
     * Replace the product's spec values with the given ones (attribute_id => value). Only fields in the
     * category's template are kept; empty values are removed.
     *
     * @param  array<int, ?string>  $values
     */
    public function syncSpecs(array $values): void
    {
        $fields = collect($this->template())->keyBy('attribute_id');

        $values = collect($values)
            ->filter(fn ($value, $attributeId) => $fields->has($attributeId))
            ->map(fn ($value, $attributeId) => Attribute::normalise($value, $fields[$attributeId]['unit']))
            ->filter(fn (string $value) => $value !== '');

        $this->specValues()->whereNotIn('attribute_id', $values->keys()->all() ?: [0])->delete();

        foreach ($values as $attributeId => $value) {
            $this->specValues()->updateOrCreate(['attribute_id' => $attributeId], ['value' => Str::limit($value, 250, '')]);
        }

        $this->unsetRelation('specValues');
    }

    /**
     * @return array<int, string> attribute_id => value
     */
    protected function specValueMap(): array
    {
        return $this->specValues->pluck('value', 'attribute_id')->all();
    }

    /**
     * Up to three short spec values for product cards (fields marked "show on card", else the filter fields).
     *
     * @return list<string>
     */
    public function cardSpecs(): array
    {
        $values = $this->specValueMap();
        $rows = collect($this->template());
        $rows = $rows->where('card', true)->isNotEmpty() ? $rows->where('card', true) : $rows->where('filterable', true);

        return $rows->map(fn ($row) => isset($values[$row['attribute_id']]) ? Attribute::display($values[$row['attribute_id']], $row['unit']) : null)
            ->filter()->take(3)->values()->all();
    }

    /**
     * Every spec value keyed by field name, in template order (used by Compare).
     *
     * @return array<string, string>
     */
    public function specMap(): array
    {
        $values = $this->specValueMap();

        return collect($this->template())
            ->filter(fn ($row) => isset($values[$row['attribute_id']]))
            ->mapWithKeys(fn ($row) => [$row['name'] => Attribute::display($values[$row['attribute_id']], $row['unit'])])
            ->all();
    }

    /**
     * Grouped spec table for the product page: template rows that have a value, then any extra rows.
     *
     * @return array<string, array<string, string>>
     */
    public function specTable(): array
    {
        $values = $this->specValueMap();
        $table = [];

        foreach ($this->template() as $row) {
            if (filled($values[$row['attribute_id']] ?? null)) {
                $table[$row['group']][$row['name']] = Attribute::display($values[$row['attribute_id']], $row['unit']);
            }
        }

        foreach ((array) $this->extra_specs as $extra) {
            if (filled($extra['label'] ?? null) && filled($extra['value'] ?? null)) {
                $table[$extra['group'] ?? 'Other'][$extra['label']] = $extra['value'];
            }
        }

        if ($this->warranty) {
            $table['Warranty']['Warranty'] = $this->warranty;
        }

        return $table;
    }

    public static function pathForSlug(string $slug): string
    {
        return '/'.$slug;
    }
}
