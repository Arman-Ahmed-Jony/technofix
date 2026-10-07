<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Product;
use App\Settings\SeoSettings;
use App\Settings\StoreSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Page titles, meta descriptions, link previews and structured data (JSON-LD). Texts come from Admin → Settings →
 * SEO; a page's own SEO fields (products, categories, brands, pages, services) always win over the templates.
 */
class Seo
{
    /** Placeholders the templates may use, per template. `{site}` and `{year}` work everywhere. */
    public const TOKENS = [
        'product' => ['name', 'brand', 'category', 'price', 'warranty'],
        'category' => ['name', 'count', 'min_price'],
        'brand' => ['name'],
        'category_brand' => ['brand', 'category'],
    ];

    /** Fixed storefront pages whose title/description the owner can change (layout `page-key`). */
    public const PAGES = [
        'offers' => ['label' => 'Offers & deals', 'title' => 'Offers & deals', 'description' => 'Flash sales, Happy Hour prices and hot deals on laptops, PC components, CCTV and accessories — with cash on delivery across Bangladesh.'],
        'services' => ['label' => 'Repair & services', 'title' => 'Repair & IT Services', 'description' => 'Laptop and desktop repair, CCTV installation, networking setup, printer service and data recovery in Bangladesh. Call or WhatsApp for a quote.'],
        'brands' => ['label' => 'All brands', 'title' => 'All brands', 'description' => 'Shop genuine products from the official brands we carry — laptops, components, CCTV, networking and accessories.'],
        'faq' => ['label' => 'FAQ', 'title' => 'Frequently asked questions', 'description' => 'Answers about ordering, delivery, cash on delivery, warranty, returns and our repair services.'],
        'contact' => ['label' => 'Contact', 'title' => 'Contact us', 'description' => 'Call, WhatsApp, email or visit us. Send us a message and our team will get back to you.'],
        'request-quote' => ['label' => 'Request a quote', 'title' => 'Request a quote', 'description' => 'Get prices for bulk and corporate orders — laptops, PC components, CCTV, networking and accessories. We reply with prices, availability and delivery time.'],
        'site-visit' => ['label' => 'Free site visit', 'title' => 'Book a free site visit', 'description' => 'Book a free site visit for CCTV, networking, access control or intercom installation. A technician surveys your place and gives you a clear quote.'],
        'track-order' => ['label' => 'Track order', 'title' => 'Track your order', 'description' => 'Check the status of your order with your order number and mobile number.'],
        'warranty' => ['label' => 'Warranty status', 'title' => 'Check your warranty claim', 'description' => 'Check the status of the products you gave us for warranty service — just enter your mobile number.'],
    ];

    /** schema.org business types offered in the admin. */
    public const BUSINESS_TYPES = [
        'ElectronicsStore' => 'Electronics store',
        'ComputerStore' => 'Computer store',
        'Store' => 'Store (general)',
        'Organization' => 'Company (no shop to visit)',
    ];

    /** Cap for template descriptions — Google shows about 155–160 characters and shortens the rest itself. */
    private const DESCRIPTION_MAX = 300;

    public function __construct(private SeoSettings $settings, private StoreSettings $store) {}

    public function siteName(): string
    {
        return $this->settings->site_name;
    }

    /** The full <title>: "{title} | {site}", or the home page title. */
    public function title(?string $title): string
    {
        return filled($title) ? $title.' | '.$this->siteName() : $this->settings->home_title;
    }

    public function defaultDescription(): string
    {
        return $this->settings->home_description;
    }

    /**
     * @return array{title: string, description: string}
     */
    public function page(string $key): array
    {
        $own = $this->settings->pages[$key] ?? [];

        return [
            'title' => ($own['title'] ?? null) ?: self::PAGES[$key]['title'],
            'description' => ($own['description'] ?? null) ?: self::PAGES[$key]['description'],
        ];
    }

    /**
     * @return array{title: string, metaDescription: string}
     */
    public function product(Product $product): array
    {
        return $this->meta('product', $product->meta_title, $product->meta_description, [
            'name' => $product->name,
            'brand' => $product->brand_name,
            'category' => $product->category_name,
            'price' => $product->sale_price !== null ? Money::format($product->sale_price) : 'call for today’s price',
            'warranty' => Warranty::label($product->warranty),
        ]);
    }

    /**
     * @param  array{count: int, from: ?int}  $stats
     * @return array{title: string, metaDescription: string}
     */
    public function category(Fluent $category, array $stats, ?string $title = null, ?string $description = null): array
    {
        return $this->meta('category', $title, $description, [
            'name' => $category->name,
            'count' => (string) $stats['count'],
            'min_price' => Money::format($stats['from']),
        ]);
    }

    /**
     * @return array{title: string, metaDescription: string}
     */
    public function brand(Brand|Fluent $brand, ?string $title = null, ?string $description = null): array
    {
        return $this->meta('brand', $title, $description, ['name' => $brand->name]);
    }

