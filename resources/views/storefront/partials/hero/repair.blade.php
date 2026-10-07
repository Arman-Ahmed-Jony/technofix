{{-- Hero visual: a repair ticket with progress steps. --}}
@php
    $steps = [
        ['Received', 'Device checked in', '10:30 AM', 'done'],
        ['Diagnosed', 'Display cable faulty', '12:10 PM', 'done'],
        ['Repairing', 'Replacing genuine part', 'In progress', 'active'],
        ['Ready', 'Pickup or home delivery', '—', 'todo'],
    ];
@endphp
<div class="relative w-[330px] xl:w-[345px]">
    <div class="glass rounded-3xl p-5 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="font-mono text-[11px] font-medium tracking-wider text-white/55 uppercase">Repair ticket</span>
                <p class="mt-1 font-display text-lg font-semibold tracking-tight">#TF-2048</p>
            </div>
            <span class="grid size-11 place-items-center rounded-2xl bg-white/10"><x-icon name="device-laptop" class="size-6 text-brand-300" /></span>
        </div>
        <p class="mt-1 text-[12.5px] text-white/60">HP Pavilion 15 · No display</p>
        <ol class="mt-4 space-y-0">
            @foreach ($steps as $i => [$title, $note, $time, $state])
                <li class="relative flex gap-3 pb-3.5 last:pb-0">
                    @unless ($loop->last)
                        <span @class(['absolute top-6 bottom-0 left-[11px] w-px', 'bg-emerald-400/60' => $state === 'done', 'bg-white/15' => $state !== 'done'])></span>
                    @endunless
                    <span @class([
                        'relative mt-0.5 grid size-[23px] shrink-0 place-items-center rounded-full',
                        'bg-emerald-400 text-navy-950' => $state === 'done',
                        'bg-accent-500 text-white' => $state === 'active',
                        'bg-white/10 text-white/40 ring-1 ring-white/15' => $state === 'todo',
                    ])>
                        @if ($state === 'done')
                            <x-icon name="check" class="size-3.5 [stroke-width:3]" />
                        @elseif ($state === 'active')
                            <span class="absolute inset-0 rounded-full bg-accent-500 motion-safe:animate-ping-slow"></span><x-icon name="tool" class="relative size-3.5" />
                        @else
                            <span class="size-1.5 rounded-full bg-current"></span>
                        @endif
                    </span>
                    <span class="flex min-w-0 flex-1 items-start justify-between gap-2">
                        <span>
                            <span @class(['block text-[13px] font-semibold', 'text-white/45' => $state === 'todo'])>{{ $title }}</span>
                            <span class="block text-[11.5px] text-white/50">{{ $note }}</span>
                        </span>
                        <span @class(['font-mono text-[10.5px]', 'text-accent-300' => $state === 'active', 'text-white/40' => $state !== 'active'])>{{ $time }}</span>
                    </span>
                </li>
            @endforeach
        </ol>
    </div>
    <div class="glass absolute -bottom-6 -left-8 flex items-center gap-2 rounded-2xl px-3 py-2 text-[12px] font-medium text-white motion-safe:animate-float-slow">
        <span class="grid size-7 place-items-center rounded-lg bg-emerald-400/20"><x-icon name="receipt" class="size-4 text-emerald-300" /></span>
        Quote before any work
    </div>
    <div class="glass absolute -right-4 -bottom-10 flex items-center gap-2 rounded-2xl px-3 py-2 text-[12px] font-medium text-white motion-safe:animate-float-slow [animation-delay:-3s]">
        <x-icon name="shield-check" class="size-4 text-brand-300" /> Genuine parts
    </div>
</div>
