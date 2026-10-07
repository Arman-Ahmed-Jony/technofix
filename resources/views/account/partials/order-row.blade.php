@php
    $stopped = in_array($order->status, [\App\Enums\OrderStatus::Cancelled, \App\Enums\OrderStatus::Returned], true);
    $label = $stopped ? $order->status->getLabel() : \App\Http\Controllers\Storefront\TrackOrderController::STEPS[$order->status->value][0];
@endphp
<a href="{{ route('account.orders.show', $order->number) }}" class="group flex items-center gap-3.5 px-5 py-4 transition hover:bg-card-2/70">
    <span class="hidden sm:flex">@include('account.partials.item-thumbs', ['items' => $order->items, 'size' => 'size-11'])</span>
    <span class="min-w-0 flex-1">
        <span class="block font-mono text-[14px] font-semibold whitespace-nowrap text-ink group-hover:text-highlight">{{ $order->number }}</span>
        <span class="block truncate text-[12.5px] text-muted">{{ $order->created_at->format('d M Y') }} · {{ $order->items_count }} {{ Str::plural('item', $order->items_count) }}</span>
    </span>
    {{-- Phones: total above the status; wider screens: status, then total. --}}
    <span class="flex flex-col items-end gap-1 sm:flex-row-reverse sm:items-center sm:gap-4">
        <span class="price font-display text-[15px] whitespace-nowrap sm:w-24 sm:text-right">৳{{ number_format($order->total) }}</span>
        <span @class([
            'chip py-1 text-[11.5px] font-semibold whitespace-nowrap ring-1',
            'bg-rose-500/10 text-rose-700 ring-rose-500/20 dark:text-rose-300' => $stopped,
            'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' => ! $stopped && $order->status === \App\Enums\OrderStatus::Delivered,
            'bg-brand-500/10 text-highlight ring-brand-500/25' => ! $stopped && $order->status !== \App\Enums\OrderStatus::Delivered,
        ])>{{ $label }}</span>
    </span>
    <x-icon name="chevron-right" class="size-4 shrink-0 text-faint transition group-hover:translate-x-0.5 group-hover:text-ink" />
</a>
