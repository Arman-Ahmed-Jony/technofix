@php
    $store = app(\App\Settings\StoreSettings::class);
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $tiles = [
        ['package', 'Orders', number_format($stats['orders']), $stats['open'] ? $stats['open'].' on the way' : 'All delivered', route('account.orders'), 'brand'],
        ['wallet', 'Spent', '৳'.number_format($stats['spent']), 'on '.$stats['delivered'].' delivered '.Str::plural('order', $stats['delivered']), route('account.orders'), 'emerald'],
        ['heart', 'Wishlist', null, 'saved products', route('wishlist'), 'rose'],
        ['cpu', 'PC builds', number_format($stats['builds']), 'saved builds', route('account.builds'), 'accent'],
    ];
    $tint = [
        'brand' => 'bg-brand-500/10 text-highlight ring-brand-500/20',
        'emerald' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:text-emerald-400',
        'rose' => 'bg-rose-500/10 text-rose-600 ring-rose-500/20 dark:text-rose-400',
        'accent' => 'bg-accent-500/10 text-accent-600 ring-accent-500/20 dark:text-accent-400',
    ];
@endphp

<x-account.shell title="My account">
    {{-- ─── Welcome ─── --}}
    <section class="relative isolate overflow-hidden rounded-[28px] px-6 py-7 text-white shadow-[0_24px_60px_-30px_rgb(3_18_30/0.8)] sm:px-8 sm:py-8"
        style="background: radial-gradient(34rem 20rem at 100% 0%, rgb(0 159 227 / .32), transparent 65%), radial-gradient(26rem 16rem at 0% 120%, rgb(243 146 0 / .2), transparent 65%), linear-gradient(135deg, #06263b 0%, #041a2a 55%, #03121e 100%)">
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.07]" style="background-image: linear-gradient(rgb(255 255 255) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255) 1px, transparent 1px); background-size: 30px 30px; mask-image: radial-gradient(ellipse at 75% 0%, black, transparent 70%);"></div>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-[13px] font-medium text-white/60">{{ $greeting }}</p>
                <h1 class="mt-1 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] sm:text-[34px]">Hello, {{ $customer->firstName() }}</h1>
                <p class="mt-2 text-[13.5px] text-white/70">Member since {{ $customer->created_at->format('F Y') }}{{ $stats['orders'] ? ' · '.$stats['orders'].' '.Str::plural('order', $stats['orders']).' with us' : '' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-[13.5px] font-semibold text-navy-900 shadow-lg shadow-black/20 transition hover:bg-white/90">
                    <x-icon name="shopping-bag" class="size-4" /> Shop now
                </a>
                @if (Route::has('pc-builder'))
                    <a href="{{ route('pc-builder') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-[13.5px] font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/15">
                        <x-icon name="cpu" class="size-4" /> Build a PC
                    </a>
                @endif
                @if (Route::has('services.index'))
                    <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-[13.5px] font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/15">
                        <x-icon name="tool" class="size-4" /> Book a repair
                    </a>
                @endif
            </div>
        </div>
    </section>

    {{-- ─── At a glance ─── --}}
    <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        @foreach ($tiles as [$icon, $label, $value, $sub, $url, $color])
            <a href="{{ $url }}" class="group card relative flex flex-col p-4 transition hover:-translate-y-0.5 hover:shadow-lift sm:p-5">
                <span class="flex items-center justify-between">
                    <span class="grid size-10 place-items-center rounded-xl ring-1 {{ $tint[$color] }}"><x-icon :name="$icon" class="size-5" /></span>
                    <x-icon name="arrow-up-right" class="size-4 text-faint transition group-hover:text-ink" />
                </span>
                <span class="mt-4 text-[12.5px] font-medium text-muted">{{ $label }}</span>
                @if ($value === null)
                    <span class="price mt-0.5 font-display text-[24px] leading-tight" x-data x-text="$store.wishlist.count">0</span>
                @else
                    <span class="price mt-0.5 font-display text-[24px] leading-tight">{{ $value }}</span>
                @endif
                <span class="mt-0.5 truncate text-[12px] text-faint">{{ $sub }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-5 grid grid-cols-1 items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-5">
            {{-- ─── Order in progress ─── --}}
            @if ($active)
                @php $currentStep = $steps->firstWhere('current', true); @endphp
                <section class="card overflow-hidden">
                    <div class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6">
                        <div>
                            <p class="flex items-center gap-2 text-[12px] font-semibold tracking-[0.12em] text-highlight uppercase">
                                <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-500 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-brand-500"></span></span>
                                Order in progress
                            </p>
                            <p class="mt-1.5 font-mono text-[17px] font-semibold text-ink">{{ $active->number }}</p>
                            <p class="text-[12.5px] text-muted">Placed {{ $active->created_at->format('d M Y, h:i A') }} · {{ $active->delivery_label }}</p>
                        </div>
                        <p class="price font-display text-[22px]">৳{{ number_format($active->total) }}</p>
                    </div>

                    {{-- Stepper --}}
                    <ol class="relative mt-6 grid grid-cols-5 px-3 sm:px-5" aria-label="Order progress">
                        @foreach ($steps as $step)
                            <li class="relative flex flex-col items-center text-center">
                                @unless ($loop->first)
                                    <span @class(['absolute top-[15px] right-1/2 h-[3px] w-full -translate-y-1/2', 'bg-gradient-to-r from-brand-500 to-brand-400' => $step['done'], 'bg-line' => ! $step['done']]) aria-hidden="true"></span>
                                @endunless
                                <span @class([
                                    'relative z-10 grid size-[30px] place-items-center rounded-full text-[12px] font-semibold transition',
                                    'bg-brand-500 text-white shadow-[0_0_0_5px_rgb(0_159_227/0.18)]' => $step['current'],
                                    'bg-brand-600 text-white' => $step['done'] && ! $step['current'],
                                    'bg-card text-faint ring-2 ring-line' => ! $step['done'],
                                ])>
                                    @if ($step['done'] && ! $step['current'])
                                        <x-icon name="check" class="size-4 [stroke-width:3]" />
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <span @class(['mt-2 px-0.5 text-[11px] leading-tight font-medium sm:text-[12px]', 'text-ink' => $step['done'], 'text-faint' => ! $step['done']])>{{ $step['title'] }}</span>
                                @if ($step['at'])
                                    <span class="mt-0.5 hidden font-mono text-[10.5px] text-faint sm:block">{{ $step['at']->format('d M') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    <div class="mx-5 mt-5 flex items-start gap-3 rounded-2xl bg-brand-500/[0.07] p-3.5 ring-1 ring-brand-500/15 sm:mx-6">
                        <x-icon name="info-circle" class="mt-0.5 size-[18px] shrink-0 text-highlight" />
                        <p class="text-[13px] text-ink-2"><span class="font-semibold text-ink">{{ $currentStep['title'] ?? '' }}:</span> {{ $currentStep['text'] ?? '' }}
                            @if ($active->tracking_code)
                                <span class="mt-1 block text-muted">{{ $active->courier ?: 'Courier' }} tracking no. <span class="font-mono text-ink">{{ $active->tracking_code }}</span></span>
                            @endif
                        </p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-4 sm:px-6">
                        <span class="flex items-center gap-3">
                            @include('account.partials.item-thumbs', ['items' => $active->items])
                            <span class="text-[13px] text-muted">{{ $active->items->sum('qty') }} {{ Str::plural('item', $active->items->sum('qty')) }}</span>
                        </span>
                        <a href="{{ route('account.orders.show', $active->number) }}" class="btn btn-sm btn-ink">Track order <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                </section>
            @else
                <section class="card flex flex-col items-center gap-4 px-6 py-8 text-center sm:flex-row sm:text-left">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon name="truck-delivery" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                    <div class="flex-1">
                        <p class="font-display text-[17px] font-semibold text-ink">No orders on the way</p>
                        <p class="mt-0.5 text-[13.5px] text-muted">When you order, you can follow it here step by step — from confirmation to your door.</p>
                    </div>
                    <a href="{{ route('home') }}" class="btn btn-sm btn-primary shrink-0">Start shopping</a>
                </section>
            @endif

            {{-- ─── Recent orders ─── --}}
            <section class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-5 pb-3 sm:px-6">
                    <h2 class="font-display text-[17px] font-semibold tracking-tight text-ink">Recent orders</h2>
                    @if ($stats['orders'])
                        <a href="{{ route('account.orders') }}" class="link text-[13px]">See all</a>
                    @endif
                </div>
                @forelse ($orders as $order)
                    <div class="border-t border-line">@include('account.partials.order-row')</div>
                @empty
                    <div class="border-t border-line px-5 py-10 text-center">
                        <p class="text-sm text-muted">No orders yet — your orders will appear here.</p>
                    </div>
                @endforelse
            </section>
        </div>

        {{-- ─── Side column ─── --}}
        <div class="grid min-w-0 gap-5 md:grid-cols-2 xl:grid-cols-1">
            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-display text-[15px] font-semibold text-ink"><x-icon name="map-pin" class="size-[18px] text-highlight" /> Delivery address</h2>
                    <a href="{{ route('account.addresses') }}" class="link text-[13px]">{{ $address ? 'Manage' : 'Add' }}</a>
                </div>
                @if ($address)
                    <p class="mt-3 text-[14px] font-medium text-ink">{{ $address->name }}</p>
                    <p class="font-mono text-[12.5px] text-muted">{{ $address->phone }}</p>
                    <p class="mt-1.5 text-[13.5px] leading-relaxed text-ink-2">{{ $address->summary() }}</p>
                @else
                    <p class="mt-3 text-[13.5px] text-muted">Save an address and checkout fills itself in.</p>
                @endif
            </section>

            <section class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-display text-[15px] font-semibold text-ink"><x-icon name="user-circle" class="size-[18px] text-highlight" /> Your details</h2>
                    <a href="{{ route('account.profile') }}" class="link text-[13px]">Edit</a>
                </div>
                <p class="mt-3 text-[14px] font-medium text-ink">{{ $customer->name }}</p>
                <p class="font-mono text-[12.5px] text-muted">{{ $customer->phone }}</p>
                @if ($customer->email)
                    <p class="mt-0.5 text-[13px] text-muted">{{ $customer->email }}</p>
                @else
                    <a href="{{ route('account.profile') }}" class="mt-3 flex items-start gap-2 rounded-xl bg-accent-500/10 p-3 text-[12.5px] text-accent-800 ring-1 ring-accent-500/20 transition hover:bg-accent-500/15 dark:text-accent-300">
                        <x-icon name="mail" class="mt-px size-4 shrink-0" /> Add your email so you can reset your password yourself.
                    </a>
                @endif
            </section>

            @if ($builds->isNotEmpty())
                <section class="card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 font-display text-[15px] font-semibold text-ink"><x-icon name="cpu" class="size-[18px] text-highlight" /> Saved PC builds</h2>
                        <a href="{{ route('account.builds') }}" class="link text-[13px]">See all</a>
                    </div>
                    <ul class="mt-3 space-y-2">
                        @foreach ($builds as $saved)
                            <li>
                                <a href="{{ $saved['model']->url() }}" class="group flex items-center justify-between gap-3 rounded-xl bg-card-2 px-3.5 py-3 ring-1 ring-line transition hover:ring-line-strong">
                                    <span class="min-w-0">
                                        <span class="block truncate text-[13.5px] font-medium text-ink group-hover:text-highlight">{{ $saved['model']->name }}</span>
                                        <span class="block text-[12px] text-muted">{{ collect($saved['build']->items)->sum('qty') }} parts</span>
                                    </span>
                                    <span class="price shrink-0 font-display text-[15px]">৳{{ number_format($saved['build']->subtotal()) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Phones/tablets only — on desktop the account menu column has the help box. --}}
            <section class="relative isolate overflow-hidden rounded-[24px] border border-line bg-card p-5 shadow-card lg:hidden">
                <div class="absolute -right-10 -bottom-12 -z-10 size-40 rounded-full bg-accent-500/15 blur-3xl"></div>
                <h2 class="flex items-center gap-2 font-display text-[15px] font-semibold text-ink"><x-icon name="headset" class="size-[18px] text-accent-500" /> Need help?</h2>
                <p class="mt-1.5 text-[13px] text-muted">Questions about an order, a warranty or a repair? We’re here {{ $store->hours ? '('.$store->hours.')' : '' }}.</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <a href="{{ $store->telHref() }}" class="btn btn-sm btn-soft"><x-icon name="phone" class="size-4" /> Call</a>
                    <a href="{{ $store->whatsappUrl('Hi TechnoFix, I need help with my account/order.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                </div>
            </section>
        </div>
    </div>
</x-account.shell>
