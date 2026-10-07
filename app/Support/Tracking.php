<?php

namespace App\Support;

use App\Jobs\SendMetaEvent;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Settings\TrackingSettings;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Analytics and ad pixels (Admin → Settings → Tracking & pixels).
 *
 * The page collects events in GA4's format (view_item, add_to_cart, purchase, …); resources/js/tracking.js sends each
 * one to GTM's dataLayer, GA4, the Meta pixel and the TikTok pixel in their own format. Events known on the server are
 * pushed here for this page (`push`) or the next one after a redirect (`flash`). Purchases, leads and sign-ups also go
 * to Meta's Conversions API from the server (queued), with the same event ID so Meta counts each one once.
 */
class Tracking
{
    public const CURRENCY = 'BDT';

    /** Meta Graph API version for the Conversions API. */
    public const GRAPH_VERSION = 'v23.0';

    public const LOAD_OPTIONS = [
        'after_load' => 'After the page has loaded (recommended)',
        'on_interaction' => 'When the visitor first scrolls, taps or types (fastest pages, may miss visitors who leave at once)',
        'immediately' => 'Straight away (most complete data, slower pages)',
    ];

    /** @var list<array{event: string, params: array, id: ?string}> */
    private array $events = [];

    public function __construct(private TrackingSettings $settings) {}

    public function enabled(): bool
    {
        return (bool) ($this->settings->gtm_id || $this->settings->ga4_id || $this->settings->meta_pixel_id || $this->settings->tiktok_pixel_id);
    }

    public function capiEnabled(): bool
    {
        return filled($this->settings->meta_pixel_id) && filled($this->settings->meta_capi_token);
    }

    /** An event for this page (sent by the browser). `items: 'cart'` = the browser cart's contents. */
    public function push(string $event, array $params = [], ?string $eventId = null): void
    {
        if ($this->enabled()) {
            $this->events[] = ['event' => $event, 'params' => $params, 'id' => $eventId];
        }
    }

    /** An event for the next page (after a redirect, e.g. a form that returns to the same page). */
    public function flash(string $event, array $params = [], ?string $eventId = null): void
    {
        if ($this->enabled()) {
            session()->flash('tracking', [...(array) session('tracking', []), ['event' => $event, 'params' => $params, 'id' => $eventId]]);
        }
    }

    /**
     * Events for the browser on this page: flashed ones from the previous request, then this page's.
     *
     * @return list<array{event: string, params: array, id: ?string}>
     */
    public function events(): array
    {
        return [...(array) session('tracking', []), ...$this->events];
    }

    /** What the browser loader needs (IDs, when to load, the signed-in customer for better ad matching). */
    public function config(?Customer $customer = null): array
    {
        $user = $customer ? array_filter([
            'em' => self::hash($customer->email),
            'ph' => self::hash(self::intlPhone($customer->phone)),
            'external_id' => self::hash(self::intlPhone($customer->phone)),
        ]) : [];

        return array_filter([
            'gtm' => $this->settings->gtm_id,
            'ga4' => $this->settings->ga4_id,
            'pixel' => $this->settings->meta_pixel_id,
            'tiktok' => $this->settings->tiktok_pixel_id,
            'load' => $this->settings->load_scripts,
            'currency' => self::CURRENCY,
            'user' => $user ?: null,
        ]);
    }

