<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Settings\MaintenanceSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maintenance mode (Admin → Settings → Maintenance mode): customers get a friendly "back soon" page (503, so search
 * engines keep the pages). Signed-in staff see the shop as usual (with a reminder bar) and the admin always works.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(MaintenanceSettings::class);

        if (! rescue(fn () => $settings->enabled, false, report: false) || $this->exempt($request)) {
            return $next($request);
        }

        $backAt = $settings->back_at ? rescue(fn () => Carbon::parse($settings->back_at), null, report: false) : null;

        return response()->view('errors.maintenance', ['settings' => $settings, 'backAt' => $backAt?->isFuture() ? $backAt : null], 503)
            ->header('Retry-After', $backAt?->isFuture() ? (string) max(60, now()->diffInSeconds($backAt)) : '3600');
    }

    private function exempt(Request $request): bool
    {
        $admin = trim((string) config('technofix.admin_path'), '/');

        return auth('web')->user() instanceof User
            || $request->is($admin, $admin.'/*', 'livewire*', 'up', 'storage/*', 'build/*', 'robots.txt');
    }
}
