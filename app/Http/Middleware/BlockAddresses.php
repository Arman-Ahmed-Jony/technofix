<?php

namespace App\Http\Middleware;

use App\Support\Blocklist;
use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocked internet addresses (Admin → Settings → Security) can browse but not order or send storefront forms.
 * The admin panel and Livewire are left alone so staff can never lock themselves out.
 */
class BlockAddresses
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()
            || $request->is(config('technofix.admin_path'), config('technofix.admin_path').'/*', 'livewire*', 'logout')
            || ! Blocklist::ipBlocked($request->ip())) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => Blocklist::message()], 403);
        }

        return back()->withInput($request->except('password', 'password_confirmation'))->withErrors([Turnstile::FIELD => Blocklist::message()]);
    }
}
