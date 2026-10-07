@php($contact = app(\App\Settings\StoreSettings::class)->toArray())
{{-- Utility bar (desktop) --}}
<div class="hidden bg-navy-950 text-[12.5px] whitespace-nowrap text-white/60 lg:block">
    <div class="container-x flex h-9 items-center justify-between gap-6">
        <div class="flex min-w-0 items-center gap-6 overflow-hidden">
            {{-- Everything here is set in Admin → Content → Top bar (hotline/hours text: Settings → Store & contact). --}}
            @php($topBar = app(\App\Settings\TopBarSettings::class))
            @if ($topBar->show_hotline && $contact['hotline'])
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['hotline']) }}" class="flex items-center gap-1.5 transition hover:text-white">
                    <x-icon name="phone-call" class="size-3.5 text-accent-400" />
                    Hotline <span class="font-mono font-medium text-white">{{ $contact['hotline'] }}</span>
                </a>
            @endif
            @if ($topBar->show_hours && $contact['hours'])
                <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-3.5 text-accent-400" /> {{ $contact['hours'] }}</span>
            @endif
            @if ($topBar->message)
                {{-- Only on wide screens, so the bar never wraps on smaller laptops. --}}
                <span class="hidden items-center gap-1.5 xl:flex">@if ($topBar->message_icon)<x-icon :name="$topBar->message_icon" class="size-3.5 text-accent-400" />@endif {{ $topBar->message }}</span>
            @endif
        </div>
        <nav class="flex shrink-0 items-center gap-6" aria-label="Utility">
            @php($download = app(\App\Settings\DownloadSettings::class))
            @if ($download->shows('top_bar'))
                <a href="{{ $download->url }}" target="_blank" rel="noopener" class="group flex items-center gap-1.5 font-semibold text-sky-300 transition [text-shadow:0_0_10px_rgb(56_189_248/0.75)] hover:text-white focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-sky-300">
                    <span class="relative flex size-1.5"><span class="absolute inline-flex size-full animate-ping rounded-full bg-sky-400 opacity-75"></span><span class="relative inline-flex size-1.5 rounded-full bg-sky-400"></span></span>
                    <x-icon name="cloud-download" class="size-3.5" /> {{ $download->label }}<span class="sr-only"> (opens in a new tab)</span>
                </a>
            @endif
            @foreach ($topBar->navLinks() as $link)
                <a href="{{ $link['url'] }}"@if ($link['external']) target="_blank" rel="noopener"@endif class="transition hover:text-white">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </div>
</div>

