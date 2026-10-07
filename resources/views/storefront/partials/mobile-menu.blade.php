@php($contact = app(\App\Settings\StoreSettings::class)->toArray())
{{-- Off-canvas category menu (mobile / tablet). Added to the page when first opened ($store.ui.openMenu()). --}}
<template x-if="$store.ui.menuReady">
<div x-show="$store.ui.menuOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu"
    @keydown.escape.window="$store.ui.menuOpen = false">
    <div x-show="$store.ui.menuOpen" x-transition.opacity class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" @click="$store.ui.menuOpen = false"></div>

    <aside x-show="$store.ui.menuOpen" x-trap.noscroll="$store.ui.menuOpen"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
        class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col border-r border-line bg-canvas shadow-2xl">
        <div class="flex items-center justify-between border-b border-line px-4 py-3">
            <x-logo class="h-8" />
            <div class="flex items-center gap-1">
                <x-theme-toggle />
                <button type="button" class="icon-btn" @click="$store.ui.menuOpen = false" aria-label="Close menu"><x-icon name="x" class="size-5" /></button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto overscroll-contain">
            <div class="grid grid-cols-3 gap-2 p-4">
                @foreach ([['cpu', 'PC Builder', 'pc-builder', 'text-accent-700 dark:text-accent-400 bg-accent-500/10 ring-accent-500/20'], ['gift', 'Offers', 'offers', 'text-highlight bg-brand-500/10 ring-brand-500/20'], ['tool', 'Repair', 'services', 'text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 ring-emerald-500/20']] as [$icon, $label, $path, $tone])
                    <a href="{{ url($path) }}" class="flex flex-col items-center gap-1.5 rounded-2xl px-2 py-3.5 text-center text-[12px] font-semibold ring-1 {{ $tone }}">
                        <x-icon :name="$icon" class="size-5" /> {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="px-4 pt-1 pb-2 text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">Categories</p>
            {{-- Three levels, same as the desktop mega menu: category → sub-category → brands / sub-types --}}
            <ul class="px-2 pb-4" x-data="{ expanded: null, sub: null }">
                @foreach ($menu as $item)
                    <li>
                        @if ($item->children->isNotEmpty() || $item->brandLinks->isNotEmpty())
                            <button type="button" @click="expanded = expanded === '{{ $item->slug }}' ? null : '{{ $item->slug }}'; sub = null"
                                class="flex w-full items-center gap-3 rounded-xl px-2 py-2.5 text-left text-[14.5px] font-medium text-ink hover:bg-card-2"
                                :aria-expanded="expanded === '{{ $item->slug }}'">
                                <span class="grid size-9 place-items-center rounded-xl bg-card ring-1 ring-line text-highlight"><x-icon :name="$item->icon" class="size-[18px]" /></span>
                                <span class="flex-1">{{ $item->name }}</span>
                                <x-icon name="chevron-down" class="size-4 text-faint transition" ::class="expanded === '{{ $item->slug }}' && 'rotate-180'" />
                            </button>
                            <div x-show="expanded === '{{ $item->slug }}'" x-collapse x-cloak>
                                <ul class="mb-2 ml-[1.625rem] border-l border-line pl-3">
                                    <li><a href="{{ $item->url }}" class="block rounded-lg px-2 py-2 text-[13.5px] font-semibold text-highlight">All {{ $item->name }}</a></li>

                                    @foreach ($item->children as $child)
                                        @php($key = $item->slug.'-'.$loop->index)
                                        <li>
                                            @if ($child->links->isNotEmpty())
                                                <button type="button" @click="sub = sub === '{{ $key }}' ? null : '{{ $key }}'"
                                                    class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-[13.5px] hover:bg-card-2"
                                                    :class="sub === '{{ $key }}' && 'bg-card-2 text-ink'" :aria-expanded="sub === '{{ $key }}'">
                                                    <span class="flex-1">{{ $child->name }}</span>
                                                    <x-icon name="chevron-down" class="size-3.5 text-faint transition" ::class="sub === '{{ $key }}' && 'rotate-180'" />
                                                </button>
                                                <div x-show="sub === '{{ $key }}'" x-collapse x-cloak>
                                                    <ul class="mt-0.5 mb-1.5 ml-2 border-l border-line pl-3">
                                                        <li><a href="{{ $child->url }}" class="block px-2 py-1.5 text-[13px] font-semibold text-highlight">All {{ $child->name }}</a></li>
                                                        @foreach ($child->links as $link)
                                                            <li><a href="{{ $link->url }}" class="block px-2 py-1.5 text-[13px] text-muted hover:text-ink">{{ $link->name }}</a></li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <a href="{{ $child->url }}" class="block rounded-lg px-2 py-2 text-[13.5px] text-ink-2 hover:bg-card-2">{{ $child->name }}</a>
                                            @endif
                                        </li>
                                    @endforeach

                                    @foreach ($item->brandLinks as $brand)
                                        <li><a href="{{ $brand->url }}" class="block rounded-lg px-2 py-2 text-[13.5px] text-ink-2 hover:bg-card-2">{{ $brand->name }} {{ $item->name }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a href="{{ $item->url }}" class="flex items-center gap-3 rounded-xl px-2 py-2.5 text-[14.5px] font-medium text-ink hover:bg-card-2">
                                <span class="grid size-9 place-items-center rounded-xl bg-card ring-1 ring-line text-highlight"><x-icon :name="$item->icon" class="size-[18px]" /></span>
                                {{ $item->name }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Secondary links (the desktop top bar + header icons) --}}
            @php($download = app(\App\Settings\DownloadSettings::class))
            @if ($download->shows('top_bar'))
                <div class="border-t border-line px-4 py-4"><x-download-button size="sm" class="w-full" /></div>
            @endif
            <p class="border-t border-line px-4 pt-4 pb-2 text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">More</p>
            <ul class="grid grid-cols-2 gap-1 px-2 pb-4">
                @foreach ([
                    ['user', 'My account', 'account'],
                    ['truck-delivery', 'Track order', 'orders.track'],
                    ['shield-check', 'Warranty status', 'warranty'],
                    ['heart', 'Wishlist', 'wishlist'],
                    ['arrows-exchange', 'Compare', 'compare'],
                    ['tool', 'Repair service', 'services.index'],
                    ['file-invoice', 'Request a quote', 'quote'],
                    ['calendar-check', 'Free site visit', 'site-visit'],
                    ['gift', 'Offers', 'offers'],
                    ['tag', 'All brands', 'brand.index'],
                    ['map-pin', 'Store & contact', 'contact'],
                    ['help-circle', 'Help & FAQ', 'faq'],
                ] as [$icon, $label, $routeName])
                    {{-- Features that aren't built yet stay hidden until their route exists. --}}
                    @continue(! Route::has($routeName))
                    @continue($routeName === 'warranty' && ! app(\App\Settings\WarrantySettings::class)->lookup_enabled)
                    <li>
                        <a href="{{ route($routeName) }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2.5 text-[13.5px] text-ink-2 hover:bg-card-2 hover:text-ink">
                            <x-icon :name="$icon" class="size-[18px] text-faint" /> {{ $label }}
                            @if ($routeName === 'wishlist')
                                <span x-show="$store.wishlist.count" x-text="$store.wishlist.count" x-cloak class="ml-auto rounded-full bg-rose-500 px-1.5 font-mono text-[10px] text-white"></span>
                            @elseif ($routeName === 'compare')
                                <span x-show="$store.compare.count" x-text="$store.compare.count" x-cloak class="ml-auto rounded-full bg-brand-500 px-1.5 font-mono text-[10px] text-white"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['hotline']) }}" class="flex items-center gap-3 border-t border-line p-4 transition hover:bg-card-2">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/20 dark:text-accent-400"><x-icon name="phone-call" class="size-[18px]" /></span>
            <span class="min-w-0">
                <span class="block font-mono text-[14px] font-medium whitespace-nowrap text-ink">{{ $contact['hotline'] }}</span>
                <span class="block truncate text-xs text-muted">{{ $contact['hours'] }}</span>
            </span>
        </a>
    </aside>
</div>
</template>
