@php($contact = app(\App\Settings\StoreSettings::class)->toArray())
{{-- Distraction-free header for cart / checkout. --}}
<header class="sticky top-0 z-40 border-b border-line bg-canvas/80 backdrop-blur-xl backdrop-saturate-150">
    <div class="container-x flex h-16 items-center justify-between gap-4 lg:h-[72px]">
        <a href="{{ route('home') }}" class="shrink-0" aria-label="TechnoFix home">
            <x-logo class="h-8 sm:h-9" />
        </a>

        <span class="hidden items-center gap-2 text-[13px] font-medium text-muted sm:flex">
            <span class="grid size-7 place-items-center rounded-full bg-emerald-500/10 text-emerald-600 ring-1 ring-emerald-500/20 dark:text-emerald-400"><x-icon name="lock" class="size-3.5" /></span>
            Secure checkout
        </span>

        <div class="flex items-center gap-1">
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['hotline']) }}" class="hidden items-center gap-2 rounded-xl px-2.5 py-1.5 text-[13px] text-muted transition hover:bg-card-2 hover:text-ink md:flex">
                <x-icon name="headset" class="size-[18px]" /> Need help? <span class="font-mono font-medium text-ink">{{ $contact['hotline'] }}</span>
            </a>
            <x-theme-toggle />
        </div>
    </div>
</header>
