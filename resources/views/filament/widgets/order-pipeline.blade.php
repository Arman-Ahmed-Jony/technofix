<x-filament-widgets::widget>
    <div class="tf-card flex flex-col">
        <div class="tf-card-head">
            <div>
                <p class="tf-card-title">Order pipeline</p>
                <p class="mt-0.5 text-[12.5px] text-gray-500 dark:text-gray-400">Open orders by stage</p>
            </div>
            <a href="{{ $allUrl }}" class="tf-card-link">View all</a>
        </div>

        <div class="px-5">
            <p class="flex items-baseline gap-2"><span class="tf-num text-[34px] font-semibold text-gray-950 dark:text-white">{{ $open }}</span> <span class="text-[13px] text-gray-500 dark:text-gray-400">open {{ Str::plural('order', $open) }}</span></p>

            {{-- Stacked bar --}}
            <div class="mt-3 flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5">
                @foreach ($stages as $stage)
                    @if ($stage['count'])
                        <div class="h-full first:rounded-l-full last:rounded-r-full" style="width: {{ $stage['count'] / max(1, $open) * 100 }}%; background: {{ $stage['color'] }}"></div>
                    @endif
                @endforeach
            </div>
        </div>

        <ul class="mt-4 flex-1 space-y-1 px-3">
            @foreach ($stages as $stage)
                <li>
                    <a href="{{ $stage['url'] }}" class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="size-2.5 rounded-full" style="background: {{ $stage['color'] }}"></span>
                        <span class="flex-1 text-[13.5px] font-medium text-gray-700 dark:text-gray-200">{{ $stage['label'] }}</span>
                        <span class="tf-num text-[15px] font-semibold text-gray-950 dark:text-white">{{ $stage['count'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-3 grid grid-cols-2 border-t border-gray-100 dark:border-white/5">
            <div class="px-5 py-3.5">
                <p class="text-[11.5px] font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Delivered</p>
                <p class="tf-num mt-0.5 text-[18px] font-semibold text-emerald-600 dark:text-emerald-400">{{ $delivered }}</p>
                <p class="text-[11.5px] text-gray-400">this month</p>
            </div>
            <div class="border-l border-gray-100 px-5 py-3.5 dark:border-white/5">
                <p class="text-[11.5px] font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">Cancelled</p>
                <p class="tf-num mt-0.5 text-[18px] font-semibold text-rose-600 dark:text-rose-400">{{ $lost }}</p>
                <p class="text-[11.5px] text-gray-400">this month, incl. returns</p>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
