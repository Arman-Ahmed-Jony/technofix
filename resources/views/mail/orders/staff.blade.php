<x-mail::message>
# New order {{ $order->number }}

**{{ $order->name }}** · {{ $order->phone }}{{ $order->email ? ' · '.$order->email : '' }}<br>
{{ $order->delivery_label }}{{ $order->is_pickup ? '' : ' — '.$order->fullAddress() }}

@include('mail.orders._items')

@if ($order->customer_note)
<x-mail::panel>
**Customer note:** {{ $order->customer_note }}
</x-mail::panel>
@endif

<x-mail::button :url="$adminUrl">
Open the order
</x-mail::button>

Placed {{ $order->created_at->format('d M Y, h:i A') }}{{ ($order->attribution['utm_source'] ?? null) ? ' · via '.$order->attribution['utm_source'] : '' }}.
</x-mail::message>
