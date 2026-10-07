{{-- Four department cards: big (with the laptop illustration for the laptop category), wide, two small. --}}
@php
    $from = fn (?int $amount) => $amount ? '৳'.number_format($amount) : null;
    $big = $cards['big'];
    $wide = $cards['wide'];
    $catalog = app(\App\Support\Catalog::class);
    $wideIcons = $catalog->children($wide['category'])->pluck('icon')->filter()->unique()->take(6)->values();
    $wideIcons = $wideIcons->count() >= 3 ? $wideIcons : collect(array_fill(0, 6, $wide['category']->icon));
@endphp
<section class="container-x mt-12 lg:mt-16">
    <x-section-heading :eyebrow="$section->eyebrow" :title="$section->title ?: 'Everything tech, under one roof'" />
    <div class="grid gap-3 sm:gap-4 md:h-[440px] md:grid-cols-4 md:grid-rows-2">
        <a href="{{ $big['category']->url }}" class="mesh-dark grain group relative isolate flex min-h-80 flex-col justify-between overflow-hidden rounded-[28px] p-7 text-white md:col-span-2 md:row-span-2 lg:p-9">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <div class="relative max-w-xs">
                <p class="eyebrow text-accent-300">{{ $big['category']->name }}</p>
                <h3 class="mt-3 font-display text-[28px] leading-[1.1] font-semibold tracking-[-0.03em] lg:text-[34px]">{{ $big['heading'] }}</h3>
                <p class="mt-3 text-sm text-white/60">{{ $big['count'] }} {{ Str::plural('model', $big['count']) }}@if ($big['from']) · from <span class="font-mono text-white">{{ $from($big['from']) }}</span>@endif</p>
            </div>
            <span class="btn btn-glass relative w-fit">Shop {{ Str::lower($big['category']->name) }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" /></span>

            @if ($big['category']->slug === 'laptop')
            {{-- CSS-drawn laptop: gradient "wallpaper" screen with floating windows, silver base --}}
            <div class="pointer-events-none absolute right-6 bottom-12 hidden w-[54%] max-w-[360px] sm:block lg:right-10">
                <div class="absolute inset-[5%] rounded-full bg-brand-500/35 blur-3xl"></div>
                <div class="relative transition duration-700 group-hover:-translate-y-2">
                    <div class="relative aspect-[16/10] rounded-t-2xl bg-[#0a141e] p-[3%] shadow-[0_40px_70px_-25px_rgb(0_0_0/0.9)] ring-1 ring-white/15">
                        <span class="absolute top-[1.2%] left-1/2 size-1 -translate-x-1/2 rounded-full bg-white/25"></span>
                        <div class="relative h-full overflow-hidden rounded-md bg-[radial-gradient(120%_120%_at_15%_0%,#35c3ff_0%,#1d71b8_35%,#0b2a4a_70%,#051320_100%)]">
                            <div class="absolute -right-8 -bottom-12 size-40 rounded-full bg-accent-500/55 blur-2xl"></div>
                            <div class="absolute inset-0 bg-[linear-gradient(115deg,rgb(255_255_255/0.2),transparent_42%)]"></div>
                            <div class="absolute top-[16%] left-[9%] h-[48%] w-[46%] rounded-md bg-white/15 ring-1 ring-white/25 backdrop-blur-sm">
                                <div class="flex gap-1 p-1.5"><span class="size-1.5 rounded-full bg-white/60"></span><span class="size-1.5 rounded-full bg-white/40"></span><span class="size-1.5 rounded-full bg-white/30"></span></div>
                                <div class="mx-1.5 mt-1 h-1.5 w-2/3 rounded-full bg-white/40"></div>
                                <div class="mx-1.5 mt-1 h-1.5 w-1/2 rounded-full bg-white/25"></div>
                            </div>
                            <div class="absolute right-[10%] bottom-[16%] h-[36%] w-[34%] rounded-md bg-navy-950/40 ring-1 ring-white/20 backdrop-blur-sm"></div>
                        </div>
                    </div>
                    <div class="relative -mx-[7%] h-3.5 rounded-b-2xl bg-[linear-gradient(180deg,#e2e8f0,#8391a3)] shadow-[0_12px_24px_-10px_rgb(0_0_0/0.8)]">
                        <span class="absolute top-0 left-1/2 h-1 w-[18%] -translate-x-1/2 rounded-b-md bg-[#6b7888]"></span>
                    </div>
                </div>
                @foreach ([['Core i5 · 13th Gen', '-top-5 left-[4%]'], ['RTX 4050', 'top-[30%] -right-6'], ['16GB DDR5', '-bottom-9 left-[18%]']] as [$label, $pos])
                    <span class="glass absolute {{ $pos }} rounded-full px-3 py-1.5 font-mono text-[11px] text-white/90 motion-safe:animate-float-slow" style="animation-delay: -{{ $loop->index * 2 }}s">{{ $label }}</span>
                @endforeach
            </div>
            @else
                <x-icon :name="$big['category']->icon" class="pointer-events-none absolute -right-10 -bottom-10 size-80 text-white/[0.07] [stroke-width:0.7] transition duration-700 group-hover:-rotate-6" />
            @endif
        </a>

        <a href="{{ $wide['category']->url }}" class="group relative isolate flex flex-col justify-between gap-5 overflow-hidden rounded-[28px] border border-line bg-card p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-lift sm:flex-row sm:items-center md:col-span-2 lg:p-7">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(90%_90%_at_100%_0%,rgb(0_159_227/0.10),transparent_60%)]"></div>
            <div class="max-w-[260px]">
                <p class="eyebrow">{{ $wide['category']->name }}</p>
                <h3 class="mt-2.5 font-display text-2xl leading-tight font-semibold tracking-tight text-ink">{{ $wide['heading'] }}</h3>
                <p class="mt-2 text-sm text-muted">{{ $wide['text'] ? $wide['text'].' · ' : '' }}@if ($wide['from'])from <span class="font-mono text-ink">{{ $from($wide['from']) }}</span>@endif</p>
            </div>
            <div class="grid shrink-0 grid-cols-3 gap-2">
                @foreach ($wideIcons as $icon)
                    <span class="grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line transition duration-300 group-hover:-translate-y-0.5" style="transition-delay: {{ $loop->index * 40 }}ms">
                        <x-icon :name="$icon" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.4]" />
                    </span>
                @endforeach
            </div>
        </a>

        @foreach (['small1', 'small2'] as $slot)
            @php($dept = $cards[$slot])
            <a href="{{ $dept['category']->url }}" class="group relative isolate flex min-h-48 flex-col justify-between overflow-hidden rounded-[28px] border border-line bg-card p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-lift">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(90%_80%_at_100%_0%,rgb(243_146_0/0.09),transparent_60%)]"></div>
                <x-icon :name="$dept['category']->icon" class="absolute -right-8 -bottom-8 -z-10 size-40 text-line [stroke-width:0.8] transition duration-700 group-hover:-rotate-6" />
                <div class="flex items-start justify-between">
                    <span class="grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line transition duration-300 group-hover:scale-105 group-hover:ring-brand-500/40">
                        <x-icon :name="$dept['category']->icon" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.4]" />
                    </span>
                    <span class="grid size-9 place-items-center rounded-full bg-card-2 text-ink ring-1 ring-line transition duration-300 group-hover:bg-ink group-hover:text-canvas"><x-icon name="arrow-up-right" class="size-4" /></span>
                </div>
                <div class="mt-6">
                    @if ($dept['text'])
                        <p class="text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">{{ $dept['text'] }}</p>
                    @endif
                    <h3 class="mt-1.5 font-display text-xl font-semibold tracking-tight text-ink">{{ $dept['heading'] }}</h3>
                    <p class="mt-1 text-sm text-muted">@if ($dept['from'])From <span class="font-mono text-ink">{{ $from($dept['from']) }}</span> · @endif{{ $dept['count'] }} products</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
