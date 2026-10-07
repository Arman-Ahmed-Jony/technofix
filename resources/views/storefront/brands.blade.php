<x-layouts.storefront page-key="brands">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Brands', 'url' => null]]" />

        <header class="mesh-dark grain relative isolate mt-4 overflow-hidden rounded-[28px] px-6 py-10 text-white sm:px-10 sm:py-12">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <p class="eyebrow text-accent-300">Official brands</p>
            <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] sm:text-[42px]">Shop by brand</h1>
            <p class="mt-3 max-w-lg text-[15px] text-white/65">{{ $groups->flatten()->count() }} brands, all 100% genuine with official warranty.</p>

            <nav class="mt-7 flex flex-wrap gap-1.5" aria-label="Jump to letter">
                @foreach ($groups->keys() as $letter)
                    <a href="#brands-{{ $letter === '#' ? 'num' : $letter }}" class="grid size-9 place-items-center rounded-xl bg-white/10 font-mono text-[13px] font-medium ring-1 ring-white/15 transition hover:bg-white/20">{{ $letter }}</a>
                @endforeach
            </nav>
        </header>

        @if ($zones->isNotEmpty())
            <section class="mt-8 grid gap-3 sm:grid-cols-3">
                @foreach ($zones as $zone)
                    <x-brand-zone-card :name="$zone->name" :tagline="$zone->zone_tagline" :url="$zone->url()" :theme="\App\Support\ZoneThemes::get($zone->zone_theme)" :logo="$zone->logoUrl()" compact class="min-h-28" />
                @endforeach
            </section>
        @endif

        {{-- A–Z directory: one card per letter, flowing in columns --}}
        <div class="mt-8 gap-4 sm:columns-2 lg:columns-3 xl:columns-4">
            @foreach ($groups as $letter => $brands)
                <section id="brands-{{ $letter === '#' ? 'num' : $letter }}" class="mb-4 break-inside-avoid scroll-mt-40 rounded-[22px] border border-line bg-card p-4 shadow-card lg:scroll-mt-36">
                    <h2 class="mb-2 flex items-center gap-3">
                        <span class="grid size-9 place-items-center rounded-xl bg-card-2 font-display text-[16px] font-bold text-highlight ring-1 ring-line">{{ $letter }}</span>
                        <span class="h-px flex-1 bg-line"></span>
                        <span class="font-mono text-[11px] text-faint">{{ $brands->count() }}</span>
                    </h2>
                    <ul>
                        @foreach ($brands as $brand)
                            <li>
                                <a href="{{ $brand->url() }}" class="group -mx-1 flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-card-2">
                                    @if ($logo = $brand->logoUrl())
                                        <span class="grid h-7 w-12 shrink-0 place-items-center rounded-lg bg-white p-1 ring-1 ring-line"><img src="{{ $logo }}" alt="" loading="lazy" class="max-h-full max-w-full object-contain"></span>
                                    @endif
                                    <span class="flex-1 truncate text-[14px] font-medium text-ink group-hover:text-highlight">{{ $brand->name }}</span>
                                    <span class="font-mono text-[11.5px] text-faint">{{ $brand->products_count }}</span>
                                    <x-icon name="chevron-right" class="size-3.5 text-faint transition group-hover:translate-x-0.5 group-hover:text-highlight" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.storefront>
