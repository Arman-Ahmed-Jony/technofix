    {{-- ─── Brand marquee ────────────────────────────────────── --}}
    <section class="mt-14 lg:mt-20" aria-label="Brands we carry">
        <p class="text-center text-[11px] font-semibold tracking-[0.18em] text-faint uppercase">{{ $section->title ?: 'Official brands we carry' }}</p>
        <div class="fade-x group mt-6 flex overflow-hidden">
            <div class="flex shrink-0 animate-marquee items-center group-hover:[animation-play-state:paused]">
                @foreach ([...$brands, ...$brands] as $brand)
                    <a href="{{ $brand['url'] }}" @if ($loop->index >= count($brands)) aria-hidden="true" tabindex="-1" @endif class="px-7 font-display text-[22px] font-semibold tracking-tight whitespace-nowrap text-faint uppercase transition hover:text-ink sm:px-9 sm:text-[26px]">{{ $brand['name'] }}</a>
                @endforeach
            </div>
        </div>
    </section>

