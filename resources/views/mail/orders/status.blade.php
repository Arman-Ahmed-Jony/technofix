@php($store = app(\App\Settings\StoreSettings::class))
<x-mail::message>
@switch($status->value)
@case('confirmed')
# Your order is confirmed

Hi {{ Str::before(trim($order->name), ' ') }}, your order **{{ $order->number }}** is confirmed. We’re getting it ready now.
@break
@case('shipped')
# Your order is on the way

Hi {{ Str::before(trim($order->name), ' ') }}, your order **{{ $order->number }}** has been handed to {{ $order->courier ?: 'our delivery partner' }}. They’ll call you before delivery.

@if ($order->tracking_code)
<x-mail::panel>
Courier tracking number: **{{ $order->tracking_code }}**
</x-mail::panel>
@endif

@if ($order->amountDue())
Please keep **BDT {{ number_format($order->amountDue()) }}** ready to pay on delivery.
@endif
@break
@case('delivered')
# Delivered — enjoy!

Hi {{ Str::before(trim($order->name), ' ') }}, your order **{{ $order->number }}** was delivered. Thank you for shopping with {{ config('app.name') }}.

Keep this email and your invoice for warranty claims. Need setup help or a repair? We’re here.
@break
@case('cancelled')
# Your order was cancelled

Hi {{ Str::before(trim($order->name), ' ') }}, your order **{{ $order->number }}** was cancelled. If this is a surprise, or you’d like to order again, just call us.
@break
@endswitch

<x-mail::button :url="route('orders.track', ['number' => $order->number])">
View order status
</x-mail::button>

Questions? Call {{ $store->hotline }} and mention your order number.
</x-mail::message>
