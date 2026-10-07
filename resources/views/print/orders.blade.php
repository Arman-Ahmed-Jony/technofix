@php
    $title = \App\Http\Controllers\Admin\PrintOrdersController::TYPES[$type];
    $isInvoice = $type === 'invoice';
    $money = fn (?int $amount) => number_format((int) $amount);
    $switchUrl = fn (string $to) => request()->fullUrlWithQuery(['type' => $to, 'autoprint' => null]);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }}{{ $orders->count() === 1 ? ' '.$orders->first()->number : ' ('.$orders->count().')' }} — TechnoFix</title>
    @include('print._styles')
</head>
<body>
    <div class="toolbar">
        <a href="{{ $switchUrl('invoice') }}" @class(['current' => $isInvoice])>Invoice</a>
        <a href="{{ $switchUrl('slip') }}" @class(['current' => ! $isInvoice])>Packing slip</a>
        <button type="button" class="primary" onclick="window.print()">Print / Save as PDF</button>
        <span>{{ $orders->count() }} {{ Str::plural('order', $orders->count()) }}</span>
    </div>

    @foreach ($orders as $order)
        <section class="sheet">
            <header class="head">
                <div>
                    <img src="{{ \App\Support\Brand::logo() }}" alt="TechnoFix">
                    <div class="store">
                        @if ($store->legal_name !== 'TechnoFix')<b>{{ $store->legal_name }}</b><br>@endif
                        {{ $store->address }}<br>
                        {{ collect($store->phones())->pluck('number')->join(' · ') }} · {{ $store->email }}
                        @if ($isInvoice && ($store->trade_licence || $store->bin))
                            <br>{{ collect([$store->trade_licence ? 'Trade licence '.$store->trade_licence : null, $store->bin ? 'BIN '.$store->bin : null])->filter()->join(' · ') }}
                        @endif
                    </div>
                </div>
                <div class="doc">
                    <h1>{{ $title }}</h1>
                    <div class="no">{{ $order->number }}</div>
                    <div class="date">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                </div>
            </header>

            <div class="parties">
                <div class="box">
                    <h2>{{ $order->is_pickup ? 'Customer (store pickup)' : 'Deliver to' }}</h2>
                    <div class="name">{{ $order->name }}</div>
                    <div class="big">{{ $order->phone }}</div>
                    @if ($order->email)
                        <div class="muted">{{ $order->email }}</div>
                    @endif
                    @unless ($order->is_pickup)
                        <div style="margin-top:6px">{{ $order->fullAddress() }}</div>
                    @endunless
                </div>
                <div class="box">
                    <h2>Order</h2>
                    <dl class="kv">
                        <dt>Delivery</dt><dd>{{ $order->delivery_label }}</dd>
                        <dt>Payment</dt><dd>{{ $order->payment_method->getLabel() }}</dd>
                        @if ($order->courier)
                            <dt>Courier</dt><dd>{{ $order->courier }}</dd>
                        @endif
                        @if ($order->tracking_code)
                            <dt>Tracking</dt><dd>{{ $order->tracking_code }}</dd>
                        @endif
                        <dt>Items</dt><dd>{{ $order->items->sum('qty') }} pcs</dd>
                    </dl>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        @if ($isInvoice)
                            <th style="width:32px">#</th><th>Product</th><th class="num">Qty</th><th class="num">Unit price (BDT)</th><th class="num">Total (BDT)</th>
                        @else
                            <th style="width:36px"></th><th>Product</th><th>Code</th><th class="num">Qty</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            @if ($isInvoice)
                                <td class="muted">{{ $loop->iteration }}</td>
                                <td>{{ $item->label() }}<div class="muted">{{ collect([$item->sku, \App\Support\Warranty::label($item->warranty)])->filter()->join(' · ') }}</div></td>
                                <td class="num">{{ $item->qty }}</td>
                                <td class="num">{{ $money($item->price) }}</td>
                                <td class="num">{{ $money($item->total) }}</td>
                            @else
                                <td><span class="check"></span></td>
                                <td>{{ $item->label() }}@if ($item->warranty)<div class="muted">{{ \App\Support\Warranty::label($item->warranty) }}</div>@endif</td>
                                <td class="muted">{{ $item->sku }}</td>
                                <td class="num" style="font-size:15px;font-weight:700">{{ $item->qty }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($isInvoice)
                <div class="totals">
                    <div><span>Subtotal</span><span>{{ $money($order->subtotal) }}</span></div>
                    <div><span>Delivery</span><span>{{ $order->delivery_fee ? $money($order->delivery_fee) : 'Free' }}</span></div>
                    @if ($order->discount)
                        <div><span>Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>− {{ $money($order->discount) }}</span></div>
                    @endif
                    @if ($order->paid_amount && $order->amountDue())
                        <div><span>Total</span><span>{{ $money($order->total) }}</span></div>
                        <div><span>Paid</span><span>− {{ $money($order->paid_amount) }}</span></div>
                    @endif
                    <div class="grand">
                        <span>{{ $order->amountDue() ? 'Amount to collect' : 'Total (paid)' }}</span>
                        <span>BDT {{ $money($order->amountDue() ?: $order->total) }}</span>
                    </div>
                </div>
            @endif

            @if ($order->customer_note || (! $isInvoice && $order->staff_note))
                <div class="notes">
                    @if ($order->customer_note)
                        <div><b>Customer note:</b> {{ $order->customer_note }}</div>
                    @endif
                    @if (! $isInvoice && $order->staff_note)
                        <div><b>Staff note:</b> {{ $order->staff_note }}</div>
                    @endif
                </div>
            @endif

            <footer class="foot">
                <div class="thanks">
                    @if ($isInvoice)
                        {{ $store->invoice_footer }}
                    @else
                        Packed by: ____________________ &nbsp; Checked by: ____________________
                    @endif
                </div>
                @if ($isInvoice)
                    <div class="sign">Authorised signature</div>
                @endif
            </footer>
        </section>
    @endforeach

    @if ($autoprint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
