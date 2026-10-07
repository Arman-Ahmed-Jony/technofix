<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Support\Brand;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(config('technofix.admin_path'))
            ->login()
            ->passwordReset()
            // Every staff member's own name, email, password and two-step sign-in (user menu → My profile).
            ->profile(EditProfile::class, isSimple: false)
            ->multiFactorAuthentication([AppAuthentication::make()->recoverable()->brandName('TechnoFix admin')])
            ->brandName('TechnoFix')
            // Logos and icon from Settings → Branding (built-in files until uploaded).
            ->brandLogo(fn () => Brand::logo())
            ->darkModeBrandLogo(fn () => Brand::logoDark())
            ->brandLogoHeight('2.1rem')
            ->favicon(fn () => Brand::icon())
            ->colors([
                // Brand blue with #1D71B8 as 600 (buttons, links); Color::hex() would make every shade too light.
                'primary' => [
                    50 => '#eff7fd', 100 => '#dcedfa', 200 => '#b9dbf4', 300 => '#86c0ea', 400 => '#4d9fdc',
                    500 => '#2687cc', 600 => '#1d71b8', 700 => '#195f9b', 800 => '#174f80', 900 => '#154268', 950 => '#0d2b45',
                ],
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'info' => Color::Sky,
            ])
            // Brand theme (resources/css/filament/admin/theme.css) with the storefront's self-hosted fonts.
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font('Geist', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString(app(Vite::class)->fonts()))
            ->topbar(false) // logo, search and user menu live in the navy sidebar
            ->sidebarWidth('17rem')
            ->navigationGroups(['Sales', 'Catalog', 'Inventory', 'Content', 'Settings'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
