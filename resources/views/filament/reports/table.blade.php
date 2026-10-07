{{-- One report table (filament/pages/sales-reports). $columns: [key => [label, type]] with type text|num|money|share. --}}
@php
    $money = fn ($v) => $v === null ? '—' : 'BDT '.number_format($v);
@endphp
<div class="tf-card">
    <div class="tf-card-head">
        <div>
            <p class="tf-card-title">{{ $title }}</p>
            @isset($subtitle)<p class="mt-0.5 text-[12.5px] text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>@endisset
        </div>
    </div>
    @if ($rows->isEmpty())
        <p class="px-5 pt-2 pb-8 text-center text-[13px] text-gray-500">Nothing in this period.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="border-y border-gray-100 bg-gray-50/70 text-[11px] font-semibold tracking-wide text-gray-500 uppercase dark:border-white/5 dark:bg-white/[0.03] dark:text-gray-400">
                        @foreach ($columns as [$label, $type])
                            <th @class(['px-5 py-2.5', 'text-left' => $type === 'text', 'text-right' => $type !== 'text'])>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($rows->take($limit ?? 100) as $row)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-white/[0.02]">
                            @foreach ($columns as $key => [$label, $type])
                                @if ($type === 'share')
                                    <td class="w-40 px-5 py-2.5">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-100 dark:bg-white/5"><span class="block h-full rounded-full bg-gradient-to-r from-primary-500 to-sky-400" style="width: {{ max(2, $row[$key]) }}%"></span></span>
                                            <span class="tf-num w-12 text-right text-gray-500">{{ $row[$key] }}%</span>
                                        </div>
                                    </td>
                                @elseif ($type === 'text')
                                    <td class="max-w-sm truncate px-5 py-2.5 font-medium text-gray-800 dark:text-gray-100" title="{{ $row[$key] }}">{{ $row[$key] }}</td>
                                @else
                                    <td @class(['tf-num px-5 py-2.5 text-right whitespace-nowrap', 'text-gray-950 dark:text-white' => $type === 'money', 'text-gray-600 dark:text-gray-300' => $type === 'num',
                                        'text-emerald-600 dark:text-emerald-400' => $key === 'profit' && ($row[$key] ?? 0) > 0, 'text-rose-600' => $key === 'profit' && ($row[$key] ?? 0) < 0])>
                                        {{ $type === 'money' ? $money($row[$key]) : number_format($row[$key]) }}
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($rows->count() > ($limit ?? 100))
            <p class="px-5 py-3 text-[12px] text-gray-500">Showing the top {{ $limit ?? 100 }} of {{ number_format($rows->count()) }} — the Excel file has them all.</p>
        @endif
    @endif
</div>
