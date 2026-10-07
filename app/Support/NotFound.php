<?php

namespace App\Support;

use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Runs when nothing answers an address (every 404 on the storefront, see bootstrap/app.php): follows a redirect
 * from Admin → Content → Redirects, or logs the address under Broken links. Normal page views never get here,
 * so redirects cost nothing until a page is actually missing.
 */
class NotFound
{
    /** Scanner / bot junk and missing files — not worth listing (checked against the lower-case path). */
    private const IGNORE = [
        '~^/(wp-admin|wp-login|wp-content|wp-includes|wp-json|xmlrpc|cgi-bin|phpmyadmin|pma|vendor|storage|build|livewire|filament)(/|\.|$)~',
        '~/\.~', // dotfiles: /.env, /.git/…
        '~\.(js|css|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|eot|txt|xml|json|zip|sql|bak|env|ini|log|yml|yaml)$~',
        '~(?<!^/index)\.php$~', // probes for random .php files (an old /index.php?… link is kept)
    ];

    /** Removed from logged addresses, so one broken link isn't listed once per ad click. */
    private const TRACKING = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid', 'msclkid'];

    public static function handle(Request $request): ?RedirectResponse
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        $address = Redirect::normalize($request->getRequestUri());
        $path = Str::before($address, '?');
        $admin = '/'.trim((string) config('technofix.admin_path'), '/');

        if ($path === $admin || str_starts_with($path, $admin.'/') || $request->expectsJson()) {
            return null;
        }

        if ($match = Redirect::match($address, (string) $request->getQueryString())) {
            [$redirect, $target] = $match;
            $redirect->recordHit();

            return redirect()->to($target, $redirect->status);
        }

        self::log($request, $path);

        return null;
    }

    public static function log(Request $request, string $path): void
    {
        if (collect(self::IGNORE)->contains(fn (string $pattern) => preg_match($pattern, $path))) {
            return;
        }

        parse_str((string) $request->getQueryString(), $params);
        $params = array_diff_key($params, array_flip(self::TRACKING));
        $address = Str::limit($path.($params ? '?'.http_build_query($params) : ''), 250, '');
        $bot = (int) self::isBot($request->userAgent());
        $referrer = Str::limit((string) $request->headers->get('referer'), 500, '') ?: null;
        $now = now();

        DB::table('not_found_urls')->upsert(
            [['path' => $address, 'hits' => 1, 'bot_hits' => $bot, 'referrer' => $referrer, 'user_agent' => Str::limit((string) $request->userAgent(), 250, ''), 'first_seen_at' => $now, 'last_seen_at' => $now]],
            ['path'],
            array_filter([
                'hits' => DB::raw('hits + 1'),
                'bot_hits' => DB::raw('bot_hits + '.$bot),
                'referrer' => $referrer, // keep the last known one when this visit has none
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'last_seen_at' => $now,
            ], fn ($value) => $value !== null),
        );
    }

    public static function isBot(?string $userAgent): bool
    {
        return (bool) preg_match('~bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|curl|wget|python|headless|lighthouse~i', (string) $userAgent);
    }
}
