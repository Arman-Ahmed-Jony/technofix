{{-- Sales reports (App\Filament\Pages\SalesReports, App\Support\Reports). --}}
@php
    $s = $report->summary();
    $days = $report->daily();
    $bars = $report->chart();
    $maxBar = max(1, $bars->max('sales'));
    $money = fn (int $v) => 'BDT '.number_format($v);
    $tabs = ['overview' => 'Overview', 'products' => 'Products', 'categories' => 'Categories & brands', 'channels' => 'Came from & places', 'staff' => 'Staff & coupons'];
    $input = 'rounded-xl border-0 bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20';
@endphp
<x-filament-panels::page>
    <div x-data="{ tab: 'overview' }" class="space-y-6">
        {{-- Period --}}
        <div class="tf-card flex flex-wrap items-end gap-3 p-4" style="height: auto">
            <label class="grid gap-1 text-[12px] font-medium text-gray-500">Period
                <select wire:model.live="preset" class="{{ $input }} pe-9">
                    @foreach (\App\Support\Reports::PRESETS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1 text-[12px] font-medium text-gray-500">From
                <input type="date" wire:model.live.debounce.500ms="from" class="{{ $input }}">
            </label>
            <label class="grid gap-1 text-[12px] font-medium text-gray-500">Until
                <input type="date" wire:model.live.debounce.500ms="until" class="{{ $input }}">
            </label>
            <div class="grid gap-1 text-[12px] font-medium text-gray-500">Count
                <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-white/5">
                    @foreach (\App\Support\Reports::BASES as $key => $label)
                        <button type="button" wire:click="$set('basis', '{{ $key }}')" @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                            'bg-white text-gray-950 shadow-sm dark:bg-white/10 dark:text-white' => $basis === $key,
                            'text-gray-500 hover:text-gray-800 dark:text-gray-400' => $basis !== $key,
                        ])>{{ $label }}</button>
                    @endforeach
                </div>
            </div>
            <p class="ms-auto max-w-sm text-[12px] leading-snug text-gray-500" wire:loading.remove>
                {{ $basis === 'delivered' ? 'Delivered orders, by the day they were delivered — what was really sold.' : 'Every order placed except cancelled and returned ones, by the day it was placed.' }}
            </p>
            <p class="ms-auto text-[12px] text-primary-600" wire:loading>Updating…</p>
        </div>

        {{-- Key figures --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Sales', $money($s['sales']), number_format($s['orders']).' '.Str::plural('order', $s['orders']).' · '.number_format($s['items']).' items'],
                ['Average order', $money($s['average']), number_format($s['buyers']).' '.Str::plural('customer', $s['buyers'])],
                ['Collected', $money($s['collected']), $s['to_collect'] ? $money($s['to_collect']).' still to collect' : 'Nothing left to collect'],
                ['Estimated profit', $money($s['profit']), $s['profit_coverage'] < 100 ? 'From products with a cost price ('.$s['profit_coverage'].'% of sales)' : 'From the products’ cost prices'],
            ] as [$label, $value, $note])
                <div class="tf-card p-5">
                    <p class="text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="tf-num mt-2 text-[26px] leading-none font-semibold tracking-tight text-gray-950 dark:text-white">{{ $value }}</p>
                    <p class="mt-2 text-[12.5px] text-gray-500 dark:text-gray-400">{{ $note }}</p>
                </div>
            @endforeach
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-1 rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            @foreach ($tabs as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5'"
                    class="rounded-xl px-4 py-2 text-sm font-medium transition">{{ $label }}</button>
            @endforeach
        </div>

        <div x-show="tab === 'overview'" class="grid gap-6 xl:grid-cols-3">
            <div class="tf-card xl:col-span-2">
                <div class="tf-card-head"><div><p class="tf-card-title">Sales over time</p><p class="mt-0.5 text-[12.5px] text-gray-500">{{ $report->label() }}</p></div></div>
                @if ($s['orders'])
                    <div class="flex h-56 items-end gap-[3px] px-5 pb-2">
                        @foreach ($bars as $bar)
                            <div class="group relative flex h-full min-w-0 flex-1 items-end">
                                <div class="w-full rounded-t-md bg-gradient-to-t from-primary-600 to-sky-400 transition group-hover:from-amber-500 group-hover:to-amber-300" style="height: {{ $bar['sales'] ? max(2, $bar['sales'] / $maxBar * 100) : 0.6 }}%"></div>
                                <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 hidden -translate-x-1/2 rounded-lg bg-gray-950 px-2.5 py-1.5 text-[11.5px] whitespace-nowrap text-white shadow-lg group-hover:block">
                                    {{ $bar['label'] }} · {{ $money($bar['sales']) }} · {{ $bar['orders'] }} {{ Str::plural('order', $bar['orders']) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between px-5 pb-4 text-[11px] text-gray-400"><span>{{ $days->first()['day']->format('j M') }}</span><span>{{ $days->last()['day']->format('j M') }}</span></div>
                @else
                    <p class="px-5 pt-2 pb-10 text-center text-[13px] text-gray-500">No sales in this period.</p>
                @endif
            </div>
            <div class="tf-card">
                <div class="tf-card-head"><p class="tf-card-title">The money</p></div>
                <dl class="divide-y divide-gray-100 px-5 pb-3 text-[13.5px] dark:divide-white/5">
                    @foreach ([['Products', $s['subtotal']], ['Delivery charges', $s['delivery']], ['Discounts', -$s['discount']], ['Sales', $s['sales']], ['Collected', $s['collected']], ['Still to collect', $s['to_collect']]] as [$label, $value])
                        <div @class(['flex justify-between py-2.5', 'font-semibold text-gray-950 dark:text-white' => $label === 'Sales'])>
                            <dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="tf-num">{{ $value < 0 ? '− '.$money(-$value) : $money($value) }}</dd>
                        </div>
                    @endforeach
                    <div class="flex justify-between py-2.5"><dt class="text-gray-500">Cancelled</dt><dd class="tf-num text-rose-600">{{ $s['cancelled'] }} · {{ $money($s['cancelled_value']) }}</dd></div>
                    <div class="flex justify-between py-2.5"><dt class="text-gray-500">Returned</dt><dd class="tf-num text-rose-600">{{ $s['returned'] }} · {{ $money($s['returned_value']) }}</dd></div>
                </dl>
            </div>
        </div>

        <div x-show="tab === 'products'" x-cloak>
            @include('filament.reports.table', ['title' => 'Products sold', 'subtitle' => 'Profit = sales − today’s cost price × quantity (only products with a cost price).', 'rows' => $report->products(),
                'columns' => ['name' => ['Product', 'text'], 'category' => ['Category', 'text'], 'qty' => ['Qty', 'num'], 'revenue' => ['Sales', 'money'], 'cost' => ['Cost', 'money'], 'profit' => ['Profit', 'money']]])
        </div>

        <div x-show="tab === 'categories'" x-cloak class="grid gap-6 xl:grid-cols-2">
            @include('filament.reports.table', ['title' => 'By category', 'rows' => $report->byCategory(), 'columns' => ['name' => ['Category', 'text'], 'qty' => ['Qty', 'num'], 'revenue' => ['Sales', 'money'], 'share' => ['Share', 'share']]])
            @include('filament.reports.table', ['title' => 'By brand', 'rows' => $report->byBrand(), 'columns' => ['name' => ['Brand', 'text'], 'qty' => ['Qty', 'num'], 'revenue' => ['Sales', 'money'], 'share' => ['Share', 'share']]])
        </div>

        <div x-show="tab === 'channels'" x-cloak class="grid gap-6 xl:grid-cols-2">
            @include('filament.reports.table', ['title' => 'Where orders came from', 'subtitle' => 'Website orders by the ad or site that brought the visitor; staff orders by channel.', 'rows' => $report->byChannel(),
                'columns' => ['name' => ['Came from', 'text'], 'orders' => ['Orders', 'num'], 'revenue' => ['Sales', 'money'], 'share' => ['Share', 'share']]])
            @include('filament.reports.table', ['title' => 'By district', 'rows' => $report->byDistrict(), 'limit' => 30,
                'columns' => ['name' => ['District', 'text'], 'orders' => ['Orders', 'num'], 'revenue' => ['Sales', 'money'], 'share' => ['Share', 'share']]])
        </div>

        <div x-show="tab === 'staff'" x-cloak class="grid gap-6 xl:grid-cols-2">
            @include('filament.reports.table', ['title' => 'Staff', 'subtitle' => 'In this period: orders taken by phone / Facebook / WhatsApp / walk-in, confirmed and marked delivered.', 'rows' => $report->byStaff(),
                'columns' => ['name' => ['Staff member', 'text'], 'taken' => ['Taken', 'num'], 'taken_value' => ['Value', 'money'], 'confirmed' => ['Confirmed', 'num'], 'delivered' => ['Delivered', 'num']]])
            @include('filament.reports.table', ['title' => 'Coupons', 'rows' => $report->byCoupon(),
                'columns' => ['name' => ['Code', 'text'], 'orders' => ['Orders', 'num'], 'discount' => ['Discount', 'money'], 'revenue' => ['Sales', 'money']]])
        </div>
    </div>
</x-filament-panels::page>
