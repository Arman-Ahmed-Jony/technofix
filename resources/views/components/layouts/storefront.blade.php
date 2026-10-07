@props(['title' => null, 'description' => null, 'minimal' => false, 'canonical' => null, 'noindex' => false, 'pageKey' => null, 'image' => null, 'type' => 'website'])
{{-- `minimal` = distraction-free checkout chrome: slim header/footer, no menus, drawers or bottom nav.
     SEO (Admin → Settings → SEO, via App\Support\Seo): `page-key` = a fixed page from Seo::PAGES (its title and
     description come from the settings); `image` / `type` = link preview picture and og:type (default: the site's). --}}
@php
    $seo = app(\App\Support\Seo::class);
    if ($pageKey) {
        ['title' => $title, 'description' => $description] = $seo->page($pageKey);
    }
    $description ??= $seo->defaultDescription();
    $canonical ??= url()->current();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $seo->title($title) }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light dark">
    @if ($minimal || $noindex)
        <meta name="robots" content="noindex">
    @endif
    <link rel="canonical" href="{{ $canonical }}">
    {{-- Link previews (Facebook, WhatsApp, Messenger, X) --}}
    <meta property="og:site_name" content="{{ $seo->siteName() }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title ?: $seo->title(null) }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image ?: $seo->shareImage() }}">
    <meta name="twitter:card" content="summary_large_image">
    {{-- Apply the saved (or OS) theme before first paint to avoid a light flash in dark mode. --}}
    <script>
        (() => {
            try {
                const saved = JSON.parse(localStorage.getItem('tf-theme'));
                const dark = saved ? saved === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.classList.toggle('dark', dark);
                if (dark) document.querySelector('meta[name="theme-color"]').content = '#050c15';
            } catch (e) {}
        })();
    </script>
    <link rel="icon" href="{{ \App\Support\Brand::icon() }}">
    <link rel="apple-touch-icon" href="{{ \App\Support\Brand::appIcon() }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
    @include('storefront.partials.tracking-head')
</head>
<body @class([
        'min-h-screen bg-canvas font-sans text-ink-2 antialiased',
        'max-lg:pb-[calc(4rem+env(safe-area-inset-bottom))]' => ! $minimal,
    ])
    x-data x-effect="document.documentElement.classList.toggle('overflow-hidden', $store.ui.locked)">
    <a href="#main" class="sr-only z-50 rounded-lg bg-card px-4 py-2 font-semibold text-ink focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    @unless ($minimal)
        {{-- Staff only: the shop is closed to customers (Admin → Settings → Maintenance mode). --}}
        @if (auth('web')->check() && rescue(fn () => app(\App\Settings\MaintenanceSettings::class)->enabled, false, false))
            <div class="bg-amber-400 px-4 py-2 text-center text-[13px] font-medium text-amber-950">Maintenance mode is on — customers see the “back soon” page. You see the shop because you’re signed in as staff.</div>
        @endif
        @include('storefront.partials.announcement')
    @endunless
    @include($minimal ? 'storefront.partials.checkout-header' : 'storefront.partials.header')

    <main id="main">
        {{ $slot }}
    </main>

    @if ($minimal)
        @include('storefront.partials.checkout-footer')
    @else
        @include('storefront.partials.footer')
        @include('storefront.partials.bottom-nav')
        @include('storefront.partials.mobile-menu')
        @include('storefront.partials.cart-drawer')
    @endif
    @include('storefront.partials.toasts')
    @include('storefront.partials.tracking-body')

    {{-- Must stay last: prints every icon used above exactly once. --}}
    {!! app(\App\Support\IconSprite::class)->render() !!}
</body>
</html>
