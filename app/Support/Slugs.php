<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Categories and products share the flat URL space (/{slug}), so a slug must be unique across both
 * tables and must not shadow a fixed route.
 */
class Slugs
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** First URL segments used by fixed routes (current and planned). */
    public const RESERVED = [
        'account', 'admin', 'api', 'blog', 'brand', 'brands', 'build', 'cart', 'checkout', 'compare', 'contact',
        'enquiry', 'faq', 'filament', 'forgot-password', 'livewire', 'login', 'logout', 'offers', 'orders', 'page',
        'pc-builder', 'questions', 'recently-viewed', 'register', 'reviews', 'request-quote', 'reset-password', 'search', 'services', 'site-visit', 'sitemap', 'sitemaps', 'storage', 'track-order', 'up', 'warranty', 'wishlist',
    ];

    public static function isAvailable(string $slug, ?Model $ignore = null): bool
    {
        if (in_array($slug, [...self::RESERVED, config('technofix.admin_path')], true)) {
            return false;
        }

        $taken = fn (string $model) => $model::query()
            ->when(in_array(SoftDeletes::class, class_uses_recursive($model)), fn ($q) => $q->withTrashed())
            ->where('slug', $slug)
            ->when($ignore instanceof $model, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->exists();

        return ! $taken(Category::class) && ! $taken(Product::class);
    }

    /** A free slug based on $text: "name", "name-2", "name-3"… */
    public static function unique(string $text, ?Model $ignore = null): string
    {
        $base = Str::limit(Str::slug($text), 180, '') ?: 'item';
        $slug = $base;

        for ($i = 2; ! self::isAvailable($slug, $ignore); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Validation rule for admin slug fields.
     */
    public static function rule(?Model $ignore): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignore) {
            if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
                $fail('Use lowercase letters, numbers and single hyphens only (e.g. gaming-laptop).');
            } elseif (! self::isAvailable($value, $ignore)) {
                $fail('This URL is already used by another category or product, or by a site page.');
            }
        };
    }
}
