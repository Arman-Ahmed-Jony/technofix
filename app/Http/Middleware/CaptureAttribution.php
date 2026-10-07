<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers where a visitor came from — ad tags (utm_*, fbclid, gclid) or another website — so orders and
 * enquiries can be credited to the campaign that brought them. The latest ad click wins; a plain referrer is
 * only kept when nothing better is known.
 */
class CaptureAttribution
{
    public const PARAMS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession()) {
            $tags = collect(self::PARAMS)
                ->mapWithKeys(fn (string $key) => [$key => Str::limit((string) $request->query($key, ''), 150, '')])
                ->filter()
                ->all();

            $referrer = (string) $request->headers->get('referer', '');
            $external = $referrer !== '' && parse_url($referrer, PHP_URL_HOST) !== $request->getHost();

            if ($tags) {
                $request->session()->put('attribution', [
                    ...$tags,
                    'landing' => Str::limit($request->fullUrl(), 250, ''),
                    'referrer' => $external ? Str::limit($referrer, 250, '') : null,
                    'at' => now()->toIso8601String(),
                ]);
            } elseif ($external && ! $request->session()->has('attribution')) {
                $request->session()->put('attribution', [
                    'referrer' => Str::limit($referrer, 250, ''),
                    'landing' => Str::limit($request->fullUrl(), 250, ''),
                    'at' => now()->toIso8601String(),
                ]);
            }
        }

        return $next($request);
    }
}
