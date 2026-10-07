<?php

namespace App\Http\Middleware;

use App\Support\PageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Serves storefront pages to guests from App\Support\PageCache (alias `page.cache`). */
class CachePage
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PageCache::applies($request)) {
            return $next($request);
        }

        if ($cached = PageCache::get($request)) {
            return $cached;
        }

        $response = $next($request);
        PageCache::put($request, $response);

        return $response;
    }
}
