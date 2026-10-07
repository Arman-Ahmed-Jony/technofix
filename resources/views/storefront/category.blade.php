@php
    $searchQuery ??= null;
    $stats ??= null;
    $brand ??= null;
    $metaDescription ??= null;
    $categoryDescription ??= null;
    $eyebrow ??= 'Category';
    $noindex ??= false;
    $guide ??= null;
    $subcategoriesLabel ??= null;
    $filterCount = count($active);
    // Canonical URL: this page without filters; a category filtered by exactly one brand points to /category/brand.
    $canonical = url()->current().($products->currentPage() > 1 ? '?page='.$products->currentPage() : '');
    $onlyBrand = count($selected['brand']) === 1 && ! $selected['stock'] && ! array_filter($selected['f']) && ! $selected['min'] && ! $selected['max'];
    if ($category && ! $brand && $onlyBrand && collect(\App\Support\CatalogCache::data()['brands'])->contains('slug', $selected['brand'][0])) {
        $canonical = \App\Support\Catalog::categoryBrandUrl($category->slug, $selected['brand'][0]);
    }
    $clearUrl = url()->current().($searchQuery !== null ? '?q='.urlencode($searchQuery) : '');
    // Brand stores with an "Exclusive Zone" get their theme colours in the header band.
    $zone = $brand?->is_zone ? \App\Support\ZoneThemes::get($brand->zone_theme) : null;
    $zone = $zone && ! $zone['light'] ? $zone : null; // the white "light card" theme keeps the normal header
    $logo = $brand?->logoUrl();
    // Analytics (App\Support\Tracking): the products listed on this page, and what was searched for.
    $tracking = app(\App\Support\Tracking::class);
    $tracking->push('view_item_list', [
        'item_list_id' => $searchQuery !== null ? 'search' : ($category?->slug ?? 'brand-'.$brand?->slug),
        'item_list_name' => $searchQuery !== null ? 'Search results' : $heading,
        'items' => $products->getCollection()->take(12)->values()->map(fn ($p, $i) => [...\App\Support\Tracking::item($p), 'index' => $i])->all(),
    ]);
    if ($searchQuery !== null) {
        $tracking->push('search', ['search_term' => $searchQuery]);
    }
@endphp

