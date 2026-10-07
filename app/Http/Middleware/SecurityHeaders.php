<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every response (storefront, admin, files served by Laravel).
 * The CSP only limits framing, <base>, plugins and form targets — scripts stay unrestricted because the pixels,
 * Tag Manager and Alpine need inline/eval code; Cloudflare adds its own protection in front.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), browsing-topics=()',
            // "https:" in form-action: online payment gateways post their pages from forms.
            'Content-Security-Policy' => "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self' https:"
                .($request->isSecure() ? '; upgrade-insecure-requests' : ''),
        ];

        if ($request->isSecure() && app()->isProduction()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
