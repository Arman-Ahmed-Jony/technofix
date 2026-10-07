@props(['product'])
@php
    $cartItem = $product->cartItem();
    $hotline = app(\App\Settings\StoreSettings::class)->hotline;
    $specs = $product->cardSpecs();
@endphp
<article {{ $attributes->class('group relative flex h-full flex-col rounded-2xl border border-line bg-card p-2.5 shadow-card transition duration-300 hover:-translate-y-1 hover:border-line-strong hover:shadow-lift sm:p-3') }}>
    <div class="relative">
        <a href="{{ $product->url }}" class="block overflow-hidden rounded-xl" tabindex="-1" aria-hidden="true">
            <x-product.image :product="$product" class="transition duration-500 ease-out group-hover:scale-[1.04]" />
        </a>

        <div class="pointer-events-none absolute top-2 left-2 flex flex-col items-start gap-1">
            @if ($product->discount_percent)
                <span class="chip bg-accent-800 text-white shadow-[0_6px_14px_-6px_rgb(233_78_27/0.9)]">−{{ $product->discount_percent }}%</span>
            @endif
            @if ($deal = $product->deal())
                <span class="chip gap-1 bg-[linear-gradient(110deg,#e94e1b,#f39200)] text-[10px] font-semibold tracking-wider text-white uppercase"><x-icon name="bolt" class="size-3" /> {{ $deal['badge'] }}</span>
            @endif
            @if (in_array('new', $product->flags, true))
                <span class="chip bg-ink text-[10px] tracking-wider text-canvas uppercase">New</span>
            @endif
        </div>

        <div class="absolute top-2 right-2 flex flex-col gap-1.5 transition duration-300 sm:translate-x-1 sm:opacity-0 sm:group-focus-within:translate-x-0 sm:group-focus-within:opacity-100 sm:group-hover:translate-x-0 sm:group-hover:opacity-100">
            <button type="button" @click="$store.wishlist.toggle({{ $product->id }})"
                :class="$store.wishlist.has({{ $product->id }}) ? 'text-rose-500' : 'text-muted'"
                class="grid size-8 place-items-center rounded-full bg-card/85 shadow-sm ring-1 ring-line backdrop-blur transition hover:text-rose-500"
                aria-label="Add to wishlist">
                <x-icon name="heart" class="size-4" />
            </button>
            <button type="button" @click="$store.compare.toggle({{ $product->id }})"
                :class="$store.compare.has({{ $product->id }}) ? 'text-highlight' : 'text-muted'"
                class="grid size-8 place-items-center rounded-full bg-card/85 shadow-sm ring-1 ring-line backdrop-blur transition hover:text-highlight"
                aria-label="Add to compare">
                <x-icon name="arrows-exchange" class="size-4" />
            </button>
        </div>
    </div>

    <div class="mt-3 flex flex-1 flex-col px-1">
        <div class="flex items-center justify-between gap-2">
            <span class="truncate text-[10.5px] font-semibold tracking-[0.12em] text-faint uppercase">{{ $product->brand_name }}</span>
            <span class="flex shrink-0 items-center gap-1.5 text-[11px] font-medium text-muted">
                <span class="size-1.5 rounded-full {{ $product->stock->dotClass() }}"></span>{{ $product->stock->label() }}
            </span>
        </div>

        <h3 class="mt-1.5 line-clamp-2 min-h-[2.7em] text-[13px] leading-[1.35] font-medium text-ink sm:text-[13.5px]">
            <a href="{{ $product->url }}" class="transition hover:text-highlight">{{ $product->name }}</a>
        </h3>
        @if ($product->colors->count() > 1)
            <p class="mt-1 flex items-center gap-1" aria-label="{{ $product->colors->count() }} colors: {{ $product->colors->pluck('name')->join(', ') }}">
                @foreach ($product->colors->take(6) as $c)
                    <span class="size-3 rounded-full ring-1 ring-black/10 dark:ring-white/20" style="background: {{ preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', (string) $c->hex) ? $c->hex : '#cbd5e1' }}" title="{{ $c->name }}"></span>
                @endforeach
                @if ($product->colors->count() > 6)<span class="text-[11px] text-muted">+{{ $product->colors->count() - 6 }}</span>@endif
            </p>
        @endif
        @if ($product->rating_count)
            <p class="mt-1 flex items-center gap-1 text-[12px] text-muted" aria-label="Rated {{ number_format($product->rating_avg, 1) }} out of 5 from {{ $product->rating_count }} reviews">
                <x-icon name="star-filled" class="size-3.5 text-amber-400" /><span class="font-semibold text-ink-2">{{ number_format($product->rating_avg, 1) }}</span> ({{ $product->rating_count }})
            </p>
        @endif

        @if ($specs)
            <div class="mt-2.5 hidden flex-wrap gap-1 sm:flex">
                @foreach ($specs as $spec)
                    <span class="spec-chip">{{ $spec }}</span>
                @endforeach
            </div>
        @endif

        @php
            $pickColors = $product->stock->isPurchasable() && $product->colors->isNotEmpty();
            $swatch = fn ($c) => preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', (string) $c->hex) ? $c->hex : '#cbd5e1';
        @endphp
        <div class="relative mt-auto flex items-end justify-between gap-2 pt-3.5" @if ($pickColors) x-data="{ pick: false }" @keydown.escape="pick = false" @click.outside="pick = false" @endif>
            <x-product.price :product="$product" class="min-w-0" />

            @if ($pickColors)
                {{-- Color versions: the cart button opens a small picker; choosing a color adds that version. --}}
                <button type="button" @click="pick = ! pick" :aria-expanded="pick.toString()"
                    class="btn-ink grid size-10 shrink-0 place-items-center rounded-xl transition hover:scale-105 active:scale-95"
                    aria-label="{{ $product->stock === \App\Enums\StockStatus::PreOrder ? 'Pre-order' : 'Add to cart' }}: {{ $product->name }} — choose a color">
                    <x-icon name="shopping-bag-plus" class="size-[18px]" />
                </button>
                <div x-show="pick" x-cloak x-transition.origin.bottom.right
                    class="absolute inset-x-0 bottom-full z-30 mb-2 rounded-xl border border-line bg-card p-1.5 shadow-lift sm:p-2">
                    <p class="truncate px-1.5 pt-0.5 pb-1 text-[11.5px] font-semibold text-muted">Choose a color</p>
                    <div class="grid max-h-48 gap-0.5 overflow-y-auto">
                        @foreach ($product->colors as $c)
                            <button type="button" @disabled(! $c->is_available)
                                @if ($c->is_available) @click="pick = false; $store.cart.add({ ...@js($cartItem), color: @js($c->name) })" @endif
                                class="flex w-full items-center gap-2 rounded-lg px-1.5 py-1.5 text-left text-[12.5px] font-medium text-ink transition enabled:hover:bg-card-2 disabled:cursor-not-allowed disabled:text-faint">
                                <span @class(['size-4 shrink-0 rounded-full ring-1 ring-black/10 dark:ring-white/20', 'opacity-40' => ! $c->is_available]) style="background: {{ $swatch($c) }}"></span>
                                <span class="min-w-0 flex-1 leading-tight">
                                    <span class="block truncate">{{ $c->name }}</span>
                                    @unless ($c->is_available)<span class="block text-[10.5px] font-normal">Sold out</span>@endunless
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @elseif ($product->stock->isPurchasable())
                <button type="button" @click="$store.cart.add(@js($cartItem))"
                    class="btn-ink grid size-10 shrink-0 place-items-center rounded-xl transition hover:scale-105 active:scale-95"
                    aria-label="{{ $product->stock === \App\Enums\StockStatus::PreOrder ? 'Pre-order' : 'Add to cart' }}: {{ $product->name }}">
                    <x-icon name="shopping-bag-plus" class="size-[18px]" />
                </button>
            @elseif ($product->stock === \App\Enums\StockStatus::CallForPrice)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotline) }}" class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/25 transition hover:bg-accent-500/15 dark:text-accent-400" aria-label="Call for price">
                    <x-icon name="phone-call" class="size-[18px]" />
                </a>
            @else
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-card-2 text-faint ring-1 ring-line" title="{{ $product->stock->label() }}">
                    <x-icon name="bell" class="size-[18px]" />
                </span>
            @endif
        </div>
    </div>
</article>
