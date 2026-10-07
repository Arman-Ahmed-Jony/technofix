{{-- Hero visual: Happy Hour countdown with two live deals. --}}
<div class="relative w-[320px] xl:w-[340px]" x-data="countdown(@js(($dealEndsAt ?? now()->endOfDay())->toIso8601String()))">
    <div class="glass rounded-3xl p-5 text-white">
        <div class="flex items-center justify-between">
            <span class="font-mono text-[11px] font-medium tracking-wider text-white/55 uppercase">Ends tonight</span>
            <span class="flex items-center gap-1.5 text-[11px] font-semibold text-accent-300"><span class="relative flex size-2"><span class="absolute inset-0 rounded-full bg-accent-400 motion-safe:animate-ping-slow"></span><span class="relative size-2 rounded-full bg-accent-400"></span></span> Live</span>
        </div>
        <div class="mt-4 grid grid-cols-3 gap-2">
            @foreach (['h' => 'Hours', 'm' => 'Minutes', 's' => 'Seconds'] as $unit => $label)
                <div class="rounded-2xl bg-white/[0.06] py-3 text-center ring-1 ring-white/10">
                    <span class="block font-mono text-[30px] leading-none font-medium tabular-nums" x-text="parts.{{ $unit }}">00</span>
                    <span class="mt-1.5 block text-[10px] tracking-wider text-white/45 uppercase">{{ $label }}</span>
                </div>
            @endforeach
        </div>
        <ul class="mt-4 space-y-2">
            @foreach ([['device-floppy', 'Samsung 990 EVO Plus 1TB', 12900, 13800], ['gpu', 'ASUS Dual RTX 4060 OC 8GB', 42500, 44000]] as [$icon, $name, $price, $was])
                <li class="flex items-center gap-3 rounded-xl bg-white/[0.04] px-2.5 py-2 ring-1 ring-white/[0.06]">
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-white/10"><x-icon :name="$icon" class="size-[18px] text-accent-300" /></span>
                    <span class="min-w-0 flex-1 truncate text-[13px] font-medium">{{ $name }}</span>
                    <span class="text-right">
                        <span class="block font-mono text-[10.5px] text-white/40 line-through">৳{{ number_format($was) }}</span>
                        <span class="block font-mono text-[12.5px] text-white">৳{{ number_format($price) }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
    <div class="btn-primary absolute -top-7 -right-7 grid size-24 place-items-center rounded-full text-center motion-safe:animate-float-slow">
        <span class="leading-none"><span class="block text-[10px] font-semibold tracking-wider uppercase opacity-85">Up to</span><span class="block font-display text-[26px] font-bold">15%</span><span class="block text-[10px] font-semibold tracking-wider uppercase opacity-85">off</span></span>
    </div>
</div>
