<?php

namespace App\Support;

use App\Models;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Finished storefront pages for guests (route middleware `page.cache`).
 *
 * Every guest gets the same HTML: the cart, wishlist and compare list live in the browser, so the only personal
 * part is the CSRF token — it is stored as a placeholder and put back when the page is served. Signed-in visitors
 * (customers and staff), pages showing a form message (flash data) and addresses with filters or other query
 * values are always rendered fresh. Saving almost anything in the admin (models + settings) clears the whole store.
 */
class PageCache
{
    public const TOKEN = '__tf_csrf_token__';

    /** Query values that don't change the page (ad and analytics tags). */
    public const IGNORED_PARAMS = ['fbclid', 'gclid', 'gbraid', 'wbraid', 'ttclid', 'msclkid', 'yclid', '_ga', 'ref'];

    /** Query values a cached address may have; anything else (search, filters, sort) is rendered fresh. */
    public const KEPT_PARAMS = ['page'];

    /** Models that never show on a cached page — saving them leaves the cache alone. */
    public const UNRELATED = [
        Models\Activity::class, Models\CheckoutDraft::class, Models\Coupon::class, Models\Customer::class,
        Models\CustomerAddress::class, Models\Lead::class, Models\NotFoundUrl::class, Models\Order::class,
        Models\OrderItem::class, Models\OrderStatusHistory::class, Models\Payment::class, Models\Redirect::class,
        Models\Role::class, Models\SavedBuild::class, Models\StockAlert::class, Models\StockMovement::class,
        Models\StockReceipt::class, Models\StockReceiptItem::class, Models\Supplier::class, Models\User::class,
        Models\WarrantyClaim::class, Models\WarrantyClaimEvent::class,
    ];

    protected static bool $pending = false;

    public static function enabled(): bool
    {
        return (bool) config('technofix.page_cache.enabled');
    }

    public static function store(): Repository
    {
        return Cache::store(config('technofix.page_cache.store'));
    }

    /** Cache key for this address, or null when its query string rules caching out. */
    public static function key(Request $request): ?string
    {
        $query = collect($request->query())
            ->reject(fn ($value, $name) => in_array($name, self::IGNORED_PARAMS, true) || str_starts_with((string) $name, 'utm_'));

        if ($query->keys()->diff(self::KEPT_PARAMS)->isNotEmpty() || $query->contains(fn ($value) => ! is_string($value) || ! ctype_digit($value))) {
            return null;
        }

        // A new asset build (deploy) starts a fresh set of pages.
        $build = (int) @filemtime(public_path('build/manifest.json'));

        return 'page:'.sha1($build.'|'.$request->getSchemeAndHttpHost().$request->getPathInfo().'?'.http_build_query($query->sortKeys()->all()));
    }

    /** Whether this visit may be served from (and saved to) the cache. */
    public static function applies(Request $request): bool
    {
        return self::enabled()
            && $request->isMethod('GET')
            && ! $request->ajax()
            && ! self::hasFlash($request)
            && ! auth('web')->check()
            && ! auth('customer')->check()
            && self::key($request) !== null;
    }

    public static function get(Request $request): ?Response
    {
        try {
            $page = self::store()->get(self::key($request));
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (! is_array($page) || ($html = @gzinflate($page['html'] ?? '')) === false) {
            return null;
        }

        return response(str_replace(self::TOKEN, $request->session()->token(), $html), 200, [
            'Content-Type' => $page['type'],
            'X-Page-Cache' => 'hit',
        ]);
    }

    public static function put(Request $request, Response $response): void
    {
        $html = $response->getContent();
        $token = $request->session()->token();

        if ($response->getStatusCode() !== 200
            || ! is_string($html) || $html === ''
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || $response->headers->getCookies() !== []
            || self::hasFlash($request)) {
            return;
        }

        try {
            self::store()->put(self::key($request), [
                'html' => gzdeflate($token ? str_replace($token, self::TOKEN, $html) : $html, 6),
                'type' => $response->headers->get('Content-Type'),
            ], self::ttl());
            $response->headers->set('X-Page-Cache', 'miss');
        } catch (Throwable $e) {
            report($e); // a full disk must not break the page
        }
    }

    /** Seconds a page may be kept: the configured time, cut short when a flash sale or banner starts or ends. */
    public static function ttl(): int
    {
        $ttl = max(60, (int) config('technofix.page_cache.minutes') * 60);
        $next = self::nextScheduledChange();

        return $next ? max(10, min($ttl, (int) ceil(now()->diffInSeconds($next)))) : $ttl;
    }

    public static function nextScheduledChange(): ?Carbon
    {
        $times = [];
        foreach ([Models\Campaign::class, Models\Banner::class] as $model) {
            foreach (['starts_at', 'ends_at'] as $column) {
                $times[] = $model::query()->where('is_active', true)->where($column, '>', now())->min($column);
            }
        }

        $times = array_filter($times);

        return $times ? Carbon::parse(min($times)) : null;
    }

    /** A model was saved or deleted: clear the cache unless it never shows on the storefront. */
    public static function modelChanged(mixed $model): void
    {
        if (($model instanceof Model && Str::startsWith($model::class, 'App\\Models\\') && ! in_array($model::class, self::UNRELATED, true))
            || $model instanceof Media) {
            self::invalidate();
        }
    }

    /** Clear the cache — once, after the response, when inside a web request (a bulk import saves hundreds of rows). */
    public static function invalidate(): void
    {
        if (! self::enabled() || self::$pending) {
            return;
        }

        if (app()->runningInConsole()) {
            self::flush();

            return;
        }

        self::$pending = true;
        app()->terminating(function () {
            self::$pending = false;
            self::flush();
        });
    }

    public static function flush(): void
    {
        try {
            self::store()->flush();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Delete expired pages (the file store only removes them when read again). Returns the number deleted. */
    public static function prune(): int
    {
        $config = config('cache.stores.'.config('technofix.page_cache.store'));
        if (($config['driver'] ?? null) !== 'file' || ! is_dir($config['path'])) {
            return 0;
        }

        $deleted = 0;
        foreach (File::allFiles($config['path']) as $file) {
            $handle = @fopen($file->getPathname(), 'r');
            $expires = $handle ? (int) fread($handle, 10) : 0;
            $handle && fclose($handle);

            if ($expires && $expires < now()->getTimestamp() && @unlink($file->getPathname())) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /** Flash data (form errors, old input, "thanks" messages, tracking events) makes a page personal. */
    protected static function hasFlash(Request $request): bool
    {
        if (! $request->hasSession()) {
            return true;
        }

        $session = $request->session();

        return $session->get('_flash.new', []) !== [] || $session->get('_flash.old', []) !== [];
    }
}
