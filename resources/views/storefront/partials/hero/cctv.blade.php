{{-- Hero visual: a CCTV "live view" with four camera tiles. --}}
<div class="relative w-[340px] xl:w-[360px]">
    <div class="glass rounded-3xl p-4 text-white">
        <div class="flex items-center justify-between px-1">
            <span class="flex items-center gap-2 font-mono text-[11px] font-medium tracking-wider text-white/60 uppercase">
                <span class="relative flex size-2"><span class="absolute inset-0 rounded-full bg-rose-500 motion-safe:animate-ping-slow"></span><span class="relative size-2 rounded-full bg-rose-500"></span></span>
                Live view
            </span>
            <span class="text-[11px] text-white/50">4 cameras online</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2">
            @foreach (['Main gate', 'Showroom', 'Parking', 'Warehouse'] as $i => $label)
                <div class="relative aspect-video overflow-hidden rounded-xl bg-[linear-gradient(135deg,#0b2a3c,#06151f)] ring-1 ring-white/10">
                    <div class="absolute inset-0 bg-[linear-gradient(rgb(255_255_255/0.05)_1px,transparent_1px)] [background-size:100%_4px]"></div>
                    <div @class([
                        'absolute bottom-0 h-[55%] w-[70%] rounded-t-[40%] opacity-60',
                        'left-[15%] bg-[radial-gradient(ellipse_at_top,rgb(76_194_245/0.35),transparent_70%)]' => $i % 2 === 0,
                        'right-[10%] bg-[radial-gradient(ellipse_at_top,rgb(247_160_31/0.25),transparent_70%)]' => $i % 2 === 1,
                    ])></div>
                    <span class="absolute top-1.5 left-2 font-mono text-[9px] text-white/70">CAM 0{{ $i + 1 }}</span>
                    <span class="absolute top-1.5 right-2 flex items-center gap-1 font-mono text-[9px] text-rose-300"><span class="size-1.5 rounded-full bg-rose-500"></span>REC</span>
                    <span class="absolute bottom-1.5 left-2 text-[10px] font-medium text-white/85">{{ $label }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-3 flex items-center justify-between rounded-xl bg-white/[0.05] px-3 py-2 ring-1 ring-white/[0.07]">
            <span class="flex items-center gap-2 text-[12px]"><x-icon name="device-mobile" class="size-4 text-brand-300" /> Watch from your phone</span>
            <span class="font-mono text-[11px] text-white/50">24/7</span>
        </div>
    </div>
    <div class="glass absolute -bottom-6 -left-8 flex items-center gap-2 rounded-2xl px-3 py-2 text-[12px] font-medium text-white motion-safe:animate-float-slow">
        <span class="grid size-7 place-items-center rounded-lg bg-rose-500/20"><x-icon name="bell-ringing" class="size-4 text-rose-300" /></span>
        Motion · Main gate
    </div>
    <div class="glass absolute -right-4 -bottom-10 flex items-center gap-2 rounded-2xl px-3 py-2 text-[12px] font-medium text-white motion-safe:animate-float-slow [animation-delay:-3s]">
        <x-icon name="map-pin" class="size-4 text-accent-300" /> Free site survey
    </div>
</div>
