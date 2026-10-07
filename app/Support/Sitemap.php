<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * XML sitemaps: /sitemap.xml (index) → /sitemaps/{pages|categories|brands|products-N}.xml. Built from the live
 * catalog and kept for an hour, so new products appear by themselves. Only pages search engines may index.
 */
class Sitemap
{
    /** Products per file (Google allows 50,000; smaller files are kinder to shared hosting). */
    public const PER_FILE = 2000;

    private const CACHE_MINUTES = 60;

    public function __construct(private Catalog $catalog) {}

    /** @return list<string> file names without ".xml" */
    public function files(): array
    {
        $productFiles = (int) ceil(Product::query()->published()->count() / self::PER_FILE);

        return ['pages', 'categories', 'brands', ...array_map(fn (int $n) => "products-{$n}", $productFiles ? range(1, $productFiles) : [])];
    }

    public function index(): string
    {
        return Cache::remember('sitemap.index', now()->addMinutes(self::CACHE_MINUTES), function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($this->files() as $name) {
                $xml .= '  <sitemap><loc>'.self::e(route('sitemap.file', $name)).'</loc></sitemap>'."\n";
            }

            return $xml.'</sitemapindex>'."\n";
        });
    }

    /** One sitemap file, or null when there is no such file. */
    public function file(string $name): ?string
    {
        if (! in_array($name, $this->files(), true)) {
            return null;
        }

        return Cache::remember("sitemap.{$name}", now()->addMinutes(self::CACHE_MINUTES), fn () => self::urlset($this->entries($name)));
    }

    /** How many addresses each part lists (Admin → Settings → SEO & sharing). */
    public function counts(): array
    {
        return [
            'pages' => count($this->entries('pages')),
            'categories' => count($this->entries('categories')),
            'brands' => count($this->entries('brands')),
            'products' => Product::query()->published()->count(),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod?: ?Carbon, image?: ?string}>
     */
    protected function entries(string $name): array
    {
        return match (true) {
            $name === 'pages' => $this->pages(),
            $name === 'categories' => $this->categories(),
            $name === 'brands' => $this->brands(),
            default => $this->products((int) str_replace('products-', '', $name)),
        };
    }

    protected function pages(): array
    {
        $fixed = collect(['home', 'offers', 'services.index', 'pc-builder', 'brand.index', 'faq', 'contact', 'quote', 'site-visit'])
            ->filter(fn (string $route) => Route::has($route))
            ->map(fn (string $route) => ['loc' => route($route)]);

        return $fixed
            ->concat(Service::active()->orderBy('sort')->get()->map(fn (Service $service) => ['loc' => $service->url(), 'lastmod' => $service->updated_at]))
            ->concat(Page::published()->orderBy('sort')->get()->map(fn (Page $page) => ['loc' => $page->url(), 'lastmod' => $page->updated_at]))
            ->values()
            ->all();
    }

    /** Active categories, then each brand's page in a category it has products in ("/motherboard/gigabyte"). */
    protected function categories(): array
    {
        $latest = Product::query()->published()->toBase()
            ->selectRaw('category_id, brand_id, max(updated_at) as last')
            ->groupBy('category_id', 'brand_id')
            ->get();
        $brands = CatalogCache::data()['brands'];

        $categories = collect(CatalogCache::data()['categories'])
            ->map(fn (array $row) => $this->catalog->categoryById($row['id']))
            ->filter()
            ->map(fn ($category) => ['loc' => $category->url, 'lastmod' => self::date($latest->where('category_id', $category->id)->max('last'))]);

        $pairs = $latest
            ->filter(fn ($row) => $row->brand_id && ($brands[$row->brand_id]['active'] ?? false) && $this->catalog->categoryById((int) $row->category_id))
            ->map(fn ($row) => [
                'loc' => Catalog::categoryBrandUrl($this->catalog->categoryById((int) $row->category_id)->slug, $brands[$row->brand_id]['slug']),
                'lastmod' => self::date($row->last),
            ]);

        return $categories->concat($pairs)->values()->all();
    }

    /** Active brands with published products. */
    protected function brands(): array
    {
        $latest = Product::query()->published()->toBase()->selectRaw('brand_id, max(updated_at) as last')->groupBy('brand_id')->pluck('last', 'brand_id');

        return Brand::active()->whereIn('id', $latest->keys())->orderBy('name')->get(['id', 'slug'])
            ->map(fn (Brand $brand) => ['loc' => route('brand.show', $brand->slug), 'lastmod' => self::date($latest[$brand->id])])
            ->all();
    }

    protected function products(int $file): array
    {
        return Product::query()->published()->with('media')->orderBy('id')->forPage($file, self::PER_FILE)->get(['id', 'slug', 'updated_at'])
            ->map(fn (Product $product) => [
                'loc' => $product->url,
                'lastmod' => $product->updated_at,
                'image' => $product->getFirstMediaUrl('images', 'large') ?: null,
            ])
            ->all();
    }

    /**
     * @param  list<array{loc: string, lastmod?: ?Carbon, image?: ?string}>  $entries
     */
    protected static function urlset(array $entries): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";

        foreach ($entries as $entry) {
            $xml .= '  <url><loc>'.self::e($entry['loc']).'</loc>'
                .(($entry['lastmod'] ?? null) ? '<lastmod>'.$entry['lastmod']->toAtomString().'</lastmod>' : '')
                .(($entry['image'] ?? null) ? '<image:image><image:loc>'.self::e($entry['image']).'</image:loc></image:image>' : '')
                .'</url>'."\n";
        }

        return $xml.'</urlset>'."\n";
    }

    private static function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
