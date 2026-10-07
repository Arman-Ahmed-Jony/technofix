<x-filament-widgets::widget>
    <div class="tf-card">
        <div class="tf-card-head">
            <div>
                <p class="tf-card-title">Latest enquiries</p>
                <p class="mt-0.5 text-[12.5px] text-gray-500 dark:text-gray-400">Contact forms, repairs, PC builds</p>
            </div>
            <a href="{{ $allUrl }}" class="tf-card-link">View all</a>
        </div>

        @if ($leads->isEmpty())
            <div class="px-5 pt-6 pb-8 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-gray-50 ring-1 ring-gray-900/5 dark:bg-white/5 dark:ring-white/10"><x-filament::icon icon="heroicon-o-inbox" class="size-6 text-gray-400" /></span>
                <p class="mt-3 text-[13px] text-gray-500">No enquiries yet.</p>
            </div>
        @else
            <ul class="space-y-1 px-3 pb-4">
                @foreach ($leads as $lead)
                    <li>
                        <a href="{{ $lead['url'] }}" class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600 ring-1 ring-primary-600/10 dark:bg-primary-400/10 dark:text-primary-300">
                                <x-filament::icon :icon="$lead['icon']" class="size-[18px]" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13.5px] font-medium text-gray-800 group-hover:text-primary-600 dark:text-gray-100">{{ $lead['name'] }}</span>
                                <span class="block truncate text-[12px] text-gray-500 dark:text-gray-400">{{ $lead['type'] }} · {{ $lead['when'] }}</span>
                            </span>
                            <x-filament::badge :color="$lead['status']->getColor()" size="sm">{{ $lead['status']->getLabel() }}</x-filament::badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-widgets::widget>
