@use('App\Enums\StockStatus')
@use('App\Support\Money')
@php
    $contact = app(\App\Settings\StoreSettings::class)->toArray();
    $tel = 'tel:'.preg_replace('/[^0-9+]/', '', $contact['hotline']);
    $whatsapp = $contact['whatsapp'] ? 'https://wa.me/'.$contact['whatsapp'].'?text='.rawurlencode("I'm interested in: {$product->name}") : url('contact');
    $cartItem = $product->cartItem();
    app(\App\Support\Tracking::class)->push('view_item', array_filter(['currency' => \App\Support\Tracking::CURRENCY, 'value' => $product->sale_price, 'items' => [\App\Support\Tracking::item($product)]]));
    // Color versions (Admin → Products → Colors): the first available one is pre-selected; all sold out = can't order.
    $colors = $product->colors;
    $firstColor = $colors->firstWhere('is_available', true)?->name;
    $purchasable = $product->stock->isPurchasable() && ($colors->isEmpty() || $firstColor !== null);
    $gallery = $product->gallery();
    $specTable = $product->specTable();
    $description = $product->descriptionHtml();
    $feedbackSettings = app(\App\Settings\ReviewSettings::class);
    $delivery = collect(app(\App\Settings\DeliverySettings::class)->active())->where('type', 'delivery');
    // Google product details. "Call for price" items have no price, so no offer (Google needs a price for one).
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'mpn' => $product->model,
        'brand' => $product->brand_name ? ['@type' => 'Brand', 'name' => $product->brand_name] : null,
        'category' => $product->category_name,
        'description' => $metaDescription,
        'image' => array_column($gallery, 'large') ?: null,
        'offers' => $product->sale_price === null ? null : array_filter([
            '@type' => 'Offer',
            'url' => $product->url,
            'priceCurrency' => 'BDT',
            'price' => $product->sale_price,
            'priceValidUntil' => ($deal = $product->deal()) ? $deal['ends_at']->toDateString() : null,
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => 'https://schema.org/'.match ($product->stock) {
                StockStatus::InStock => 'InStock',
                StockStatus::PreOrder => 'PreOrder',
                default => 'OutOfStock',
            },
            'seller' => ['@type' => 'Organization', 'name' => app(\App\Support\Seo::class)->siteName()],
        ]),
        'aggregateRating' => $product->rating_count ? [
            '@type' => 'AggregateRating',
            'ratingValue' => $product->rating_avg,
            'reviewCount' => $product->rating_count,
            'bestRating' => 5,
            'worstRating' => 1,
        ] : null,
        'review' => $product->rating_count ? app(\App\Services\ProductFeedback::class)->reviews($product)->take(5)->map(fn ($review) => [
            '@type' => 'Review',
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $review->rating, 'bestRating' => 5],
            'author' => ['@type' => 'Person', 'name' => $review->authorName()],
            'datePublished' => ($review->approved_at ?? $review->created_at)->toDateString(),
            'reviewBody' => $review->body,
        ])->all() : null,
    ];
@endphp

