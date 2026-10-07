{{-- Stock overview (App\Filament\Pages\StockOverview, App\Support\StockValuation). --}}
@php
    $money = fn (int $v) => 'BDT '.number_format($v);
    $edit = fn (int $id) => \App\Filament\Resources\Products\ProductResource::getUrl('edit', ['record' => $id]);
@endphp
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Stock value at cost', $money($summary['cost_value']), $summary['missing_cost'] ? $summary['missing_cost'].' in-stock '.Str::plural('product', $summary['missing_cost']).' have no cost price yet' : 'Every product in stock has a cost price'],
                ['Stock value at selling price', $money($summary['retail_value']), $summary['cost_value'] ? 'About '.$money(max(0, $summary['retail_value'] - $summary['cost_value'])).' above cost' : 'At today’s normal prices'],
                ['In stock', number_format($summary['units']).' units', number_format($summary['products']).' '.Str::plural('product', $summary['products'])],
                ['Needs attention', number_format($summary['low']).' low', $summary['negative'] ? $summary['negative'].' with negative stock (sold before arriving)' : 'Nothing below zero'],
            ] as [$label, $value, $note])
                <div class="tf-card p-5">
                    <p class="text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="tf-num mt-2 text-[26px] leading-none font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
                    <p class="mt-2 text-[12.5px] text-gray-500 dark:text-gray-400">{{ $note }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2">
                @include('filament.reports.table', ['title' => 'By category', 'subtitle' => 'Quantities on hand; value at cost only counts products with a cost price.', 'rows' => $categories,
                    'columns' => ['name' => ['Category', 'text'], 'products' => ['Products', 'num'], 'units' => ['Units', 'num'], 'cost_value' => ['At cost', 'money'], 'retail_value' => ['At price', 'money']]])
            </div>

            <div class="tf-card">
                <div class="tf-card-head"><div><p class="tf-card-title">Running low</p><p class="mt-0.5 text-[12.5px] text-gray-500">{{ \App\Filament\Widgets\StockWatch::low() }} or fewer left (Settings → Stock & orders)</p></div></div>
                @forelse ($low as $product)
                    <a href="{{ $edit($product->id) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px] hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="min-w-0"><span class="block truncate font-medium text-gray-800 dark:text-gray-100">{{ $product->name }}</span><span class="text-[11.5px] text-gray-400">{{ $product->category_name }}</span></span>
                        <span @class(['tf-num shrink-0 rounded-lg px-2 py-0.5 text-[12px] font-semibold', 'bg-rose-50 text-rose-600 dark:bg-rose-500/10' => $product->stock_qty <= 0, 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => $product->stock_qty > 0])>{{ (int) $product->stock_qty }} left</span>
                    </a>
                @empty
                    <p class="px-5 pt-1 pb-6 text-center text-[13px] text-gray-500">Nothing running low.</p>
                @endforelse
                <div class="h-3"></div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="tf-card">
                <div class="tf-card-head"><div><p class="tf-card-title">No cost price</p><p class="mt-0.5 text-[12.5px] text-gray-500">Add one (or receive them through Stock-in) so profit and stock value are right.</p></div></div>
                @forelse ($missingCost as $row)
                    <a href="{{ $edit($row['id']) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px] hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="truncate font-medium text-gray-800 dark:text-gray-100">{{ $row['name'] }}</span>
                        <span class="tf-num shrink-0 text-gray-500">{{ $row['qty'] }} in stock</span>
                    </a>
                @empty
                    <p class="px-5 pt-1 pb-6 text-center text-[13px] text-gray-500">All set.</p>
                @endforelse
                @if ($summary['missing_cost'] > 15)<p class="px-5 py-3 text-[12px] text-gray-500">+{{ $summary['missing_cost'] - 15 }} more — the Excel file lists them all.</p>@endif
                <div class="h-3"></div>
            </div>
            <div class="tf-card">
                <div class="tf-card-head"><div><p class="tf-card-title">Below zero</p><p class="mt-0.5 text-[12.5px] text-gray-500">Sold before the stock was entered — receive the stock or count it to fix.</p></div></div>
                @forelse ($negative as $row)
                    <a href="{{ $edit($row['id']) }}" class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px] hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="truncate font-medium text-gray-800 dark:text-gray-100">{{ $row['name'] }}</span>
                        <span class="tf-num shrink-0 font-semibold text-rose-600">{{ $row['qty'] }}</span>
                    </a>
                @empty
                    <p class="px-5 pt-1 pb-6 text-center text-[13px] text-gray-500">Nothing below zero.</p>
                @endforelse
                <div class="h-3"></div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
