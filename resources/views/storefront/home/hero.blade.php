{{-- Hero slides (Admin → Content → Banners) + Exclusive Zone brand cards. --}}
@php
    $glows = [
        'blue' => 'bg-[radial-gradient(45%_60%_at_78%_45%,rgb(0_159_227/0.28),transparent_70%)]',
        'orange' => 'bg-[radial-gradient(45%_60%_at_78%_45%,rgb(243_116_29/0.32),transparent_70%)]',
        'teal' => 'bg-[radial-gradient(45%_60%_at_78%_45%,rgb(20_184_166/0.26),transparent_70%)]',
        'indigo' => 'bg-[radial-gradient(45%_60%_at_78%_45%,rgb(129_140_248/0.26),transparent_70%)]',
        'navy' => 'bg-[radial-gradient(45%_60%_at_78%_45%,rgb(38_171_230/0.16),transparent_70%)]',
    ];
    $builtInVisuals = ['pc-build', 'deals', 'cctv', 'repair'];
@endphp
    {{-- ─── Hero ─────────────────────────────────────────────── --}}
    <section @class(['container-x mt-4 grid gap-3 lg:mt-6 lg:gap-4', 'lg:grid-cols-[minmax(0,1fr)_340px]' => $slides->isNotEmpty() && $brandZones->isNotEmpty()])>
        @if ($slides->isNotEmpty())
        <div x-data="slider({{ count($slides) }}, 7000)" @mouseenter="stop()" @mouseleave="start()" aria-roledescription="carousel"
            class="mesh-dark grain group relative isolate h-[340px] overflow-hidden rounded-[28px] sm:h-[400px] lg:h-[456px]">
            <div class="grid-lines absolute inset-0 -z-10"></div>

            @foreach ($slides as $i => $slide)
                {{-- Visibility via :style (Alpine overrides inline styles, but only ever adds classes). --}}
                <div class="absolute inset-0 transition-opacity duration-700" @if ($i > 0) style="opacity:0;pointer-events:none" @endif
                    :style="active === {{ $i }} ? { opacity: 1, pointerEvents: 'auto' } : { opacity: 0, pointerEvents: 'none' }"
                    aria-roledescription="slide" aria-label="{{ $i + 1 }} of {{ count($slides) }}">
                    @if ($slide->style === 'image')
                        {{-- Finished banner design: the whole slide is the link --}}
                        @php($link = \App\Models\Banner::link($slide->button_url))
                        @php($mobile = $slide->imageUrl('mobile'))
                        <a @if ($link) href="{{ $link }}" @endif class="absolute inset-0 block bg-card-2" aria-label="{{ $slide->title }}">
                            @unless ($mobile)
                                {{-- No phone image: show the whole wide image on phones, over a soft blurred fill --}}
                                <img src="{{ $slide->imageUrl('desktop') }}" alt="" aria-hidden="true" class="absolute inset-0 size-full scale-110 object-cover opacity-70 blur-2xl sm:hidden" loading="lazy" decoding="async">
                            @endunless
                            <picture>
                                @if ($mobile)
                                    <source media="(max-width: 639px)" srcset="{{ $mobile }}">
                                @endif
                                <img src="{{ $slide->imageUrl('desktop') }}" alt="{{ $slide->title }}" @class(['relative size-full', 'object-cover' => $mobile, 'object-contain sm:object-cover' => ! $mobile]) @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                            </picture>
                        </a>
                    @else
                        <div class="absolute inset-0 -z-10 {{ $glows[$slide->color] ?? $glows['blue'] }}"></div>

                        <div class="flex h-full items-center justify-between gap-6 px-6 pb-12 sm:px-12 lg:px-14">
                            <div class="relative max-w-[560px]">
                                @if ($slide->eyebrow)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 py-1 pr-3 pl-1 text-[12px] font-medium text-white/85 ring-1 ring-white/15 backdrop-blur">
                                        <span class="btn-primary grid size-6 place-items-center rounded-full"><x-icon :name="$slide->icon ?: 'sparkles'" class="size-3.5" /></span>
                                        {{ $slide->eyebrow }}
                                    </span>
                                @endif
                                <h2 class="mt-4 font-display text-[30px] leading-[1.08] font-semibold tracking-[-0.035em] text-balance text-white sm:mt-5 sm:text-[42px] xl:text-[48px]">{{ $slide->titleHtml() }}</h2>
                                @if ($slide->text)
                                    <p class="mt-4 hidden max-w-md text-[15px] leading-relaxed text-white/65 sm:block">{{ $slide->text }}</p>
                                @endif
                                <div class="mt-6 flex flex-wrap gap-3 sm:mt-8">
                                    @if ($slide->button_label && ($link = \App\Models\Banner::link($slide->button_url)))
                                        <a href="{{ $link }}" class="btn btn-primary max-sm:h-10 max-sm:px-4 sm:h-12 sm:rounded-2xl sm:px-6 sm:text-[15px]">{{ $slide->button_label }} <x-icon name="arrow-right" class="size-4" /></a>
                                    @endif
                                    @if ($slide->button2_label && ($link2 = \App\Models\Banner::link($slide->button2_url)))
                                        <a href="{{ $link2 }}" class="btn btn-lg btn-glass hidden sm:inline-flex">{{ $slide->button2_label }}</a>
                                    @endif
                                </div>
                            </div>

                            {{-- Mockup visuals need desktop room: shown from 1200px; phones/tablets/narrow laptops get the text-only banner --}}
                            @if (in_array($slide->visual, $builtInVisuals, true) || ($slide->visual === 'picture' && $slide->imageUrl('picture')))
                                <div class="relative mr-6 hidden shrink-0 transition duration-700 min-[1200px]:block xl:mr-10"
                                    :style="active === {{ $i }} ? { opacity: 1, transform: 'none' } : { opacity: 0, transform: 'translateY(16px)' }">
                                    @if ($slide->visual === 'picture')
                                        <img src="{{ $slide->imageUrl('picture') }}" alt="" class="max-h-[360px] w-auto max-w-[440px] object-contain drop-shadow-[0_30px_40px_rgb(0_0_0/0.45)]" loading="lazy" decoding="async">
                                    @else
                                        @include('storefront.partials.hero.'.$slide->visual)
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @if (count($slides) > 1)
            {{-- Controls sit on a dark pill so they stay visible on light banner images --}}
            <div class="absolute bottom-4 left-5 flex items-center gap-4 rounded-full bg-navy-950/35 px-3.5 py-2 ring-1 ring-white/10 backdrop-blur-md sm:left-10 lg:bottom-6 lg:left-12">
                <div class="flex items-center gap-2">
                    @foreach ($slides as $i => $slide)
                        <button type="button" @click="go({{ $i }})" aria-label="Go to slide {{ $i + 1 }}"
                            class="relative h-1 w-8 overflow-hidden rounded-full bg-white/20 sm:w-12"
                            :style="{ backgroundColor: active === {{ $i }} ? 'rgb(255 255 255 / 0.45)' : '' }">
                            <span class="absolute inset-y-0 left-0 rounded-full bg-white transition-[width] ease-linear"
                                :class="bar === {{ $i }} ? 'w-full' : 'w-0'"
                                :style="bar === {{ $i }} ? 'transition-duration: 7000ms' : 'transition-duration: 0ms'"></span>
                        </button>
                    @endforeach
                </div>
                <div class="hidden items-center gap-2 lg:flex">
                    <button type="button" @click="prev()" class="btn-glass grid size-8 place-items-center rounded-full" aria-label="Previous slide"><x-icon name="chevron-left" class="size-4" /></button>
                    <span class="w-14 text-center font-mono text-xs text-white/50"><span class="text-white" x-text="String(active + 1).padStart(2, '0')">01</span> / {{ str_pad(count($slides), 2, '0', STR_PAD_LEFT) }}</span>
                    <button type="button" @click="next()" class="btn-glass grid size-8 place-items-center rounded-full" aria-label="Next slide"><x-icon name="chevron-right" class="size-4" /></button>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Exclusive brand zones: swipeable on phones, 3 columns on tablets, stacked beside the slider on desktop --}}
        @if ($brandZones->isNotEmpty())
        <div class="scrollbar-none -mx-4 -my-2 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-4 px-4 py-2 sm:mx-0 sm:my-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 sm:py-0 lg:grid-cols-1 lg:grid-rows-3">
            @foreach ($brandZones as $zone)
                <x-brand-zone-card :name="$zone['name']" :tagline="$zone['tagline']" :url="$zone['url']" :theme="$zone['theme']" :logo="$zone['logo']"
                    class="min-h-28 w-[82%] shrink-0 snap-start sm:w-auto lg:min-h-0 lg:px-6" />
            @endforeach
        </div>
        @endif
    </section>

