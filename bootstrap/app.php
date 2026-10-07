<?php

use App\Http\Middleware\CachePage;
use App\Http\Middleware\BlockAddresses;
use App\Http\Middleware\CaptureAttribution;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\SecurityHeaders;
use App\Support\NotFound;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            CaptureAttribution::class,
            MaintenanceMode::class,
            BlockAddresses::class,
        ]);

        // Browser security headers on every response (admin included).
        $middleware->append(SecurityHeaders::class);

        // Guest page cache for storefront pages (App\Support\PageCache).
        $middleware->alias(['page.cache' => CachePage::class]);

        // Set by the ad pixels' own scripts: read as they are (Tracking::attribution), not decrypted.
        $middleware->encryptCookies(except: ['_fbp', '_fbc', '_ttp', '_ga']);

        // Storefront accounts (the admin panel handles its own sign-in).
        $middleware->redirectGuestsTo(fn () => route('account.login'));
        $middleware->redirectUsersTo(fn () => route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Missing pages: follow a redirect (Admin → Content → Redirects) or list the address under Broken links.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            try {
                return NotFound::handle($request);
            } catch (Throwable $failure) {
                report($failure); // never let this break the 404 page itself

                return null;
            }
        });
    })->create();

// cPanel layout (the deploy package): the app folder sits next to public_html and has no public/ folder of its own.
if (! is_dir(dirname(__DIR__).'/public') && is_dir($publicHtml = dirname(__DIR__, 2).'/public_html')) {
    $app->usePublicPath($publicHtml);
}

return $app;