    /**
     * @return array{title: string, metaDescription: string}
     */
    public function categoryBrand(Fluent $category, Brand|Fluent $brand, ?string $title = null, ?string $description = null): array
    {
        return $this->meta('category_brand', $title, $description, ['brand' => $brand->name, 'category' => $category->name]);
    }

    /** A template filled in for a real product from the catalog (the admin's live example). */
    public function example(string $type, string $template): string
    {
        $product = Product::query()->published()->whereNotNull('price')->latest('id')->first();

        return $this->fill($template, match ($type) {
            'product' => [
                'name' => $product?->name ?? 'Gigabyte B760M DS3H DDR4',
                'brand' => $product?->brand_name ?? 'Gigabyte',
                'category' => $product?->category_name ?? 'Motherboard',
                'price' => Money::format($product?->sale_price ?? 15500),
                'warranty' => Warranty::label($product?->warranty ?? '3 Years'),
            ],
            'category' => ['name' => $product?->category_name ?? 'Motherboard', 'count' => '24', 'min_price' => Money::format(8500)],
            'brand' => ['name' => $product?->brand_name ?? 'Gigabyte'],
            'category_brand' => ['brand' => $product?->brand_name ?? 'Gigabyte', 'category' => $product?->category_name ?? 'Motherboard'],
            default => [],
        });
    }

    /**
     * Fills a template's placeholders and tidies what empty ones leave behind ("from  at", " — .").
     *
     * @param  array<string, ?string>  $tokens
     */
    public function fill(string $template, array $tokens): string
    {
        $tokens += ['site' => $this->siteName(), 'year' => (string) now()->year];
        $text = preg_replace_callback('/\{(\w+)\}/', fn (array $m) => array_key_exists($m[1], $tokens) ? (string) $tokens[$m[1]] : $m[0], $template);

        $text = preg_replace('/\s+/u', ' ', $text);
        $text = preg_replace('/\s*[,:;—–]\s*(?=[.,!?]|$)/u', '', $text); // a separator left before punctuation
        $text = preg_replace('/\s+([,.:;!?])/u', '$1', $text);
        $text = preg_replace('/([.,])(?:\s*[.,])+/u', '$1', $text);

        return trim($text, ' ,;:—–');
    }

    /** The default picture for link previews (Admin → Settings → SEO, else the built-in one). */
    public function shareImage(): string
    {
        return $this->settings->share_image
            ? Storage::disk('public')->url($this->settings->share_image)
            : asset('images/brand/share.jpg');
    }

    /** Business details + site name for Google (home page). */
    public function homeSchema(): HtmlString
    {
        $home = url('/');

        return self::jsonLd([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => $this->settings->business_type,
                    '@id' => $home.'#business',
                    'name' => $this->siteName(),
                    'url' => $home,
                    'logo' => \App\Support\Brand::appIcon(), // (Brand here = App\Models\Brand)
                    'image' => $this->shareImage(),
                    'telephone' => $this->store->hotline,
                    'email' => $this->store->email ?: null,
                    'address' => $this->store->address ? ['@type' => 'PostalAddress', 'streetAddress' => $this->store->address, 'addressCountry' => 'BD'] : null,
                    'priceRange' => $this->settings->business_type === 'Organization' ? null : '৳৳',
                    'currenciesAccepted' => $this->settings->business_type === 'Organization' ? null : 'BDT',
                    'sameAs' => array_values($this->store->socialLinks()) ?: null,
                ]),
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'url' => $home,
                    'name' => $this->siteName(),
                    'publisher' => ['@id' => $home.'#business'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param  list<array{label: string, url: ?string}>  $items  as given to <x-breadcrumbs> (home is added here)
     */
    public static function breadcrumbSchema(array $items): HtmlString
    {
        $crumbs = [['label' => 'Home', 'url' => route('home')], ...$items];

        return self::jsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn (array $crumb, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['label'],
                'item' => $crumb['url'] ?? url()->current(),
            ])->all(),
        ]);
    }

    /** A <script type="application/ld+json"> block (safe to print: "<" is escaped). */
    public static function jsonLd(array $data): HtmlString
    {
        return new HtmlString('<script type="application/ld+json">'.json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).'</script>');
    }

    /**
     * @param  array<string, ?string>  $tokens
     * @return array{title: string, metaDescription: string}
     */
    private function meta(string $type, ?string $title, ?string $description, array $tokens): array
    {
        return [
            'title' => $title ?: $this->fill($this->settings->{$type.'_title'}, $tokens),
            'metaDescription' => $description ?: Str::limit($this->fill($this->settings->{$type.'_description'}, $tokens), self::DESCRIPTION_MAX, '…', preserveWords: true),
        ];
    }
}
