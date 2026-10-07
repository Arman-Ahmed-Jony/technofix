<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\RedirectsWhenMoved;
use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['name', 'slug', 'description', 'is_featured', 'is_zone', 'zone_tagline', 'zone_theme', 'sort', 'is_active', 'meta_title', 'meta_description'])]
class Brand extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, RedirectsWhenMoved;

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'is_zone' => 'boolean', 'is_active' => 'boolean', 'sort' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => CatalogCache::flush());
        static::deleted(fn () => CatalogCache::flush());
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Own buying-guide content for this brand's category pages (/laptop/dell). */
    public function categoryPages(): HasMany
    {
        return $this->hasMany(CategoryBrandPage::class);
    }

    /** Categories whose mega-menu column lists this brand. */
    public function menuCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('sort');
    }

    public function url(): string
    {
        return route('brand.show', $this->slug);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('logo')->format('webp')->fit(Fit::Max, 320, 160);
    }

    public function logoUrl(): ?string
    {
        $media = $this->getFirstMedia('logo');

        return $media ? $media->getUrl('logo') : null;
    }

    public static function pathForSlug(string $slug): string
    {
        return '/brand/'.$slug;
    }
}
