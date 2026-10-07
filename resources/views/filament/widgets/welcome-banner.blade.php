<x-filament-widgets::widget>
    <div class="relative isolate overflow-hidden rounded-3xl px-6 py-7 text-white shadow-[0_24px_60px_-28px_rgb(3_18_30/0.9)] sm:px-8 sm:py-8"
        style="background: radial-gradient(38rem 22rem at 100% 0%, rgb(0 159 227 / .35), transparent 65%), radial-gradient(30rem 18rem at 0% 120%, rgb(243 146 0 / .22), transparent 65%), linear-gradient(135deg, #06263b 0%, #041a2a 55%, #03121e 100%)">
        {{-- Decorative grid --}}
        <div class="pointer-events-none absolute inset-0 -z-10 opacity-[0.07]" style="background-image: linear-gradient(rgb(255 255 255) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255) 1px, transparent 1px); background-size: 32px 32px; mask-image: radial-gradient(ellipse at 70% 0%, black, transparent 70%);"></div>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <p class="text-[13px] font-medium text-white/60">{{ $date }}</p>
                <h1 class="mt-1.5 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] sm:text-[34px]">{{ $greeting }}, {{ $name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-[13.5px]">
                    @if ($can['orders'] && $newOrders)
                        <a href="{{ $urls['newOrders'] }}" class="inline-flex items-center gap-2 rounded-full bg-amber-400/15 px-3 py-1.5 font-medium text-amber-200 ring-1 ring-amber-300/25 transition hover:bg-amber-400/25">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-amber-300 opacity-60"></span><span class="relative inline-flex size-2 rounded-full bg-amber-300"></span></span>
                            {{ $newOrders }} new {{ Str::plural('order', $newOrders) }} to confirm
                        </a>
                    @elseif ($can['orders'])
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-400/15 px-3 py-1.5 font-medium text-emerald-200 ring-1 ring-emerald-300/25">
                            <x-filament::icon icon="heroicon-m-check-circle" class="size-4" /> All orders confirmed
                        </span>
                    @endif
                    @if ($can['leads'] && $newLeads)
                        <a href="{{ $urls['leads'] }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 font-medium text-white/85 ring-1 ring-white/15 transition hover:bg-white/15">
                            <x-filament::icon icon="heroicon-m-inbox-arrow-down" class="size-4" /> {{ $newLeads }} new {{ Str::plural('enquiry', $newLeads) }}
                        </a>
                    @endif
                    @if ($can['reports'])
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 font-medium text-white/85 ring-1 ring-white/15">
                            <x-filament::icon icon="heroicon-m-banknotes" class="size-4" /> Today: BDT {{ number_format($today['sales']) }} · {{ $today['orders'] }} {{ Str::plural('order', $today['orders']) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($can['orders'])
                <a href="{{ $urls['createOrder'] }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-[13.5px] font-semibold text-[#06263b] shadow-lg shadow-black/20 transition hover:bg-white/90">
                    <x-filament::icon icon="heroicon-m-plus" class="size-4" /> New order
                </a>
                @endif
                @if ($can['catalog'])
                <a href="{{ $urls['createProduct'] }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-[13.5px] font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/15">
                    <x-filament::icon icon="heroicon-m-cube" class="size-4" /> Add product
                </a>
                @endif
                <a href="{{ $urls['store'] }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-[13.5px] font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/15">
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="size-4" /> View store
                </a>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