<x-layouts.storefront :title="$title" :description="$metaDescription" type="product" :image="$product->shareImage()">
    <x-slot:head>
        {{ \App\Support\Seo::jsonLd(array_filter($schema)) }}
        @if ($product->sale_price !== null)
            <meta property="product:price:amount" content="{{ $product->sale_price }}">
            <meta property="product:price:currency" content="BDT">
        @endif
    </x-slot:head>

    <div class="container-x pt-5 max-lg:pb-20 lg:pt-7" x-data="{ qty: 1, color: @js($firstColor) }">
        <x-breadcrumbs :items="$breadcrumbs" />

        {{-- ─── Summary ─── --}}
        <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:gap-12">
            <div class="self-start lg:sticky lg:top-[138px]" x-data="gallery(@js(array_map(fn ($image) => ['large' => $image['large'], 'thumb' => $image['thumb']], $gallery)))">
                <div class="relative overflow-hidden rounded-[28px] border border-line bg-card p-3 shadow-card">
                    @if ($gallery)
                        <div class="relative aspect-square overflow-hidden rounded-[22px] bg-white" @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)">
                            @foreach ($gallery as $i => $image)
                                <a href="{{ $image['large'] }}" @click.prevent="openAt({{ $i }})" class="absolute inset-0 cursor-zoom-in transition-opacity duration-300" aria-label="Open image {{ $i + 1 }} full screen"
                                    @if ($i > 0) style="opacity:0;pointer-events:none" @endif
                                    :style="active === {{ $i }} ? 'opacity:1' : 'opacity:0;pointer-events:none'">
                                    <img src="{{ $image['large'] }}" alt="{{ $product->name }}{{ $i ? ' — image '.($i + 1) : '' }}" width="1000" height="1000"
                                        @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="size-full object-contain p-6">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-product.image :product="$product" icon-class="size-[48%]" class="rounded-[22px]" />
                    @endif
                    <div class="pointer-events-none absolute top-6 left-6 flex flex-col items-start gap-1.5">
                        @if ($product->discount_percent)
                            <span class="chip bg-accent-800 py-1 text-[12px] text-white shadow-[0_6px_14px_-6px_rgb(233_78_27/0.9)]">−{{ $product->discount_percent }}%</span>
                        @endif
                        @if (in_array('new', $product->flags, true))
                            <span class="chip bg-ink py-1 text-[11px] tracking-wider text-canvas uppercase">New</span>
                        @endif
                    </div>
                    @if ($gallery)
                        <button type="button" @click="openAt(active)" class="icon-btn absolute top-6 right-6 bg-card/80 ring-1 ring-line backdrop-blur" aria-label="Open the images full screen"><x-icon name="zoom-in" class="size-5" /></button>
                    @endif
                </div>
                @if (count($gallery) > 1)
                    <div class="mt-3 grid grid-cols-5 gap-2.5">
                        @foreach ($gallery as $i => $image)
                            <button type="button" @click="show({{ $i }})" aria-label="View image {{ $i + 1 }}"
                                class="overflow-hidden rounded-2xl border border-line bg-white p-1 transition hover:border-line-strong"
                                :class="active === {{ $i }} && 'outline-2 outline-offset-2 outline-brand-500'">
                                <img src="{{ $image['thumb'] }}" alt="" loading="lazy" decoding="async" width="200" height="200" class="aspect-square w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($gallery)
                    {{-- Full-screen viewer: arrows, ← → / Esc keys, swipe, click to zoom (resources/js/gallery.js). --}}
                    <template x-teleport="body">
                        <div x-show="open" x-cloak x-trap.noscroll="open" role="dialog" aria-modal="true" aria-label="{{ $product->name }} — images"
                            @keydown.escape.window="open && close()" @keydown.arrow-right.window="open && next()" @keydown.arrow-left.window="open && prev()"
                            x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="opacity-0"
                            class="fixed inset-0 z-[80] flex flex-col bg-navy-950/95 text-white backdrop-blur-md">
                            <div class="flex items-center justify-between gap-4 px-4 pt-[max(1rem,env(safe-area-inset-top))] pb-3 sm:px-6">
                                <div class="min-w-0">
                                    <p class="truncate text-[14px] font-semibold">{{ $product->name }}</p>
                                    <p class="font-mono text-[12px] text-white/60" x-show="count > 1"><span x-text="active + 1"></span> / <span x-text="count"></span></p>
                                </div>
                                <button type="button" @click="close()" class="grid size-11 shrink-0 place-items-center rounded-full bg-white/10 ring-1 ring-white/15 transition hover:bg-white/20" aria-label="Close"><x-icon name="x" class="size-5" /></button>
                            </div>

                            <div class="relative min-h-0 flex-1 overflow-hidden px-3 sm:px-20" @click.self="close()" @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)">
                                <template x-for="(image, i) in images" :key="i">
                                    <img x-show="active === i" :src="image.large" alt="{{ $product->name }}" draggable="false"
                                        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 scale-[0.97]" x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
                                        @click="toggleZoom($event)" @mousemove="pan($event)" @touchmove.passive="pan($event)"
                                        :class="zoom ? 'cursor-zoom-out' : 'cursor-zoom-in'"
                                        :style="zoom ? `transform: scale(2.2); transform-origin: ${origin}` : ''"
                                        class="absolute inset-0 m-auto max-h-[calc(100%-1rem)] max-w-[calc(100%-1.5rem)] rounded-2xl bg-white object-contain p-3 shadow-2xl select-none sm:max-w-[calc(100%-10rem)] sm:p-6">
                                </template>

                                <template x-if="count > 1">
                                    <div>
                                        @foreach ([['prev', 'chevron-left', 'Previous image', 'left-2 sm:left-6'], ['next', 'chevron-right', 'Next image', 'right-2 sm:right-6']] as [$action, $icon, $label, $side])
                                            <button type="button" @click.stop="{{ $action }}()" aria-label="{{ $label }}"
                                                class="absolute top-1/2 {{ $side }} z-10 grid size-10 -translate-y-1/2 place-items-center rounded-full bg-navy-950/55 ring-1 ring-white/20 sm:bg-white/12 backdrop-blur transition hover:scale-105 hover:bg-white/25 sm:size-13">
                                                <x-icon :name="$icon" class="size-6" />
                                            </button>
                                        @endforeach
                                    </div>
                                </template>
                            </div>

                            <div x-show="count > 1" class="scrollbar-none flex justify-center gap-2 overflow-x-auto px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                                <template x-for="(image, i) in images" :key="i">
                                    <button type="button" @click="show(i)" :aria-label="'View image ' + (i + 1)"
                                        class="size-14 shrink-0 overflow-hidden rounded-xl bg-white p-1 transition sm:size-16"
                                        :class="active === i ? 'opacity-100 ring-2 ring-brand-400 ring-offset-2 ring-offset-navy-950' : 'opacity-55 hover:opacity-90'">
                                        <img :src="image.thumb" alt="" loading="lazy" class="size-full object-contain">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                @endif
            </div>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($product->brand_slug)
                        <a href="{{ $product->categoryBrandUrl() }}" class="chip bg-card py-1 text-[12px] text-ink ring-1 ring-line transition hover:ring-brand-500/50">{{ $product->brand_name }}</a>
                    @endif
                    <x-stock-badge :status="$product->stock" class="py-1 text-[12px]" />
                </div>

                <h1 class="mt-4 font-display text-[24px] leading-[1.18] font-semibold tracking-[-0.025em] text-ink sm:text-[30px] lg:text-[34px]">{{ $product->name }}</h1>
                @if ($product->rating_count)
                    <a href="#reviews" class="mt-2 inline-flex items-center gap-2 text-[13px] transition hover:opacity-80">
                        <x-stars :rating="$product->rating_avg" />
                        <span class="font-semibold text-ink">{{ number_format($product->rating_avg, 1) }}</span>
                        <span class="text-muted underline-offset-2 hover:underline">{{ $product->rating_count }} {{ Str::plural('review', $product->rating_count) }}</span>
                    </a>
                @endif

                <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[13px]">
                    @foreach (array_filter(['Model' => $product->model, 'Code' => $product->sku, 'Warranty' => $product->warranty]) as $label => $value)
                        <div class="flex items-center gap-1.5"><dt class="text-muted">{{ $label }}</dt><dd class="font-mono text-[12.5px] font-medium text-ink">{{ $value }}</dd></div>
                    @endforeach
                </dl>

                <ul class="mt-6 grid gap-2.5 sm:grid-cols-2">
                    @foreach ($product->key_features ?? [] as $feature)
                        <li class="flex gap-2.5 rounded-xl bg-card px-3.5 py-3 text-[13.5px] leading-snug text-ink-2 ring-1 ring-line">
                            <x-icon name="circle-check" class="mt-px size-[18px] [stroke:url(#tf-grad)] [stroke-width:2]" /> {{ $feature }}
                        </li>
                    @endforeach
                </ul>
                <a href="#specification" class="mt-3 inline-flex items-center gap-1 text-[13px] font-semibold text-highlight">Full specification <x-icon name="arrow-down" class="size-3.5" /></a>

                {{-- Price & actions --}}
                <div id="price-box" class="relative isolate mt-6 scroll-mt-36 overflow-hidden rounded-[24px] border border-line bg-card p-5 shadow-card sm:p-6">
                    <div class="absolute -top-20 -right-20 -z-10 size-56 rounded-full bg-accent-500/10 blur-3xl"></div>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">{{ $product->discount ? 'Special price' : 'Price' }}</p>
                            <x-product.price :product="$product" size="lg" class="mt-1" />
                        </div>
                        @if ($product->discount)
                            <span class="chip bg-accent-500/10 py-1.5 text-[12.5px] text-accent-800 ring-1 ring-accent-500/25 dark:text-accent-300">You save <span class="font-mono">{{ Money::format($product->discount) }}</span></span>
                        @endif
                    </div>
                    @if ($deal = $product->deal())
                        {{-- Campaign price (Admin → Sales → Campaigns): charged at checkout until the timer ends --}}
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-[linear-gradient(110deg,#e94e1b,#f39200)] px-4 py-3 text-white shadow-[0_12px_28px_-14px_rgb(233_78_27/0.9)]">
                            <p class="flex items-center gap-2 text-[13.5px] font-semibold"><x-icon name="bolt" class="size-[18px]" /> {{ $deal['badge'] }}@if ($deal['campaign'] !== $deal['badge'])<span class="font-normal text-white/85">· {{ $deal['campaign'] }}</span>@endif</p>
                            <x-countdown :ends-at="$deal['ends_at']" size="sm" label-class="text-white/85" />
                        </div>
                    @endif
                    @if ($product->emi)
                        <p class="mt-3 flex items-center gap-1.5 text-[13px] text-muted"><x-icon name="credit-card" class="size-4 text-highlight" /> EMI available on selected bank credit cards.</p>
                    @endif

                    @if ($colors->isNotEmpty())
                        <fieldset class="mt-5">
                            <legend class="mb-2 text-[13px] text-muted">Color: <span class="font-semibold text-ink" x-text="color ?? 'sold out'"></span></legend>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($colors as $c)
                                    @php($swatch = preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', (string) $c->hex) ? $c->hex : null)
                                    <label @class([
                                        'inline-flex items-center gap-2 rounded-full bg-card py-1.5 pr-3.5 pl-1.5 text-[13px] font-medium text-ink ring-1 ring-line transition has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-brand-500',
                                        'cursor-pointer hover:ring-line-strong' => $c->is_available,
                                        'cursor-not-allowed opacity-50' => ! $c->is_available,
                                    ]) :class="color === @js($c->name) && 'ring-2 ring-brand-500! bg-brand-500/5'" title="{{ $c->is_available ? $c->name : $c->name.' — sold out' }}">
                                        <input type="radio" name="color" value="{{ $c->name }}" class="sr-only" x-model="color" @disabled(! $c->is_available)>
                                        <span class="grid size-6 place-items-center rounded-full ring-1 ring-black/10 dark:ring-white/20" style="background: {{ $swatch ?? 'conic-gradient(#e94e1b, #f39200, #26abe6, #1d71b8, #e94e1b)' }}" aria-hidden="true"></span>
                                        {{ $c->name }}
                                        @unless ($c->is_available)<span class="text-[11px] font-normal text-muted">Sold out</span>@endunless
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @if ($purchasable)
                        <div class="mt-5 flex flex-wrap gap-2.5">
                            <div class="flex h-12 items-center rounded-2xl bg-card-2 ring-1 ring-line">
                                <button type="button" class="grid h-full w-11 place-items-center text-muted hover:text-ink" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity"><x-icon name="minus" class="size-4" /></button>
                                <input type="number" x-model.number="qty" min="1" max="99" class="h-full w-10 [appearance:textfield] bg-transparent text-center font-mono text-[15px] font-medium text-ink focus:outline-none [&::-webkit-inner-spin-button]:appearance-none" aria-label="Quantity">
                                <button type="button" class="grid h-full w-11 place-items-center text-muted hover:text-ink" @click="qty = Math.min(99, qty + 1)" aria-label="Increase quantity"><x-icon name="plus" class="size-4" /></button>
                            </div>
                            <button type="button" class="btn btn-lg btn-ink min-w-36 flex-1" @click="$store.cart.add({ ...@js($cartItem), color }, qty)">
                                <x-icon name="shopping-bag-plus" class="size-5" /> {{ $product->stock === StockStatus::PreOrder ? 'Pre-order' : 'Add to cart' }}
                            </button>
                            <button type="button" class="btn btn-lg btn-primary min-w-36 flex-1" @click="$store.cart.add({ ...@js($cartItem), color }, qty, false); window.location.href = @js(url('checkout'))">
                                Buy now
                            </button>
                        </div>
                        @if (Route::has('quote'))
                            <p class="mt-3 text-[12.5px] text-muted">Buying for an office or in bulk? <a href="{{ route('quote', ['product' => $product->slug]) }}" class="link font-medium">Get a quote</a></p>
                        @endif
                    @else
                        {{-- Call for price → price request; out of stock / upcoming → "notify me". Both become leads (Admin → Leads). --}}
                        @php($askPrice = $product->stock === StockStatus::CallForPrice)
                        <div class="mt-5 rounded-2xl bg-card-2 p-4 ring-1 ring-line">
                            @if (session('enquiry_sent') === ($askPrice ? 'call_for_price' : 'stock_alert'))
                                <p class="flex items-start gap-2.5 text-[13.5px] text-emerald-700 dark:text-emerald-300" role="status">
                                    <x-icon name="circle-check" class="mt-px size-5 shrink-0" />
                                    <span>{{ $askPrice ? 'Thanks — we’ll call you with today’s price shortly.' : 'Done — we’ll let you know as soon as it’s available.' }}</span>
                                </p>
                            @else
                                <p class="text-[13.5px] text-muted">
                                    @if ($askPrice)
                                        <span class="font-semibold text-ink">Prices for this item change often.</span> Leave your number and we’ll call you with today’s price.
                                    @else
                                        Currently <span class="font-semibold text-ink">{{ Str::lower($product->stock->label()) }}</span>. Get a message when it’s available.
                                    @endif
                                </p>
                                <form method="post" action="{{ route('enquiry.store') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="mt-3 grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $askPrice ? 'call_for_price' : 'stock_alert' }}">
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                                    @if ($askPrice)
                                        <input name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Your name" aria-label="Your name" @class(['input h-11', 'border-rose-500' => $errors->has('name')])>
                                    @endif
                                    <input name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel-national" placeholder="Mobile number" aria-label="Mobile number" @class(['input h-11 font-mono', 'border-rose-500' => $errors->has('phone')])>
                                    @unless ($askPrice)
                                        <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="Email (optional)" aria-label="Email (optional)" @class(['input h-11', 'border-rose-500' => $errors->has('email')])>
                                    @endunless
                                    <button type="submit" class="btn {{ $askPrice ? 'btn-primary' : 'btn-ink' }} h-11" :disabled="sending">
                                        <x-icon :name="$askPrice ? 'phone-call' : 'bell'" class="size-4" /> {{ $askPrice ? 'Get the price' : 'Notify me' }}
                                    </button>
                                    @if (\App\Support\Turnstile::enabled('enquiry') || $errors->has(\App\Support\Turnstile::FIELD))
                                        <div class="col-span-full"><x-turnstile form="enquiry" /></div>
                                    @endif
                                </form>
                                @foreach (['name', 'phone', 'email'] as $field)
                                    <x-form.error :for="$field" />
                                @endforeach
                                <p class="mt-2 text-[12px] text-faint">{{ $askPrice ? 'Or call us:' : 'We’ll also call if you leave only your number.' }} @if ($askPrice)<a href="{{ $tel }}" class="font-mono font-medium text-ink">{{ app(\App\Settings\StoreSettings::class)->hotline }}</a>@endif</p>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2 border-t border-line pt-4">
                        <button type="button" class="btn btn-sm btn-soft" @click="$store.compare.toggle({{ $product->id }})" :class="$store.compare.has({{ $product->id }}) && 'text-highlight'">
                            <x-icon name="arrows-exchange" class="size-4" /> <span x-text="$store.compare.has({{ $product->id }}) ? 'In compare' : 'Compare'">Compare</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft" @click="$store.wishlist.toggle({{ $product->id }})" :class="$store.wishlist.has({{ $product->id }}) && 'text-rose-500'">
                            <x-icon name="heart" class="size-4" /> <span x-text="$store.wishlist.has({{ $product->id }}) ? 'Saved' : 'Save'">Save</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft"
                            @click="navigator.share ? navigator.share({ title: @js($product->name), url: @js($product->url) }) : navigator.clipboard.writeText(@js($product->url)).then(() => $store.toast.show('Link copied'))">
                            <x-icon name="share" class="size-4" /> Share
                        </button>
                    </div>
                </div>

                <ul class="mt-4 grid grid-cols-2 gap-2.5 text-[12.5px]">
                    @foreach ([
                        ['truck-delivery', 'Fast delivery', $delivery->map(fn ($option) => $option['label'].' '.$option['eta'])->join(' · ') ?: 'Delivery all over Bangladesh'],
                        ['rosette-discount-check', '100% genuine', 'Sourced from official channels'],
                        \App\Support\Warranty::none($product->warranty)
                            ? ['shield-check', 'No warranty', 'Checked and tested before delivery']
                            : ['shield-check', \App\Support\Warranty::label($product->warranty) ?? 'Official warranty', 'Official brand warranty'],
                        ['cash', 'Cash on delivery', 'Pay when you receive'],
                    ] as [$icon, $title, $text])
                        <li class="flex items-start gap-3 rounded-2xl bg-card p-3.5 ring-1 ring-line">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-card-2 ring-1 ring-line"><x-icon :name="$icon" class="size-[18px] [stroke:url(#tf-grad)] [stroke-width:1.7]" /></span>
                            <span><span class="block font-semibold text-ink">{{ $title }}</span><span class="block text-[11.5px] text-muted">{{ $text }}</span></span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-dashed border-line-strong p-4 sm:flex-row sm:items-center">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/20 dark:text-accent-400"><x-icon name="headset" class="size-5.5" /></span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-ink">Questions, or want to order by phone?</p>
                        <p class="text-[13px] text-muted">Our team is available {{ $contact['hours'] }}.</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ $tel }}" class="btn btn-sm btn-outline"><x-icon name="phone-call" class="size-4" /> Call</a>
                        <a href="{{ $whatsapp }}" class="btn btn-sm btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Details ─── --}}
        <div class="mt-12 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0">
                <nav class="scrollbar-none sticky top-[115px] z-20 -mx-4 mb-4 overflow-x-auto bg-canvas/85 px-4 py-2 backdrop-blur-xl lg:top-[122px] lg:mx-0 lg:overflow-visible lg:px-0" aria-label="Product sections">
                    <div class="inline-flex rounded-2xl bg-card-2 p-1 ring-1 ring-line">
                        @foreach (array_filter(['specification' => 'Specification', 'description' => filled(strip_tags($description)) ? 'Description' : null, 'questions' => $feedbackSettings->questions_enabled ? 'Questions' : null, 'reviews' => $feedbackSettings->reviews_enabled ? 'Reviews' : null]) as $id => $label)
                            <a href="#{{ $id }}" class="h-9 rounded-xl px-4 text-[13px] leading-9 font-semibold whitespace-nowrap text-muted transition hover:bg-card hover:text-ink">{{ $label }}</a>
                        @endforeach
                    </div>
                </nav>

                <section id="specification" class="card scroll-mt-44 p-5 sm:p-7 lg:scroll-mt-48">
                    <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Specification</h2>
                    <div class="mt-5 space-y-5">
                        @forelse ($specTable as $group => $rows)
                            <div>
                                <h3 class="eyebrow mb-2">{{ $group }}</h3>
                                <dl class="divide-y divide-line overflow-hidden rounded-2xl bg-card-2 ring-1 ring-line">
                                    @foreach ($rows as $label => $value)
                                        <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-4 px-4 py-3 text-[13.5px] sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
                                            <dt class="text-muted">{{ $label }}</dt>
                                            <dd class="font-medium text-ink">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @empty
                            <p class="text-sm text-muted">Call or WhatsApp us for the full specification.</p>
                        @endforelse
                    </div>
                </section>

                @if (filled(strip_tags($description)))
                    <section id="description" class="card mt-4 scroll-mt-44 p-5 sm:p-7 lg:scroll-mt-48">
                        <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Description</h2>
                        <div class="prose-tf mt-4">{!! $description !!}</div>
                    </section>
                @endif

                @include('storefront.partials.product-feedback')
            </div>

            <aside @class(['card self-start p-5 lg:sticky lg:top-[138px]', 'hidden' => $related->isEmpty()])>
                <h2 class="font-display text-base font-semibold tracking-tight text-ink">Related products</h2>
                <ul class="mt-3 space-y-1">
                    @foreach ($related->take(5) as $item)
                        <li>
                            <a href="{{ $item->url }}" class="group -mx-2 flex gap-3 rounded-xl p-2 transition hover:bg-card-2">
                                <x-product.image :product="$item" icon-class="size-[50%]" class="size-16 shrink-0" />
                                <span class="min-w-0 flex-1">
                                    <span class="line-clamp-2 text-[13px] leading-snug font-medium text-ink group-hover:text-highlight">{{ $item->name }}</span>
                                    <x-product.price :product="$item" class="mt-1 [&_.price]:text-[14px]" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>

        @if ($accessories->isNotEmpty())
            <section class="mt-12 lg:mt-16">
                <x-section-heading eyebrow="Complete the setup" title="Accessories for this product" />
                <x-product.scroller :products="$accessories" />
            </section>
        @endif

        @if ($related->count() > 1)
            <section class="mt-12 lg:mt-16">
                <x-section-heading eyebrow="More to explore" title="You may also like" />
                <x-product.scroller :products="$related" />
            </section>
        @endif

        {{-- Filled in the browser (the page itself is the same for every visitor). --}}
        @php(collect(['bolt', 'heart', 'arrows-exchange', 'shopping-bag-plus', 'phone-call', 'bell', 'chevron-left', 'chevron-right'])->each(fn ($icon) => app(\App\Support\IconSprite::class)->use($icon)))
        <section x-data="recentlyViewed({{ $product->id }})" data-url="{{ route('recently-viewed') }}" x-show="html" x-cloak class="mt-12 lg:mt-16">
            <x-section-heading eyebrow="Pick up where you left off" title="Recently viewed" />
            <div x-html="html"></div>
        </section>
    </div>

    {{-- Mobile buy bar (sits above the bottom navigation) --}}
    <div class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 border-t border-line bg-canvas/85 px-4 py-2.5 backdrop-blur-xl lg:hidden">
        <div class="flex items-center gap-2.5">
            <x-product.price :product="$product" class="min-w-0 flex-1" />
            @if ($purchasable)
                <button type="button" class="btn btn-sm btn-ink h-10 w-11 px-0" @click="$store.cart.add({ ...@js($cartItem), color })" aria-label="Add to cart"><x-icon name="shopping-bag-plus" class="size-[18px]" /></button>
                <button type="button" class="btn btn-sm btn-primary h-10 px-5" @click="$store.cart.add({ ...@js($cartItem), color }, 1, false); window.location.href = @js(url('checkout'))">Buy now</button>
            @else
                <a href="{{ $tel }}" class="btn btn-sm btn-primary h-10"><x-icon name="phone-call" class="size-4" /> Call us</a>
            @endif
        </div>
    </div>
</x-layouts.storefront>
