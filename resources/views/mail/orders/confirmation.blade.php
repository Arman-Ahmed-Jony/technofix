@php($store = app(\App\Settings\StoreSettings::class))
<x-mail::message>
# Thank you, {{ Str::before(trim($order->name), ' ') }}!

We’ve received your order **{{ $order->number }}**. We’ll call **{{ $order->phone }}** shortly to confirm it.

@include('mail.orders._items')

**Delivery:** {{ $order->delivery_label }}{{ $order->delivery_eta ? ' ('.$order->delivery_eta.')' : '' }}<br>
@unless ($order->is_pickup)
**Address:** {{ $order->fullAddress() }}<br>
@endunless
**Payment:** {{ $order->payment_method->getLabel() }} — pay BDT {{ number_format($order->amountDue()) }} {{ $order->is_pickup ? 'when you collect it' : 'when it arrives' }}.

<x-mail::button :url="route('orders.track', ['number' => $order->number])">
Track your order
</x-mail::button>

Questions? Call {{ $store->hotline }}{{ $store->whatsapp ? ' or WhatsApp us' : '' }} and mention your order number.
</x-mail::message>
