<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Service;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-managed footer links, cached until a page or service changes (see the models' booted()).
 * Stores slugs, not URLs, so the cache works on any host.
 */
class SiteNav
{
    public const CACHE_KEY = 'site-nav.footer';

    /**
     * @return array{pages: array<string, list<array{title: string, slug: string}>>, services: list<array{name: string, slug: string}>}
     */
    public static function footer(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => [
            'pages' => Page::published()
                ->whereNotNull('footer_group')
                ->orderBy('sort')
                ->get(['title', 'slug', 'footer_group'])
                ->groupBy('footer_group')
                ->map(fn ($pages) => $pages->map(fn (Page $page) => ['title' => $page->title, 'slug' => $page->slug])->values()->all())
                ->all(),
            'services' => Service::active()
                ->limit(5)
                ->get(['name', 'slug'])
                ->map(fn (Service $service) => ['name' => $service->name, 'slug' => $service->slug])
                ->all(),
        ]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
