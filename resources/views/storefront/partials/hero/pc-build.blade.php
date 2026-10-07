{{-- Hero visual: a live "Your build" summary card, as the PC Builder will show it. --}}
@php
    $parts = [
        ['cpu', 'Core i5-14400F', 21500],
        ['cpu-2', 'B760M DS3H DDR4', 15500],
        ['components', '16GB DDR5 5200MHz', 6900],
        ['gpu', 'RTX 4060 8GB', 42500],
        ['device-floppy', '990 EVO Plus 1TB', 12900],
    ];
@endphp
<div class="relative w-[310px] xl:w-[330px]">
    <div class="glass rounded-3xl p-5 text-white">
        <div class="flex items-center justify-between">
            <span class="font-mono text-[11px] font-medium tracking-wider text-white/55 uppercase">Your build</span>
            <span class="chip bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/25"><x-icon name="circle-check" class="size-3.5" /> Compatible</span>
        </div>
        <ul class="mt-4 space-y-1.5">
            @foreach ($parts as [$icon, $name, $price])
                <li class="flex items-center gap-3 rounded-xl bg-white/[0.04] px-2.5 py-1.5 ring-1 ring-white/[0.06]">
                    <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-white/10"><x-icon :name="$icon" class="size-4 text-brand-300" /></span>
                    <span class="min-w-0 flex-1 truncate text-[13px] font-medium">{{ $name }}</span>
                    <span class="font-mono text-[12px] text-white/75">৳{{ number_format($price) }}</span>
                </li>
            @endforeach
        </ul>
        <p class="mt-3 flex items-center gap-2 text-[11.5px] text-white/60">
            <x-icon name="bolt" class="size-3.5 text-accent-300" /> Suggested PSU <span class="font-mono text-white/85">650W</span> · est. draw <span class="font-mono text-white/85">410W</span>
        </p>
        <div class="mt-3 flex items-end justify-between border-t border-white/10 pt-3.5">
            <span class="text-xs text-white/55">Estimated total</span>
            <span class="font-display text-[24px] leading-none font-semibold tracking-tight">৳{{ number_format(array_sum(array_column($parts, 2))) }}</span>
        </div>
    </div>
    <div class="glass absolute -right-6 -bottom-8 flex items-center gap-2 rounded-2xl px-3 py-2 text-[12px] font-medium whitespace-nowrap text-white motion-safe:animate-float-slow [animation-delay:-3s]">
        <x-icon name="tool" class="size-4 text-brand-300" /> Assembled &amp; tested
    </div>
</div>
