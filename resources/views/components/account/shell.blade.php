@props(['title', 'active' => 'account', 'breadcrumbs' => []])
@php
    $customer = auth('customer')->user();
    $counts = [
        'account.orders' => $customer->orders()->count(),
        'account.builds' => $customer->savedBuilds()->count(),
    ];
    $links = [
        ['account', 'layout-dashboard', 'Overview'],
        ['account.orders', 'package', 'My orders'],
        ['account.addresses', 'map-pin', 'Addresses'],
        ['account.builds', 'cpu', 'Saved PC builds'],
        ['account.profile', 'user-circle', 'Profile & password'],
    ];
    $store = app(\App\Settings\StoreSettings::class);
@endphp
<x-layouts.storefront :title="$title" :noindex="true">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'My account', 'url' => $active === 'account' ? null : route('account')], ...$breadcrumbs]" />

        {{-- grid-cols-1 = minmax(0,1fr): keeps the scrolling link row from widening the page on phones --}}
        <div class="mt-5 grid grid-cols-1 items-start gap-5 lg:grid-cols-[264px_minmax(0,1fr)] lg:gap-7">
            <aside class="min-w-0 space-y-4 lg:sticky lg:top-[138px]">
                <div class="card overflow-hidden">
                    {{-- Who's signed in --}}
                    <div class="relative isolate flex items-center gap-3 px-4 pt-4 pb-3.5 lg:flex-col lg:items-start lg:px-5 lg:pt-5">
                        <div class="absolute inset-x-0 top-0 -z-10 h-16 bg-gradient-to-b from-brand-500/10 to-transparent lg:h-20"></div>
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-500 via-brand-600 to-accent-500 p-[2px] shadow-[0_8px_20px_-8px_rgb(0_159_227/0.7)] lg:size-14">
                            <span class="grid size-full place-items-center rounded-full bg-card font-display text-[18px] font-semibold text-highlight lg:text-[20px]">{{ Str::upper(Str::substr($customer->name, 0, 1)) }}</span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate font-display text-[15px] font-semibold text-ink">{{ $customer->name }}</span>
                            <span class="block font-mono text-[12px] text-muted">{{ $customer->phone }}</span>
                            <span class="hidden text-[11.5px] text-faint lg:block">Member since {{ $customer->created_at->format('M Y') }}</span>
                        </span>
                    </div>

                    <nav class="scrollbar-none flex gap-1.5 overflow-x-auto border-t border-line p-2 lg:flex-col lg:gap-0.5" aria-label="Account">
                        @foreach ($links as [$route, $icon, $label])
                            @continue(! Route::has($route))
                            @php $isActive = $active === $route; @endphp
                            <a href="{{ route($route) }}" @if ($isActive) aria-current="page" @endif @class([
                                'relative flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13.5px] font-medium transition',
                                'bg-brand-500/10 text-ink ring-1 ring-brand-500/20 lg:before:absolute lg:before:inset-y-2 lg:before:-left-2 lg:before:w-[3px] lg:before:rounded-r-full lg:before:bg-gradient-to-b lg:before:from-brand-500 lg:before:to-accent-500' => $isActive,
                                'text-ink-2 hover:bg-card-2 hover:text-ink' => ! $isActive,
                            ])>
                                <x-icon :name="$icon" @class(['size-[18px]', 'text-highlight' => $isActive, 'text-faint' => ! $isActive]) />
                                <span class="whitespace-nowrap">{{ $label }}</span>
                                @if (($counts[$route] ?? 0) > 0)
                                    <span @class([
                                        'ml-auto rounded-full px-2 py-px font-mono text-[11px]',
                                        'bg-brand-500/15 text-highlight' => $isActive,
                                        'bg-card-2 text-muted ring-1 ring-line' => ! $isActive,
                                    ])>{{ $counts[$route] }}</span>
                                @endif
                            </a>
                        @endforeach
                        <form method="post" action="{{ route('account.logout') }}" class="shrink-0 lg:mt-1.5 lg:border-t lg:border-line lg:pt-1.5">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-[13.5px] font-medium text-ink-2 transition hover:bg-rose-500/10 hover:text-rose-600">
                                <x-icon name="logout" class="size-[18px] text-faint" /> <span class="whitespace-nowrap">Sign out</span>
                            </button>
                        </form>
                    </nav>
                </div>

                {{-- Help (desktop) --}}
                <div class="hidden rounded-[24px] bg-navy-900 p-5 text-white lg:block" style="background: radial-gradient(16rem 10rem at 100% 0%, rgb(0 159 227 / .3), transparent 70%), linear-gradient(160deg, #06263b, #03121e)">
                    <p class="flex items-center gap-2 font-display text-[14.5px] font-semibold"><x-icon name="headset" class="size-[18px] text-accent-400" /> We’re here to help</p>
                    <p class="mt-1 text-[12.5px] text-white/65">Orders, warranty or repairs — call or message us.</p>
                    <a href="{{ $store->telHref() }}" class="mt-3 flex items-center gap-2 font-mono text-[14px] font-semibold text-white hover:text-brand-300"><x-icon name="phone" class="size-4" /> {{ $store->hotline }}</a>
                </div>
            </aside>

            <div class="min-w-0">
                @if (session('status'))
                    <p class="mb-4 flex items-start gap-2 rounded-2xl bg-emerald-500/10 p-3.5 text-[13.5px] text-emerald-800 ring-1 ring-emerald-500/20 dark:text-emerald-300" role="status">
                        <x-icon name="circle-check" class="mt-0.5 size-4 shrink-0" /> {{ session('status') }}
                    </p>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.storefront>