    /** A value as a JavaScript literal inside <script> ("<", ">" and "&" escaped, so it can't close the tag). */
    public static function js(mixed $value): HtmlString
    {
        return new HtmlString(json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** A product in GA4's item format (item_id = product id, used by every platform). */
    public static function item(Product $product, int $quantity = 1, ?int $price = null): array
    {
        return array_filter([
            'item_id' => (string) $product->id,
            'item_name' => $product->name,
            'item_brand' => $product->brand_name ?: null,
            'item_category' => $product->category_name ?: null,
            'price' => $price ?? $product->sale_price,
            'quantity' => $quantity,
        ], fn ($value) => $value !== null);
    }

    public function purchase(Order $order, Request $request): void
    {
        $order->loadMissing('items.product');
        $eventId = 'order-'.$order->id;

        $this->flash('purchase', array_filter([
            'transaction_id' => $order->number,
            'currency' => self::CURRENCY,
            'value' => $order->total,
            'shipping' => $order->delivery_fee,
            'coupon' => $order->coupon_code,
            'items' => $order->items->map(fn ($line) => $line->product
                ? array_filter([...self::item($line->product, $line->qty, $line->price), 'item_variant' => $line->color], fn ($v) => $v !== null)
                : array_filter(['item_id' => (string) $line->product_id, 'item_name' => $line->name, 'item_variant' => $line->color, 'price' => $line->price, 'quantity' => $line->qty], fn ($v) => $v !== null))->all(),
        ], fn ($value) => $value !== null), $eventId);

        $this->sendToMeta('Purchase', $eventId, $request, route('checkout'), [
            'phone' => $order->phone, 'email' => $order->email, 'name' => $order->name, 'city' => $order->district,
        ], [
            'currency' => self::CURRENCY,
            'value' => $order->total,
            'order_id' => $order->number,
            'content_type' => 'product',
            'content_ids' => $order->items->map(fn ($line) => (string) $line->product_id)->all(),
            'contents' => $order->items->map(fn ($line) => ['id' => (string) $line->product_id, 'quantity' => $line->qty, 'item_price' => $line->price])->all(),
            'num_items' => $order->items->sum('qty'),
        ]);
    }

    /**
     * A storefront enquiry. The browser event is flashed for the page the form returns to, or — for forms sent without
     * a page load (`$flash = false`) — returned for the JSON response.
     *
     * @return array{event: string, params: array, id: string}|null
     */
    public function lead(Lead $lead, Request $request, bool $flash = true): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $event = ['event' => 'generate_lead', 'params' => ['lead_type' => $lead->type->value, 'form' => $lead->type->getLabel()], 'id' => 'lead-'.$lead->id];

        if ($flash) {
            $this->flash($event['event'], $event['params'], $event['id']);
        }

        $this->sendToMeta('Lead', $event['id'], $request, $lead->page_url, ['phone' => $lead->phone, 'email' => $lead->email, 'name' => $lead->name], [
            'content_name' => $lead->type->getLabel(),
            'content_category' => $lead->type->value,
        ]);

        return $event;
    }

    public function signUp(Customer $customer, Request $request): void
    {
        $eventId = 'signup-'.$customer->id;

        $this->flash('sign_up', ['method' => 'phone'], $eventId);
        $this->sendToMeta('CompleteRegistration', $eventId, $request, route('account.register'), [
            'phone' => $customer->phone, 'email' => $customer->email, 'name' => $customer->name,
        ], ['status' => 'registered']);
    }

    /**
     * Where the visitor came from (CaptureAttribution) plus Meta's click / browser IDs, kept on orders and leads so
     * server-side events can be matched to the ad later.
     */
    public static function attribution(Request $request): ?array
    {
        $attribution = array_filter([
            ...(array) ($request->hasSession() ? $request->session()->get('attribution') : []),
            'fbp' => Str::limit((string) $request->cookie('_fbp'), 120, '') ?: null,
            'fbc' => Str::limit((string) $request->cookie('_fbc'), 250, '') ?: null,
        ]);

        return $attribution ?: null;
    }

    /**
     * Queues one event for Meta's Conversions API: customer details hashed (SHA-256) as Meta requires; IP, browser and
     * Meta's cookies as they are.
     *
     * @param  array{phone?: ?string, email?: ?string, name?: ?string, city?: ?string}  $customer
     */
    protected function sendToMeta(string $event, string $eventId, Request $request, ?string $url, array $customer, array $custom): void
    {
        if (! $this->capiEnabled()) {
            return;
        }

        $attribution = self::attribution($request) ?? [];
        $fbc = $attribution['fbc'] ?? (isset($attribution['fbclid']) ? 'fb.1.'.(int) (strtotime($attribution['at'] ?? 'now') * 1000).'.'.$attribution['fbclid'] : null);
        $names = preg_split('/\s+/', trim((string) ($customer['name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $phone = self::intlPhone($customer['phone'] ?? null);

        SendMetaEvent::dispatch(array_filter([
            'event_name' => $event,
            'event_time' => now()->timestamp,
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $url,
            'user_data' => array_filter([
                'ph' => self::hash($phone),
                'em' => self::hash($customer['email'] ?? null),
                'fn' => self::hash($names[0] ?? null),
                'ln' => self::hash(count($names) > 1 ? end($names) : null),
                'ct' => self::hash(preg_replace('/[^a-z]/', '', Str::lower((string) ($customer['city'] ?? '')))),
                'country' => self::hash('bd'),
                'external_id' => self::hash($phone),
                'client_ip_address' => $request->ip(),
                'client_user_agent' => $request->userAgent(),
                'fbc' => $fbc,
                'fbp' => $attribution['fbp'] ?? null,
            ]),
            'custom_data' => array_filter($custom, fn ($value) => $value !== null && $value !== []),
        ]));
    }

    /** "01712345678" → "8801712345678" (the international format ad platforms match on). */
    public static function intlPhone(?string $phone): ?string
    {
        $phone = Phone::normalize($phone);

        return Phone::isValid($phone) ? '88'.$phone : null;
    }

    public static function hash(?string $value): ?string
    {
        $value = Str::lower(trim((string) $value));

        return $value === '' ? null : hash('sha256', $value);
    }
}
