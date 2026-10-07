@props(['products', 'onDark' => false])
{{-- Horizontal, swipeable product row with prev/next buttons on desktop. --}}
<div x-data="scroller" {{ $attributes->class('relative') }}>
    {{-- The scroll box clips everything outside it: the padding gives the hover lift and its shadow room (top, sides,
         and ~50px below), and the negative margins cancel that padding so the layout doesn't move. On large screens the
         outer few pixels fade out, hiding the sliver of the next card that would otherwise peek into the side room. --}}
    <div x-ref="track" @scroll.debounce.50ms="update" class="scrollbar-none -mx-4 -mt-1 -mb-9 flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-px-4 px-4 pt-2 pb-12 sm:gap-4 lg:-mx-6 lg:scroll-px-6 lg:px-6 lg:[mask-image:linear-gradient(to_right,transparent_9px,#000_24px,#000_calc(100%-24px),transparent_calc(100%-9px))]">
        @foreach ($products as $product)
            <div class="w-[46%] shrink-0 snap-start sm:w-[calc((100%-2rem)/3)] md:w-[calc((100%-3rem)/4)] xl:w-[calc((100%-4rem)/5)]">
                <x-product.card :product="$product" />
            </div>
        @endforeach
    </div>
    @foreach ([-1 => 'chevron-left', 1 => 'chevron-right'] as $dir => $icon)
        <button type="button" x-show="{{ $dir < 0 ? 'canPrev' : 'canNext' }}" x-transition.opacity @click="scroll({{ $dir }})" aria-label="{{ $dir < 0 ? 'Previous' : 'Next' }}"
            @class([
                'absolute top-[36%] z-10 hidden size-11 place-items-center rounded-full shadow-pop transition hover:scale-105 lg:grid',
                '-left-5' => $dir < 0, '-right-5' => $dir > 0,
                'bg-white text-navy-900' => $onDark,
                'bg-elevated text-ink ring-1 ring-line' => ! $onDark,
            ])>
            <x-icon :name="$icon" class="size-5" />
        </button>
    @endforeach
</div>
