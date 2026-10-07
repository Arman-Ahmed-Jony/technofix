<x-filament-widgets::widget>
    <div class="tf-card">
        <div class="tf-card-head">
            <div>
                <p class="tf-card-title">Running low</p>
                <p class="mt-0.5 text-[12.5px] text-gray-500 dark:text-gray-400">In stock, {{ \App\Filament\Widgets\StockWatch::low() }} or fewer left · {{ $outOfStock }} out of stock</p>
            </div>
            <a href="{{ $allUrl }}" class="tf-card-link">Products</a>
        </div>

        @if ($products->isEmpty())
            <div class="px-5 pt-6 pb-8 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-600/10 dark:bg-emerald-400/10"><x-filament::icon icon="heroicon-o-check-badge" class="size-6 text-emerald-600 dark:text-emerald-400" /></span>
                <p class="mt-3 text-[13px] text-gray-500">Stock levels look healthy.</p>
            </div>
        @else
            <ul class="space-y-1 px-3 pb-4">
                @foreach ($products as $product)
                    <li>
                        <a href="{{ $product['url'] }}" class="group flex items-center gap-3 rounded-xl px-2 py-2.5 transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <span @class([
                                'tf-num grid h-8 min-w-11 shrink-0 place-items-center rounded-lg px-2 text-[13px] font-semibold ring-1',
                                'bg-rose-50 text-rose-700 ring-rose-600/15 dark:bg-rose-400/10 dark:text-rose-300' => $product['qty'] <= 0,
                                'bg-amber-50 text-amber-700 ring-amber-600/15 dark:bg-amber-400/10 dark:text-amber-300' => $product['qty'] > 0,
                            ])>{{ $product['qty'] }}</span>
                            <span class="min-w-0 flex-1 truncate text-[13px] font-medium text-gray-800 group-hover:text-primary-600 dark:text-gray-100">{{ $product['name'] }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="size-4 shrink-0 text-gray-300 group-hover:text-gray-500" />
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($count > $products->count())
                <p class="-mt-2 px-5 pb-4 text-[12px] text-gray-500">+ {{ $count - $products->count() }} more</p>
            @endif
        @endif
    </div>
</x-filament-widgets::widget>
