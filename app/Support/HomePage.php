<?php

namespace App\Support;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\HomeSection;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the home page from Admin → Content → Home page: each switched-on section, in order, with the data its
 * template (resources/views/storefront/home/{type}.blade.php) needs. Sections with nothing to show are skipped.
 */
class HomePage
{
    public function __construct(protected Catalog $catalog) {}

    /**
     * @return Collection<int, array{type: string, section: HomeSection, data: array<string, mixed>}>
     */
    public function sections(): Collection
    {
        return HomeSection::query()->where('is_active', true)->orderBy('sort')->orderBy('id')->get()
            ->map(fn (HomeSection $section) => ['type' => $section->type, 'section' => $section, 'data' => $this->data($section)])
            ->filter(fn (array $item) => $item['data'] !== null)
            ->values();
    }

    /**
     * @return array<string, mixed>|null null = nothing to show
     */
    protected function data(HomeSection $section): ?array
    {
        return match ($section->type) {
            'hero' => $this->hero($section),
            'ticker' => $this->ticker($section),
            'categories' => $this->categories($section),
            'departments' => $this->departments($section),
            'deals' => $this->deals(),
            'product_tabs' => $this->productTabs($section),
            'category_row' => $this->categoryRow($section),
            'services' => ($services = Service::active()->limit(6)->get())->isEmpty() ? null : ['services' => $services],
            'brands' => ($brands = $this->catalog->featuredBrands())->isEmpty() ? null
                : ['brands' => $brands->map(fn (Brand $brand) => ['name' => $brand->name, 'url' => $brand->url()])->all()],
            'pc_builder', 'about' => [],
            default => null,
        };
    }

    /** Scrolling notice: messages that are filled in and not past their "show until" time; none → section hidden. */
    protected function ticker(HomeSection $section): ?array
    {
        $messages = collect($section->setting('messages', []))
            ->filter(fn ($message) => is_array($message) && filled($message['text'] ?? null))
            ->reject(fn (array $message) => filled($message['until'] ?? null) && rescue(fn () => Carbon::parse($message['until'])->isPast(), false, false))
            ->map(fn (array $message) => ['text' => trim($message['text']), 'url' => Banner::link($message['url'] ?? null)])
            ->values();

        if ($messages->isEmpty()) {
            return null;
        }

        // Short notices are repeated until one round is wider than a big screen (~240 characters), so the loop has no gap.
        $length = $messages->sum(fn (array $message) => mb_strlen($message['text']) + 6);
        $repeat = (int) ceil(240 / max(1, $length));
        $messages = collect(array_merge(...array_fill(0, $repeat, $messages->all())));
        $length *= $repeat;

        // Scroll speed in characters per second, so long and short notices move at the same pace.
        $pace = ['slow' => 6, 'normal' => 9, 'fast' => 13][$section->setting('speed', 'normal')] ?? 9;

        return [
            'messages' => $messages->all(),
            'label' => $section->setting('label', 'Notice'),
            'icon' => $section->setting('icon') ?: 'speakerphone',
            'seconds' => max(12, (int) round($length / $pace)),
        ];
    }

    protected function hero(HomeSection $section): ?array
    {
        $slides = Banner::heroSlides();
        $zones = $section->setting('brand_zones', true) ? $this->catalog->brandZones()->map(fn (Brand $brand) => [
            'name' => $brand->name,
            'tagline' => $brand->zone_tagline,
            'url' => $brand->url(),
            'theme' => ZoneThemes::get($brand->zone_theme),
            'logo' => $brand->logoUrl(),
        ]) : collect();

        return $slides->isEmpty() && $zones->isEmpty() ? null : ['slides' => $slides, 'brandZones' => $zones];
    }

    protected function categories(HomeSection $section): ?array
    {
        $tiles = collect($section->setting('category_ids', []))->map(fn ($id) => $this->catalog->categoryById((int) $id))->filter()->values();

        return $tiles->isEmpty() ? null : ['tiles' => $tiles];
    }

    protected function departments(HomeSection $section): ?array
    {
        $cards = collect(['big', 'wide', 'small1', 'small2'])->mapWithKeys(function (string $slot) use ($section) {
            $category = ($id = $section->setting("{$slot}.category_id")) ? $this->catalog->categoryById((int) $id) : null;

            return [$slot => $category ? [
                'category' => $category,
                'heading' => $section->setting("{$slot}.heading") ?: $category->name,
                'text' => $section->setting("{$slot}.text"),
                ...$this->catalog->stats($category),
            ] : null];
        });

        return $cards->contains(null) ? null : ['cards' => $cards];
    }

    /**
     * The running campaign shown on the home page (the one ending soonest), with a real countdown; otherwise products
     * marked "Hot deal", without a countdown.
     */
    protected function deals(): ?array
    {
        $campaign = Campaign::query()->live()->where('show_on_home', true)->orderBy('ends_at')->first();

        if ($campaign) {
            $products = $this->catalog->products()->whereIn('id', $campaign->items()->pluck('product_id'))->get()
                ->filter(fn ($product) => $product->deal())
                ->sortBy(fn ($product) => $campaign->items->firstWhere('product_id', $product->id)?->sort)
                ->values();

            if ($products->isNotEmpty()) {
                return ['products' => $products, 'endsAt' => $campaign->ends_at, 'campaign' => $campaign];
            }
        }

        $products = $this->catalog->flagged('deal');

        return $products->isEmpty() ? null : ['products' => $products, 'endsAt' => null, 'campaign' => null];
    }

    protected function productTabs(HomeSection $section): ?array
    {
        $tabs = collect($section->setting('tabs', []))
            ->filter(fn ($tab) => isset(HomeSection::PRODUCT_SOURCES[$tab['source'] ?? '']))
            ->mapWithKeys(fn (array $tab, $i) => ['tab'.$i => [
                'label' => $tab['label'] ?: HomeSection::PRODUCT_SOURCES[$tab['source']],
                'products' => $this->catalog->flagged($tab['source'], (int) $section->setting('limit', 10)),
            ]])
            ->filter(fn (array $tab) => $tab['products']->isNotEmpty());

        return $tabs->isEmpty() ? null : ['tabs' => $tabs->all()];
    }

    protected function categoryRow(HomeSection $section): ?array
    {
        $category = ($id = $section->setting('category_id')) ? $this->catalog->categoryById((int) $id) : null;

        if (! $category) {
            return null;
        }

        $products = $this->catalog->productsIn($category)
            ->orderByRaw(Catalog::purchasableFirstSql())->orderByDesc('is_featured')->orderByDesc('id')
            ->limit((int) $section->setting('limit', 10))->get();

        return $products->isEmpty() ? null : ['category' => $category, 'products' => $products];
    }

    /** "Solid State Drive (SSD)" → "SSD" for small tiles. */
    public static function shortName(string $name): string
    {
        return preg_match('/\(([A-Za-z0-9 .\/-]{2,12})\)\s*$/', $name, $m) ? $m[1] : $name;
    }
}
