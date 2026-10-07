<x-filament-widgets::widget>
    <div class="tf-card">
        <div class="tf-card-head">
            <div>
                <p class="tf-card-title">Best sellers</p>
                <p class="mt-0.5 text-[12.5px] text-gray-500 dark:text-gray-400">Last 30 days, by units</p>
            </div>
        </div>

        @if ($rows->isEmpty())
            <div class="px-5 pt-6 pb-8 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-gray-50 ring-1 ring-gray-900/5 dark:bg-white/5 dark:ring-white/10"><x-filament::icon icon="heroicon-o-trophy" class="size-6 text-gray-400" /></span>
                <p class="mt-3 text-[13px] text-gray-500">No sales in the last 30 days yet.</p>
            </div>
        @else
            <ol class="space-y-1 px-3 pb-4">
                @foreach ($rows as $row)
                    <li>
                        <a @if ($row['url']) href="{{ $row['url'] }}" @endif class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <span @class([
                                'tf-num grid size-7 shrink-0 place-items-center rounded-lg text-[12px] font-semibold',
                                'bg-amber-100 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300' => $loop->first,
                                'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' => ! $loop->first,
                            ])>{{ $loop->iteration }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-medium text-gray-800 group-hover:text-primary-600 dark:text-gray-100">{{ $row['name'] }}</span>
                                <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5">
                                    <span class="block h-full rounded-full bg-gradient-to-r from-primary-500 to-sky-400" style="width: {{ max(6, $row['units'] / $max * 100) }}%"></span>
                                </span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="tf-num block text-[14px] font-semibold text-gray-950 dark:text-white">{{ $row['units'] }} <span class="text-[11px] font-normal text-gray-400">pcs</span></span>
                                <span class="block text-[11.5px] text-gray-500">BDT {{ number_format($row['revenue']) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</x-filament-widgets::widget>
