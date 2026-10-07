@php
    $store = app(\App\Settings\StoreSettings::class);
    $whatsapp = $store->whatsapp ? $store->whatsappUrl("Hi, about my order {$order->number}") : null;
@endphp

<x-layouts.storefront :title="'Order '.$order->number" :noindex="true">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="$breadcrumbs" />

        <div class="mx-auto mt-5 max-w-4xl">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Order</p>
                    <h1 class="mt-2 font-mono text-[26px] font-semibold tracking-tight text-ink sm:text-[32px]">{{ $order->number }}</h1>
                    <p class="mt-1 text-sm text-muted">Placed {{ $order->created_at->format('d M Y, h:i A') }}</p>
                </div>
                <span @class([
                    'chip py-1.5 text-[13px] ring-1',
                    'bg-rose-500/10 text-rose-700 ring-rose-500/20 dark:text-rose-300' => $stopped,
                    'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' => ! $stopped && $order->status === \App\Enums\OrderStatus::Delivered,
                    'bg-brand-500/10 text-highlight ring-brand-500/25' => ! $stopped && $order->status !== \App\Enums\OrderStatus::Delivered,
                ])>
                    {{ $stopped ? $order->status->getLabel() : \App\Http\Controllers\Storefront\TrackOrderController::STEPS[$order->status->value][0] }}
                </span>
            </header>

            <div class="mt-6 grid items-start gap-5 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                <section class="card p-6 sm:p-7">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Progress</h2>

                    @if ($stopped)
                        <div class="mt-5 flex gap-3 rounded-2xl bg-rose-500/[0.07] p-4 ring-1 ring-rose-500/20">
                            <x-icon name="circle-x" class="mt-0.5 size-5 shrink-0 text-rose-600" />
                            <div>
                                <p class="font-semibold text-ink">This order was {{ strtolower($order->status->getLabel()) }}.</p>
                                <p class="mt-0.5 text-sm text-muted">Questions? Call <a href="{{ $store->telHref() }}" class="font-mono text-ink">{{ $store->hotline }}</a> — we’re happy to help.</p>
                            </div>
                        </div>
                    @else
                        <ol class="mt-5">
                            @foreach ($steps as $step)
                                <li class="relative flex gap-4 pb-6 last:pb-0">
                                    @unless ($loop->last)
                                        <span @class(['absolute top-9 left-[17px] h-[calc(100%-2.25rem)] w-0.5', 'bg-emerald-500' => $step['done'] && ! $step['current'], 'bg-line' => ! $step['done'] || $step['current']])></span>
                                    @endunless
                                    <span @class([
                                        'relative grid size-9 shrink-0 place-items-center rounded-full ring-1',
                                        'bg-emerald-500 text-white ring-emerald-500' => $step['done'] && ! $step['current'],
                                        'bg-brand-500 text-white ring-brand-500 shadow-[0_0_0_6px_rgb(0_159_227/0.15)]' => $step['current'],
                                        'bg-card-2 text-faint ring-line' => ! $step['done'],
                                    ])>
                                        @if ($step['done'] && ! $step['current'])
                                            <x-icon name="check" class="size-4 [stroke-width:2.6]" />
                                        @else
                                            <span class="font-mono text-[12px]">{{ $loop->iteration }}</span>
                                        @endif
                                    </span>
                                    <div class="pt-1">
                                        <p @class(['text-[14.5px] font-semibold', 'text-ink' => $step['done'], 'text-muted' => ! $step['done']])>{{ $step['title'] }}</p>
                                        @if ($step['current'])
                                            <p class="mt-0.5 text-[13px] text-muted">{{ $step['text'] }}</p>
                                        @endif
                                        @if ($step['at'] && $step['done'])
                                            <p class="mt-0.5 font-mono text-[12px] text-faint">{{ $step['at']->format('d M, h:i A') }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($order->courier || $order->tracking_code)
                        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2 rounded-2xl bg-card-2 p-4 text-[13.5px] ring-1 ring-line">
                            @if ($order->courier)
                                <span><span class="text-muted">Courier</span> <span class="font-semibold text-ink">{{ $order->courier }}</span></span>
                            @endif
                            @if ($order->tracking_code)
                                <span><span class="text-muted">Tracking no.</span> <span class="font-mono font-semibold text-ink">{{ $order->tracking_code }}</span></span>
                            @endif
                        </div>
                    @endif
                </section>

                <section class="card p-6 sm:p-7">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Your order</h2>
                    <ul class="mt-4 divide-y divide-line">
                        @foreach ($order->items as $item)
                            <li class="flex items-center justify-between gap-3 py-2.5 text-[13.5px]">
                                <span class="min-w-0"><span class="line-clamp-1 font-medium text-ink">{{ $item->label() }}</span><span class="font-mono text-[12px] text-muted">× {{ $item->qty }}</span></span>
                                <span class="shrink-0 font-mono text-ink">৳{{ number_format($item->total) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="mt-2 space-y-1.5 border-t border-line pt-3 text-sm">
                        <div class="flex justify-between"><dt class="text-muted">Delivery</dt><dd class="font-mono text-ink">{{ $order->delivery_fee ? '৳'.number_format($order->delivery_fee) : 'Free' }}</dd></div>
                        @if ($order->discount)
                            <div class="flex justify-between"><dt class="text-muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt><dd class="font-mono text-ink">− ৳{{ number_format($order->discount) }}</dd></div>
                        @endif
                        <div class="flex items-end justify-between border-t border-line pt-2.5"><dt class="font-medium text-ink">Total · {{ strtolower($order->payment_method->getLabel()) }}</dt><dd class="price font-display text-xl">৳{{ number_format($order->total) }}</dd></div>
                        @if ($order->paid_amount > 0)
                            <div class="flex justify-between"><dt class="text-muted">Paid</dt><dd class="font-mono text-emerald-600 dark:text-emerald-400">৳{{ number_format($order->paid_amount) }}</dd></div>
                            @if ($order->amountDue())
                                <div class="flex justify-between"><dt class="font-medium text-ink">Still to pay</dt><dd class="font-mono font-semibold text-ink">৳{{ number_format($order->amountDue()) }}</dd></div>
                            @endif
                        @endif
                    </dl>
                    <p class="mt-4 text-[13px] text-muted">{{ $order->delivery_label }}{{ $order->is_pickup ? '' : ' · '.collect([$order->area, $order->district])->filter()->join(', ') }}</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-outline"><x-icon name="phone-call" class="size-4" /> Call us</a>
                        @if ($whatsapp)
                            <a href="{{ $whatsapp }}" class="btn btn-sm btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts.storefront>
