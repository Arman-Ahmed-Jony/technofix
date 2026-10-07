<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Service;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGateways;
use App\Settings\DeliverySettings;
use App\Settings\SeoSettings;
use App\Settings\StoreSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * /llms.txt (llmstxt.org): a short Markdown guide to the shop for AI assistants — what it sells, how buying works,
 * where things are. Built from the catalog, services, pages and settings; kept for an hour.
 */
class LlmsTxt
{
    public function __construct(
        private SeoSettings $seo,
        private StoreSettings $store,
        private DeliverySettings $delivery,
        private PaymentGateways $payments,
        private Catalog $catalog,
    ) {}

    public function enabled(): bool
    {
        return $this->seo->llms_enabled;
    }

    public function text(): string
    {
        return Cache::remember('llms.txt', now()->addHour(), fn () => $this->build());
    }

    public static function flush(): void
    {
        Cache::forget('llms.txt');
    }

    protected function build(): string
    {
        $out = ['# '.$this->seo->site_name, '', '> '.Str::squish($this->seo->llms_intro), ''];

        $delivery = collect($this->delivery->active())
            ->map(fn (array $o) => $o['label'].' ('.($o['fee'] ? 'BDT '.number_format($o['fee']) : 'free').($o['eta'] ? ', '.$o['eta'] : '').')')
            ->join('; ');

        $out = [...$out, ...array_values(array_filter([
            '- Website: '.url('/'),
            '- Country: Bangladesh. Prices are in Bangladeshi taka (BDT).',
            '- Payment: '.collect($this->payments->available())->map(fn (PaymentGateway $g) => $g->label())->join(', ').'.',
            $delivery ? '- Delivery: '.$delivery.'.'.($this->delivery->free_delivery_over ? ' Free home delivery on orders over BDT '.number_format($this->delivery->free_delivery_over).'.' : '') : null,
            '- Hotline: '.$this->store->hotline.($this->store->whatsapp ? ' · WhatsApp: '.$this->store->whatsappUrl() : ''),
            $this->store->email ? '- Email: '.$this->store->email : null,
            $this->store->address ? '- Address: '.$this->store->address : null,
            $this->store->hours ? '- Opening hours: '.$this->store->hours : null,
            '- Product pages show the price, stock status, warranty and full specifications.',
        ])), ''];

        $out = [...$out, '## Product categories', ''];
        $tops = collect(CatalogCache::data()['categories'])->where('depth', 0)->map(fn (array $row) => $this->catalog->categoryById($row['id']))->filter();
        foreach ($tops as $top) {
            $out[] = $this->link($top->name, $top->url, $this->count($top));
            foreach ($this->catalog->children($top) as $child) {
                if ($count = $this->count($child)) {
                    $out[] = '  '.$this->link($child->name, $child->url, $count);
                }
            }
        }

        $services = Service::active()->orderBy('sort')->get();
        if ($services->isNotEmpty()) {
            $out = [...$out, '', '## Repair & IT services', ''];
            foreach ($services as $service) {
                $out[] = $this->link($service->name, $service->url(), $service->summary);
            }
        }

        $help = array_filter([
            'pc-builder' => ['PC Builder', 'Pick compatible parts for a custom PC and see the total price.'],
            'offers' => ['Offers & deals', 'Running flash sales and discounted products.'],
            'quote' => ['Request a quote', 'Bulk and corporate orders.'],
            'site-visit' => ['Free site visit', 'Book a technician to survey a CCTV or network installation.'],
            'orders.track' => ['Track an order', 'With the order number and mobile number.'],
            'contact' => ['Contact', 'Phone, WhatsApp, email and the contact form.'],
        ], fn (string $route) => Route::has($route), ARRAY_FILTER_USE_KEY);
        $out = [...$out, '', '## Buying help', ''];
        foreach ($help as $route => [$label, $note]) {
            $out[] = $this->link($label, route($route), $note);
        }

        $out = [...$out, '', '## Policies & answers', ''];
        foreach (Page::published()->orderBy('sort')->get() as $page) {
            $out[] = $this->link($page->title, $page->url(), $page->excerpt);
        }
        if (Route::has('faq')) {
            $out[] = $this->link('FAQ', route('faq'), 'Ordering, delivery, cash on delivery, warranty and returns.');
        }

        $out = [...$out, '', '## Optional', '',
            $this->link('All brands', route('brand.index')),
            $this->link('Sitemap', route('sitemap'), 'Every product, category and brand page.'),
        ];

        return implode("\n", $out)."\n";
    }

    protected function count(Fluent $category): ?string
    {
        $count = $this->catalog->stats($category)['count'];

        return $count ? $count.' '.Str::plural('product', $count) : null;
    }

    protected function link(string $label, string $url, ?string $note = null): string
    {
        return '- ['.str_replace(['[', ']'], '', $label).']('.$url.')'.(filled($note) ? ': '.Str::squish($note) : '');
    }
}