<x-layouts.storefront :title="$title" :description="$metaDescription" :canonical="$canonical" :noindex="$noindex || $searchQuery !== null">
    @if ($guide && $guide['faqs'])
        <x-slot:head>
            {{ \App\Support\Seo::jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn ($faq) => ['@type' => 'Question', 'name' => $faq['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']]], $guide['faqs']),
            ]) }}
        </x-slot:head>
    @endif
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="$breadcrumbs" />

        {{-- Header band --}}
        <div @class([
            'relative isolate mt-4 overflow-hidden rounded-[28px] p-6 sm:p-8',
            'border border-line bg-card shadow-card' => ! $zone,
            'text-white shadow-lg ring-1 ring-white/10 '.($zone['bg'] ?? '') => $zone,
        ])>
            @if ($zone)
                <div class="absolute -top-16 -right-10 -z-10 size-72 rounded-full blur-3xl {{ $zone['glow'] }}"></div>
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(rgb(255_255_255/0.10)_1px,transparent_1px)] [mask-image:linear-gradient(to_left,black,transparent_70%)] [background-size:14px_14px]"></div>
            @else
                <div class="absolute -top-24 -right-16 -z-10 size-80 rounded-full bg-brand-500/10 blur-3xl"></div>
            @endif

            @if ($logo)
                <span class="absolute top-1/2 right-6 hidden h-24 w-44 -translate-y-1/2 place-items-center rounded-3xl bg-white p-4 shadow-card md:grid lg:right-12">
                    <img src="{{ $logo }}" alt="{{ $brand->name }}" class="max-h-full max-w-full object-contain">
                </span>
            @elseif ($zone)
                <span class="absolute top-1/2 right-8 hidden -translate-y-1/2 md:block lg:right-14">
                    <span class="grid size-24 place-items-center rounded-[28px] bg-white/15 ring-1 ring-white/25 backdrop-blur-sm"><x-icon :name="$zone['icons'][0]" class="size-11" /></span>
                    <span class="absolute -top-4 -left-6 grid size-11 place-items-center rounded-2xl bg-white/20 ring-1 ring-white/25 backdrop-blur-sm motion-safe:animate-float"><x-icon :name="$zone['icons'][1]" class="size-5" /></span>
                </span>
            @elseif ($category)
                <x-icon :name="$category->icon" class="absolute top-1/2 right-6 -z-10 hidden size-40 -translate-y-1/2 opacity-80 [stroke:url(#tf-grad)] [stroke-width:0.7] md:block lg:right-12" />
            @endif

            @if ($zone)
                <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-[10.5px] font-bold tracking-[0.08em] uppercase ring-1 ring-white/20"><x-icon name="sparkles" class="size-3.5" /> {{ $eyebrow }}</span>
            @else
                <p class="eyebrow">{{ $eyebrow }}</p>
            @endif
            <h1 @class([
                'mt-2.5 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] sm:text-[38px] md:max-w-[70%]',
                'text-ink' => ! $zone,
                'uppercase font-bold' => $zone,
            ])>{{ $heading }}</h1>
            @if ($zone && $brand->zone_tagline)
                <p class="mt-1.5 text-[14px] text-white/75">{{ $brand->zone_tagline }}</p>
            @endif
            <div class="mt-4 flex flex-wrap items-center gap-2 text-[12.5px]">
                <span @class(['chip py-1', 'bg-card-2 text-ink-2 ring-1 ring-line' => ! $zone, 'bg-white/15 text-white ring-1 ring-white/20' => $zone])><span class="font-mono">{{ number_format($products->total()) }}</span> {{ Str::plural('product', $products->total()) }}</span>
                @if ($stats && $stats['from'])
                    <span @class(['chip py-1', 'bg-card-2 text-ink-2 ring-1 ring-line' => ! $zone, 'bg-white/15 text-white ring-1 ring-white/20' => $zone])>From <span class="font-mono">৳{{ number_format($stats['from']) }}</span></span>
                @endif
                @if ($facets['brands']->count())
                    <span class="chip bg-card-2 py-1 text-ink-2 ring-1 ring-line"><span class="font-mono">{{ $facets['brands']->count() }}</span> brands</span>
                @endif
                @if ($zone)
                    <span class="chip bg-white/15 py-1 text-white ring-1 ring-white/20"><x-icon name="rosette-discount-check" class="size-3.5" /> 100% genuine</span>
                @else
                    <span class="chip bg-emerald-500/10 py-1 text-emerald-700 ring-1 ring-emerald-500/20 dark:text-emerald-300"><x-icon name="shield-check" class="size-3.5" /> Official warranty</span>
                @endif
            </div>

            @if ($subcategories->isNotEmpty())
                @if ($subcategoriesLabel)
                    <p @class(['mt-6 text-[11px] font-semibold tracking-[0.14em] uppercase', 'text-faint' => ! $zone, 'text-white/60' => $zone])>{{ $subcategoriesLabel }}</p>
                @endif
                {{-- Phones: one swipeable row (py-1 keeps the 1px rings from being clipped). Desktop: wraps, nothing clipped. --}}
                <div class="scrollbar-none -mx-6 mt-5 flex gap-2 overflow-x-auto px-6 py-1 sm:-mx-8 sm:px-8 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0">
                    @foreach ($subcategories as $sub)
                        <a href="{{ $sub->url }}" @class([
                            'group flex shrink-0 items-center gap-2 rounded-full py-1.5 pr-4 pl-1.5 text-[13px] font-medium ring-1 transition',
                            'bg-card-2 text-ink ring-line hover:ring-brand-500/50' => ! $zone,
                            'bg-white/10 text-white ring-white/20 backdrop-blur-sm hover:bg-white/20' => $zone,
                        ])>
                            <span @class(['grid size-7 place-items-center rounded-full ring-1', 'bg-card ring-line' => ! $zone, 'bg-white ring-white/30' => $zone])><x-icon :name="$sub->icon" class="size-4 [stroke:url(#tf-grad)] [stroke-width:1.6]" /></span>
                            {{ $sub->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[272px_minmax(0,1fr)]">
            {{-- ─── Filters: sidebar on desktop, bottom sheet on mobile ─── --}}
            <form id="filters" method="get" action="{{ url()->current() }}"
                @change="window.matchMedia('(min-width: 1024px)').matches && $el.requestSubmit()">
                @if ($searchQuery !== null)
                    <input type="hidden" name="q" value="{{ $searchQuery }}">
                @endif

                <div x-show="$store.ui.filtersOpen" x-transition.opacity x-cloak class="fixed inset-0 z-50 bg-navy-950/60 backdrop-blur-sm lg:hidden" @click="$store.ui.filtersOpen = false"></div>

                <aside class="fixed inset-x-0 bottom-0 z-50 flex max-h-[88vh] flex-col rounded-t-[28px] border-t border-line bg-canvas shadow-2xl transition-transform duration-300 max-lg:translate-y-full lg:static lg:z-auto lg:max-h-none lg:rounded-none lg:border-0 lg:bg-transparent lg:shadow-none"
                    :class="$store.ui.filtersOpen && 'max-lg:translate-y-0!'" aria-label="Filters">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4 lg:hidden">
                        <p class="font-display text-lg font-semibold text-ink">Filters</p>
                        <button type="button" class="icon-btn" @click="$store.ui.filtersOpen = false" aria-label="Close filters"><x-icon name="x" class="size-5" /></button>
                    </div>

                    {{-- Phones: the bottom sheet scrolls on its own. Computers: part of the page, so the mouse wheel always scrolls the page. --}}
                    <div class="flex-1 overflow-y-auto overscroll-contain px-5 lg:space-y-3 lg:overflow-visible lg:overscroll-auto lg:px-0">
                        <div class="border-b border-line py-4 lg:rounded-2xl lg:border lg:bg-card lg:p-4 lg:shadow-card" x-data="{ open: true }">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-[14px] font-semibold text-ink">
                                Price range <x-icon name="chevron-down" class="size-4 text-faint transition" ::class="!open && '-rotate-90'" />
                            </button>
                            <div x-show="open" x-collapse>
                                <div class="mt-3 flex items-center gap-2">
                                    <input type="number" name="min" value="{{ $selected['min'] }}" min="0" inputmode="numeric" placeholder="৳{{ number_format($priceBounds['min']) }}" class="input h-10 px-3 font-mono text-[13px]" aria-label="Minimum price">
                                    <span class="text-faint">–</span>
                                    <input type="number" name="max" value="{{ $selected['max'] }}" min="0" inputmode="numeric" placeholder="৳{{ number_format($priceBounds['max']) }}" class="input h-10 px-3 font-mono text-[13px]" aria-label="Maximum price">
                                </div>
                                <button type="submit" class="btn btn-sm btn-soft mt-2.5 hidden w-full lg:flex">Apply price</button>
                            </div>
                        </div>

                        @php
                            $groups = [
                                ['title' => 'Availability', 'name' => 'stock[]', 'options' => $facets['stock'], 'selected' => $selected['stock']],
                                ['title' => 'Brand', 'name' => 'brand[]', 'options' => $facets['brands'], 'selected' => $selected['brand']],
                            ];
                            foreach ($facets['specs'] as $key => $options) {
                                $groups[] = ['title' => $key, 'name' => "f[{$key}][]", 'options' => $options, 'selected' => $selected['f'][$key] ?? []];
                            }
                        @endphp

                        @foreach ($groups as $group)
                            @continue($group['options']->isEmpty())
                            <div class="border-b border-line py-4 lg:rounded-2xl lg:border lg:bg-card lg:p-4 lg:shadow-card" x-data="{ open: true, more: false }">
                                <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-[14px] font-semibold text-ink">
                                    {{ $group['title'] }} <x-icon name="chevron-down" class="size-4 text-faint transition" ::class="!open && '-rotate-90'" />
                                </button>
                                <div x-show="open" x-collapse>
                                    <ul class="mt-2.5 space-y-0.5">
                                        @foreach ($group['options'] as $option)
                                            @php($checked = in_array($option['value'], $group['selected'], true))
                                            <li @if ($loop->index >= 6 && ! $checked) x-show="more" x-cloak @endif>
                                                <label class="flex cursor-pointer items-center gap-2.5 rounded-lg px-1.5 py-1.5 text-[13.5px] text-ink-2 transition hover:bg-card-2 hover:text-ink">
                                                    <input type="checkbox" class="checkbox" name="{{ $group['name'] }}" value="{{ $option['value'] }}" @checked($checked)>
                                                    <span class="flex-1">{{ $option['label'] }}</span>
                                                    <span class="font-mono text-[11px] text-faint">{{ $option['count'] }}</span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($group['options']->count() > 6)
                                        <button type="button" @click="more = !more" class="mt-1 px-1.5 text-[13px] font-semibold text-highlight" x-text="more ? 'Show less' : 'Show {{ $group['options']->count() - 6 }} more'"></button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 border-t border-line px-5 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] lg:hidden">
                        <a href="{{ $clearUrl }}" class="btn btn-outline">Clear all</a>
                        <button type="submit" class="btn btn-ink">Show results</button>
                    </div>
                </aside>
            </form>

            {{-- ─── Results ─── --}}
            <div class="min-w-0">
                <div class="mb-4 flex items-center justify-between gap-3 rounded-2xl border border-line bg-card p-2 shadow-card lg:pl-4">
                    <button type="button" class="btn btn-sm btn-soft lg:hidden" @click="$store.ui.filtersOpen = true">
                        <x-icon name="adjustments-horizontal" class="size-4" /> Filters
                        @if ($filterCount)
                            <span class="grid size-5 place-items-center rounded-full bg-brand-500 font-mono text-[11px] text-white">{{ $filterCount }}</span>
                        @endif
                    </button>
                    <p class="hidden text-[13.5px] text-muted lg:block">
                        @if ($products->total())
                            Showing <span class="font-mono text-ink">{{ $products->firstItem() }}–{{ $products->lastItem() }}</span> of <span class="font-mono text-ink">{{ $products->total() }}</span>
                        @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <label for="sort" class="hidden text-[13px] text-muted sm:block">Sort</label>
                        <select id="sort" name="sort" form="filters" aria-label="Sort products" onchange="this.form.requestSubmit()" class="input h-9 w-auto cursor-pointer rounded-lg py-0 pr-8 pl-3 text-[13px] font-medium">
                            @foreach (\App\Support\ProductListing::SORTS as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" form="filters" onchange="this.form.requestSubmit()" class="input hidden h-9 w-auto cursor-pointer rounded-lg py-0 pr-8 pl-3 text-[13px] font-medium sm:block" aria-label="Products per page">
                            @foreach (\App\Support\ProductListing::PER_PAGE as $n)
                                <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per page</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($filterCount)
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        @foreach ($active as $chip)
                            <a href="{{ $chip['url'] }}" class="chip gap-1.5 bg-brand-500/10 py-1 pr-2 pl-3 text-[12.5px] text-highlight ring-1 ring-brand-500/25 transition hover:bg-brand-500/15">
                                {{ $chip['label'] }} <x-icon name="x" class="size-3.5" />
                            </a>
                        @endforeach
                        <a href="{{ $clearUrl }}" class="ml-1 text-[13px] font-semibold text-muted hover:text-rose-500">Clear all</a>
                    </div>
                @endif

                @if ($products->isEmpty())
                    <div class="rounded-[28px] border border-dashed border-line-strong bg-card px-6 py-16 text-center">
                        <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-card-2 ring-1 ring-line"><x-icon name="search" class="size-7 [stroke:url(#tf-grad)]" /></span>
                        @if ($filterCount)
                            <p class="mt-5 font-display text-lg font-semibold text-ink">No products match your selection</p>
                            <p class="mt-1 text-sm text-muted">Try removing a filter, or call us — we can source it for you.</p>
                            <a href="{{ $clearUrl }}" class="btn btn-ink mt-6">Clear filters</a>
                        @else
                            <p class="mt-5 font-display text-lg font-semibold text-ink">Nothing listed here yet</p>
                            <p class="mt-1 text-sm text-muted">Call or WhatsApp us — we can usually source it for you quickly.</p>
                            <div class="mt-6 flex flex-wrap justify-center gap-2">
                                <a href="{{ app(\App\Settings\StoreSettings::class)->telHref() }}" class="btn btn-ink"><x-icon name="phone-call" class="size-4" /> Call us</a>
                                @if ($category)
                                    <a href="{{ $category->url }}" class="btn btn-outline">All {{ $category->name }}</a>
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <h2 class="sr-only">Products</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 xl:grid-cols-4">
                        @foreach ($products as $product)
                            <x-product.card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-10">
                        {{ $products->onEachSide(1)->links('storefront.partials.pagination') }}
                    </div>
                @endif
            </div>
        </div>

        @if ($brand && filled($categoryDescription))
            <section class="mt-14 rounded-[28px] border border-line bg-card p-6 shadow-card sm:p-10">
                <p class="eyebrow">About {{ $brand->name }}</p>
                <div class="prose-tf mt-4 max-w-3xl">{!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($categoryDescription)->toHtml() !!}</div>
            </section>
        @endif

        @if ($guide)
            {{-- Text + questions: the page's own (Catalog → Categories / Brands → Category pages) or the defaults (Content → Buying guide) --}}
            <section class="mt-14 grid gap-8 rounded-[28px] border border-line bg-card p-6 shadow-card sm:p-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                <div>
                    <p class="eyebrow">Buying guide</p>
                    <h2 class="mt-2.5 font-display text-xl font-semibold tracking-tight text-ink sm:text-2xl">{{ $guide['name'] }} price in Bangladesh</h2>
                    <div class="prose-tf mt-3 text-sm text-muted">{!! $guide['html'] !!}</div>
                </div>
                @if ($guide['faqs'])
                    <div class="divide-y divide-line">
                        @foreach ($guide['faqs'] as $faq)
                            <details class="group py-4 first:pt-0">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-[14.5px] font-medium text-ink">
                                    {{ $faq['question'] }}
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-card-2 ring-1 ring-line transition group-open:rotate-45"><x-icon name="plus" class="size-3.5" /></span>
                                </summary>
                                <p class="mt-2.5 pr-10 text-sm leading-relaxed text-muted">{!! nl2br(e($faq['answer'])) !!}</p>
                            </details>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts.storefront>
