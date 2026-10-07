<x-mail::table>
| Product | Qty | Total |
|:--------|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->label() }} | {{ $item->qty }} | BDT&nbsp;{{ number_format($item->total) }} |
@endforeach
| Delivery | | {!! $order->delivery_fee ? 'BDT&nbsp;'.number_format($order->delivery_fee) : 'Free' !!} |
@if ($order->discount)
| Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }} | | − BDT&nbsp;{{ number_format($order->discount) }} |
@endif
| **Total** | | **BDT&nbsp;{{ number_format($order->total) }}** |
</x-mail::table>