{{-- Main header: frosted glass, gains a shadow once the page scrolls --}}
<header x-data="{ scrolled: false }" @scroll.window.throttle.50ms="scrolled = window.scrollY > 8"
    :class="scrolled ? 'shadow-[0_10px_30px_-18px_rgb(10_22_38/0.35)] dark:shadow-[0_10px_30px_-18px_rgb(0_0_0/0.9)]' : ''"
    class="sticky top-0 z-40 border-b border-line bg-canvas/80 backdrop-blur-xl backdrop-saturate-150 transition-shadow duration-300"
    @keydown.window.ctrl.k.prevent="$refs.search.focus()" @keydown.window.meta.k.prevent="$refs.search.focus()">
    <div class="container-x flex flex-wrap items-center gap-x-2 gap-y-2.5 py-2.5 lg:h-[72px] lg:flex-nowrap lg:gap-x-8 lg:py-0">
        <button type="button" class="icon-btn -ml-2 lg:hidden" @click="$store.ui.openMenu()" aria-label="Open menu">
            <x-icon name="menu-2" class="size-6" />
        </button>

        <a href="{{ route('home') }}" class="mr-auto shrink-0 lg:mr-0" aria-label="TechnoFix home">
            <x-logo class="h-8 sm:h-9 lg:h-10" />
        </a>

        {{-- Search --}}
        <form action="{{ route('search') }}" method="get" role="search" class="relative order-last w-full lg:order-none lg:max-w-[640px] lg:flex-1"
            x-data="searchBox(@js(route('search.suggest')))" @submit="submit($event)" @click.outside="open = false" @keydown.escape="open = false; $refs.search.blur()">
            <label for="site-search" class="sr-only">Search products</label>
            <div class="group flex h-11 items-center rounded-2xl bg-card pl-4 ring-1 ring-line transition focus-within:ring-2 focus-within:ring-brand-500/60 focus-within:shadow-[0_0_0_6px_rgb(0_159_227/0.12)] lg:h-12">
                <x-icon name="search" class="size-[18px] text-faint transition group-focus-within:text-highlight" />
                <input id="site-search" x-ref="search" name="q" type="search" x-model="q" @input="input" @focus="q.trim().length > 1 && hasResults && (open = true)"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                    autocomplete="off" placeholder="Search laptops, CCTV, components…"
                    class="h-full min-w-0 flex-1 bg-transparent px-3 text-[14.5px] text-ink placeholder:text-faint focus:outline-none [&::-webkit-search-cancel-button]:hidden">
                <kbd class="mr-2 hidden items-center gap-0.5 rounded-md bg-card-2 px-1.5 py-0.5 font-mono text-[10.5px] font-medium text-faint ring-1 ring-line lg:flex">Ctrl K</kbd>
                <button type="submit" class="btn-ink m-1 grid h-9 place-items-center rounded-xl px-3.5 transition lg:h-10 lg:px-5" aria-label="Search">
                    <span class="hidden text-sm font-semibold lg:inline">Search</span>
                    <x-icon name="search" class="size-4 lg:hidden" />
                </button>
            </div>

            {{-- Suggestions --}}
            <div x-show="open" x-cloak x-transition:enter="transition duration-150 ease-out" x-transition:enter-start="opacity-0 translate-y-1"
                class="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-line bg-elevated shadow-pop">
                <template x-if="!hasResults">
                    <p class="px-4 py-6 text-center text-sm text-muted">No products found for “<span x-text="q" class="text-ink"></span>”.</p>
                </template>
                <template x-if="results.categories.length">
                    <div class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-3">
                        <span class="text-[11px] font-semibold tracking-wider text-faint uppercase">Categories</span>
                        <template x-for="cat in results.categories" :key="cat.url">
                            <a :href="cat.url" class="chip bg-brand-500/10 py-1 text-[12px] text-highlight ring-1 ring-brand-500/20 hover:bg-brand-500/15" x-text="cat.name"></a>
                        </template>
                    </div>
                </template>
                <ul class="max-h-[60vh] overflow-y-auto p-1.5">
                    <template x-for="(item, i) in results.products" :key="item.url">
                        <li>
                            <a :href="item.url" @mouseenter="active = i" :class="active === i && 'bg-card-2'" class="flex items-center gap-3 rounded-xl px-2.5 py-2">
                                <span class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-card-2 text-highlight ring-1 ring-line">
                                    <template x-if="item.image"><img :src="item.image" alt="" class="size-full bg-white object-contain p-1"></template>
                                    <template x-if="!item.image"><span x-html="item.icon"></span></template>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-[10.5px] font-semibold tracking-wider text-faint uppercase" x-text="item.brand"></span>
                                    <span class="line-clamp-1 text-[13.5px] font-medium text-ink" x-text="item.name"></span>
                                </span>
                                <span class="text-right">
                                    <span class="price block text-sm" x-text="item.price"></span>
                                    <span class="block text-xs text-faint line-through tabular-nums" x-show="item.regular" x-text="item.regular"></span>
                                </span>
                            </a>
                        </li>
                    </template>
                </ul>
                <template x-if="results.total > results.products.length">
                    <button type="submit" class="flex w-full items-center justify-center gap-1 border-t border-line bg-card-2 px-4 py-3 text-sm font-semibold text-highlight hover:bg-line">
                        See all <span x-text="results.total"></span> results <x-icon name="arrow-right" class="size-4" />
                    </button>
                </template>
            </div>
        </form>

        {{-- Actions --}}
        <div class="flex items-center gap-0.5 lg:gap-1">
            {{-- Icon-only on narrow laptops (1024–1149px), full label from 1150px --}}
            <a href="{{ url('pc-builder') }}" aria-label="PC Builder" title="PC Builder"
                class="group mr-2 hidden rounded-xl bg-[linear-gradient(120deg,var(--color-accent-500),var(--color-brand-500))] p-px shadow-[0_8px_20px_-12px_rgb(233_78_27/0.8)] lg:block">
                <span class="flex h-[38px] items-center gap-2 rounded-[11px] bg-card px-2.5 text-[13.5px] font-semibold whitespace-nowrap text-ink transition group-hover:bg-card/90 min-[1150px]:px-3.5">
                    <x-icon name="cpu" class="size-4 text-accent-600" /> <span class="hidden min-[1150px]:inline">PC Builder</span>
                </span>
            </a>
            <x-theme-toggle />
            <a href="{{ url('compare') }}" class="icon-btn hidden lg:grid" aria-label="Compare" title="Compare">
                <x-icon name="arrows-exchange" class="size-5" />
                <span x-show="$store.compare.count" x-text="$store.compare.count" x-cloak class="absolute -top-0.5 -right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-brand-500 px-1 text-[10px] font-bold text-white ring-2 ring-canvas"></span>
            </a>
            <a href="{{ url('wishlist') }}" class="icon-btn hidden lg:grid" aria-label="Wishlist" title="Wishlist">
                <x-icon name="heart" class="size-5" />
                <span x-show="$store.wishlist.count" x-text="$store.wishlist.count" x-cloak class="absolute -top-0.5 -right-0.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-canvas"></span>
            </a>
            @php($customer = auth('customer')->user())
            <a href="{{ route('account') }}" class="hidden items-center gap-2.5 rounded-xl px-2 py-1.5 text-ink transition hover:bg-card-2 lg:flex" aria-label="{{ $customer ? 'My account' : 'Sign in' }}">
                <span @class(['grid size-9 place-items-center rounded-full ring-1', 'bg-card-2 ring-line' => ! $customer, 'bg-brand-500/10 text-highlight ring-brand-500/30' => $customer])><x-icon name="user" class="size-[18px]" /></span>
                <span class="hidden text-left leading-tight xl:block">
                    <span class="block text-[11px] text-muted">{{ $customer ? 'Hello, '.Str::limit($customer->firstName(), 12) : 'Welcome' }}</span>
                    <span class="block text-[13px] font-semibold">{{ $customer ? 'My account' : 'Sign in' }}</span>
                </span>
            </a>
            <button type="button" @click="$store.ui.cartOpen = true" class="relative flex items-center gap-2.5 rounded-xl p-2 text-ink transition hover:bg-card-2 lg:ml-1 lg:py-1.5 lg:pr-3 lg:pl-1.5" aria-label="Open cart">
                <span class="relative grid size-9 place-items-center rounded-full bg-ink text-canvas lg:size-9">
                    <x-icon name="shopping-bag" class="size-[18px]" />
                    <span x-show="$store.cart.count" x-text="$store.cart.count" x-cloak class="absolute -top-1 -right-1.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-accent-600 px-1 text-[10px] font-bold text-white ring-2 ring-canvas"></span>
                </span>
                <span class="hidden text-left leading-tight xl:block">
                    <span class="block text-[11px] text-muted">Cart</span>
                    <span class="price block text-[13px]" x-text="'৳' + $store.cart.subtotal.toLocaleString('en-US')">৳0</span>
                </span>
            </button>
        </div>
    </div>

    @include('storefront.partials.mega-menu')
</header>
