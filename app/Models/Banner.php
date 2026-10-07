<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Settings\StoreSettings;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A home hero slide or an announcement-bar message (Admin → Content → Banners). Shown while switched on and inside
 * its optional start/end dates.
 */
#[Fillable([
    'placement', 'style', 'eyebrow', 'icon', 'title', 'highlight', 'text', 'button_label', 'button_url',
    'button2_label', 'button2_url', 'visual', 'color', 'starts_at', 'ends_at', 'is_active', 'sort',
])]
class Banner extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity;

    public const PLACEMENTS = ['hero' => 'Hero slide (home page)', 'announcement' => 'Announcement bar (every page)'];

    /** Built-in illustrations drawn in code (resources/views/storefront/partials/hero). */
    public const VISUALS = ['pc-build' => 'PC build mock-up', 'deals' => 'Deals mock-up', 'cctv' => 'CCTV mock-up', 'repair' => 'Repair mock-up', 'picture' => 'My picture', 'none' => 'No picture'];

    /** Hero glow / announcement bar colours. */
    public const COLORS = ['blue' => 'Brand blue', 'orange' => 'Orange', 'teal' => 'Teal', 'indigo' => 'Indigo', 'navy' => 'Navy'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('banners.announcements'));
        static::deleted(fn () => Cache::forget('banners.announcements'));
    }

    #[Scope]
    protected function live(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort')->orderBy('id');
    }

    /**
     * Hero slides live now, in order.
     *
     * @return Collection<int, self>
     */
    public static function heroSlides(): Collection
    {
        return static::query()->live()->where('placement', 'hero')->with('media')->get();
    }

    /**
     * Announcement messages live now (switched-on ones are cached; dates are checked on every page). The cache holds
     * plain rows — the cache store refuses to unserialize objects (config cache.serializable_classes = false).
     *
     * @return Collection<int, self>
     */
    public static function announcements(): Collection
    {
        $rows = Cache::rememberForever('banners.announcements', fn () => static::query()->where('placement', 'announcement')
            ->where('is_active', true)->orderBy('sort')->orderBy('id')->get()->map->getAttributes()->all());

        return static::hydrate($rows)->filter(fn (self $banner) => $banner->isLive())->values();
    }

    public function isLive(): bool
    {
        return $this->is_active && (! $this->starts_at || $this->starts_at->lte(now())) && (! $this->ends_at || $this->ends_at->gt(now()));
    }

    /** Live, Scheduled, Ended or Off — for the admin list. */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'Off',
            $this->starts_at?->isFuture() ?? false => 'Scheduled',
            $this->ends_at?->isPast() ?? false => 'Ended',
            default => 'Live',
        };
    }

    /** The title with the highlighted words in the brand gradient (escaped). */
    public function titleHtml(): HtmlString
    {
        $title = e($this->title);

        if (filled($this->highlight) && str_contains($this->title, $this->highlight)) {
            $title = str_replace(e($this->highlight), '<span class="text-gradient">'.e($this->highlight).'</span>', $title);
        }

        return new HtmlString($title);
    }

    /** A link as staff typed it: "/offers", "https://…", "#happy-hour", or {call} / {whatsapp} for the store's numbers. */
    public static function link(?string $url): ?string
    {
        $store = app(StoreSettings::class);

        return match (trim((string) $url)) {
            '' => null,
            '{call}' => $store->telHref(),
            '{whatsapp}' => $store->whatsappUrl(),
            default => trim($url),
        };
    }

    public function isExternal(?string $url): bool
    {
        return str_starts_with((string) $url, 'http') && ! str_starts_with((string) $url, url('/'));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('picture')->singleFile();
        $this->addMediaCollection('desktop')->singleFile();
        $this->addMediaCollection('mobile')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('picture')->performOnCollections('picture')->format('webp')->fit(Fit::Max, 720, 560);
        $this->addMediaConversion('desktop')->performOnCollections('desktop')->format('webp')->fit(Fit::Max, 2000, 900);
        $this->addMediaConversion('mobile')->performOnCollections('mobile')->format('webp')->fit(Fit::Max, 900, 900);
    }

    /** Picture URL for a collection (the webp version once it exists). */
    public function imageUrl(string $collection): ?string
    {
        $media = $this->getFirstMedia($collection);

        return $media ? ($media->hasGeneratedConversion($collection) ? $media->getUrl($collection) : $media->getUrl()) : null;
    }
}
