<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RedirectsWhenMoved;
use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Category tree node. `depth` and `tree_path` are maintained automatically on save so the tree can be
 * listed in order with a single ORDER BY (and children follow when a category moves).
 */
#[Fillable(['parent_id', 'name', 'slug', 'icon', 'description', 'faqs', 'sort', 'is_active', 'show_in_menu', 'meta_title', 'meta_description'])]
class Category extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, RedirectsWhenMoved;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_in_menu' => 'boolean', 'sort' => 'integer', 'depth' => 'integer', 'faqs' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            $parent = $category->parent_id ? static::find($category->parent_id) : null;
            $category->depth = $parent ? $parent->depth + 1 : 0;
            $category->tree_path = ($parent ? $parent->tree_path.'/' : '')
                .str_pad((string) $category->sort, 4, '0', STR_PAD_LEFT).'-'.Str::limit($category->slug, 30, '');
        });

        static::saved(function (Category $category) {
            if ($category->wasChanged('tree_path')) {
                $category->children()->get()->each->save(); // recompute descendants' paths
            }
            CatalogCache::flush();
        });

        static::deleted(fn () => CatalogCache::flush());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Brands listed under this category in the mega menu. */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->withPivot('sort')->orderByPivot('sort');
    }

    /** Own buying-guide content for this category's brand pages (/laptop/dell). */
    public function brandPages(): HasMany
    {
        return $this->hasMany(CategoryBrandPage::class);
    }

    /** Spec template rows (which fields this category's products have). */
    public function templateAttributes(): HasMany
    {
        return $this->hasMany(CategoryAttribute::class)->orderBy('sort');
    }

    /** "Components › Motherboard" style label, used in admin selects. */
    public function treeLabel(): string
    {
        return str_repeat('— ', $this->depth).$this->name;
    }

    public function url(): string
    {
        return url($this->slug);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->format('webp')->fit(Fit::Contain, 480, 480);
    }

    /**
     * Options for admin selects: id => indented name, in tree order. Pass a category to leave out it
     * and its descendants (a category can't move under itself).
     *
     * @return array<int, string>
     */
    public static function treeOptions(?Category $except = null): array
    {
        return static::query()
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->id)->where('tree_path', 'not like', $except->tree_path.'/%'))
            ->orderBy('tree_path')
            ->get(['id', 'name', 'depth'])
            ->mapWithKeys(fn (Category $c) => [$c->id => $c->treeLabel()])
            ->all();
    }

    /**
     * This category's id and all descendant ids (for admin filters).
     *
     * @return list<int>
     */
    public static function withDescendantIds(int $id): array
    {
        $path = static::whereKey($id)->value('tree_path');

        return $path === null ? [] : static::query()
            ->where(fn ($q) => $q->whereKey($id)->orWhere('tree_path', 'like', $path.'/%'))
            ->pluck('id')->all();
    }

    public static function pathForSlug(string $slug): string
    {
        return '/'.$slug;
    }

    protected static function redirectsChildren(): bool
    {
        return true; // "/{category}/{brand}" pages move with it
    }
}
