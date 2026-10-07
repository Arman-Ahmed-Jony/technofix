@props(['name', 'tagline' => null, 'url', 'theme', 'logo' => null, 'compact' => false])
{{-- "Exclusive Zone" brand card (home hero + All brands page). Colour themes and the white "light card" come from
     App\Support\ZoneThemes (Admin → Catalog → Brands); with a logo uploaded, it sits in a white box and the name gets smaller. --}}
@php
    $light = $theme['light'] ?? false;
    $nameSize = match (true) {
        (bool) $logo => $compact ? 'text-[19px]' : 'text-[20px] lg:text-[22px]',
        $compact => 'text-[24px]',
        default => 'text-[26px] lg:text-[30px]',
    };
@endphp
<a href="{{ $url }}" {{ $attributes->class([
    'group relative isolate flex items-center gap-4 overflow-hidden rounded-3xl px-5 py-4 transition duration-300 hover:-translate-y-0.5',
    'text-white shadow-lg ring-1 shadow-navy-950/10 ring-white/10 hover:shadow-xl' => ! $light,
    'text-ink shadow-card ring-1 ring-line hover:shadow-lift' => $light,
    $theme['bg'],
]) }}>
    <div class="absolute -top-12 -right-8 -z-10 size-44 rounded-full blur-2xl transition duration-700 group-hover:scale-125 {{ $theme['glow'] }}"></div>
    <div @class([
        'absolute inset-0 -z-10 [mask-image:linear-gradient(to_left,black,transparent_75%)] [background-size:14px_14px]',
        'bg-[radial-gradient(rgb(255_255_255/0.10)_1px,transparent_1px)]' => ! $light,
        'bg-[radial-gradient(rgb(10_22_38/0.07)_1px,transparent_1px)] dark:bg-[radial-gradient(rgb(255_255_255/0.06)_1px,transparent_1px)]' => $light,
    ])></div>
    <div @class([
        'absolute inset-y-0 -left-1/2 -z-10 w-1/3 -skew-x-12 bg-gradient-to-r from-transparent to-transparent transition-transform duration-[900ms] ease-out group-hover:translate-x-[450%]',
        'via-white/20' => ! $light,
        'via-brand-500/10' => $light,
    ])></div>

    @if ($logo)
        <span @class([
            'grid shrink-0 place-items-center rounded-2xl bg-white p-2 shadow-md transition duration-500 group-hover:scale-105',
            'size-14' => $compact,
            'size-12 sm:size-14 lg:size-16' => ! $compact,
            'ring-1 ring-black/5' => ! $light,
            'ring-1 ring-line' => $light,
        ])>
            <img src="{{ $logo }}" alt="{{ $name }}" loading="lazy" decoding="async" class="max-h-full max-w-full object-contain">
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <span @class([
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold tracking-[0.08em] uppercase ring-1',
            'bg-white/15 ring-white/20' => ! $light,
            'bg-brand-500/10 text-highlight ring-brand-500/20' => $light,
        ])>
            <x-icon name="sparkles" class="size-3" /> Exclusive Zone
        </span>
        <p class="mt-2 truncate font-display leading-none font-bold tracking-tight uppercase {{ $nameSize }}">{{ $name }}</p>
        @if ($tagline)
            <p @class(['mt-1.5 truncate text-[12.5px] lg:text-[13px]', 'text-white/75' => ! $light, 'text-muted' => $light])>{{ $tagline }}</p>
        @endif
    </div>

    @if ($logo || $compact)
        {{-- With a logo the card stays calm: just the arrow. --}}
        <span @class([
            'grid size-8 shrink-0 place-items-center rounded-full shadow-md transition duration-300 group-hover:translate-x-1',
            'bg-white '.$theme['accent'] => ! $light,
            'bg-ink text-canvas' => $light,
        ])>
            <x-icon name="arrow-right" class="size-4 [stroke-width:2.25]" />
        </span>
    @else
        <div class="relative mr-1 grid shrink-0 place-items-center sm:max-lg:hidden">
            <span @class([
                'grid size-14 place-items-center rounded-2xl ring-1 backdrop-blur-sm transition duration-500 group-hover:scale-105 group-hover:rotate-6',
                'bg-white/15 ring-white/25' => ! $light,
                'bg-card-2 text-highlight ring-line' => $light,
            ])>
                <x-icon :name="$theme['icons'][0]" class="size-7" />
            </span>
            <span @class([
                'absolute -top-3 -left-4 grid size-8 place-items-center rounded-xl ring-1 backdrop-blur-sm motion-safe:animate-float',
                'bg-white/20 ring-white/25' => ! $light,
                'bg-card text-highlight ring-line' => $light,
            ])>
                <x-icon :name="$theme['icons'][1]" class="size-4" />
            </span>
            <span @class([
                'absolute -right-2 -bottom-2 grid size-7 place-items-center rounded-full shadow-md transition duration-300 group-hover:translate-x-1',
                'bg-white '.$theme['accent'] => ! $light,
                'bg-ink text-canvas' => $light,
            ])>
                <x-icon name="arrow-right" class="size-4 [stroke-width:2.25]" />
            </span>
        </div>
    @endif
</a>
