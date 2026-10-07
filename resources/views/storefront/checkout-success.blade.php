@php
    $contact = app(\App\Settings\StoreSettings::class)->toArray();
    $firstName = Str::before(trim($order->name), ' ');
    $whatsapp = $contact['whatsapp'] ? 'https://wa.me/'.$contact['whatsapp'].'?text='.rawurlencode("Hi, about my order {$order->number}") : url('contact');
@endphp

<x-layouts.storefront title="Order placed" :minimal="true">
    {{-- The order is confirmed server-side, so the browser cart can be emptied. --}}
    <div class="container-x pt-6 lg:pt-10" x-data x-init="$store.cart.clear()">
        <div class="mx-auto max-w-3xl">
            <ol class="mx-auto flex max-w-lg items-center justify-center gap-2 text-[12.5px] font-medium" aria-label="Checkout progress">
                <li class="flex items-center gap-2 text-muted"><span class="grid size-6 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check" class="size-3.5 [stroke-width:3]" /></span> Cart</li>
                <li class="h-px w-10 bg-emerald-500/50 sm:w-16" aria-hidden="true"></li>
                <li class="flex items-center gap-2 text-muted"><span class="grid size-6 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check" class="size-3.5 [stroke-width:3]" /></span> Details</li>
                <li class="h-px w-10 bg-emerald-500/50 sm:w-16" aria-hidden="true"></li>
                <li class="flex items-center gap-2 text-ink" aria-current="step"><span class="grid size-6 place-items-center rounded-full bg-ink font-mono text-[11px] text-canvas">3</span> Done</li>
            </ol>

            {{-- Confirmation --}}
            <div class="mesh-dark grain relative isolate mt-7 overflow-hidden rounded-[28px] px-6 py-10 text-center text-white sm:px-12 sm:py-12">
                <div class="grid-lines absolute inset-0 -z-10"></div>
                <span class="relative mx-auto grid size-20 place-items-center">
                    <span class="absolute inset-0 rounded-full bg-emerald-400/30 motion-safe:animate-ping-slow"></span>
                    <span class="relative grid size-20 place-items-center rounded-full bg-emerald-400 text-navy-950 shadow-[0_12px_40px_-8px_rgb(52_211_153/0.7)]"><x-icon name="check" class="size-10 [stroke-width:2.6]" /></span>
                </span>
                <p class="mt-7 eyebrow justify-center text-emerald-300">Order placed</p>
                <h1 class="mt-3 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] sm:text-[38px]">Thank you, {{ $firstName }}!</h1>
                <p class="mx-auto mt-3 max-w-md text-[15px] text-white/70">We’ll call <span class="font-mono text-white">{{ $order->phone }}</span> shortly to confirm your order. Keep your phone nearby.</p>
                <div class="mt-6 inline-flex items-center gap-3 rounded-2xl bg-white/10 py-2 pr-2 pl-4 ring-1 ring-white/15" x-data="{ copied: false }">
                    <span class="text-xs text-white/60">Order no.</span>
                    <span class="font-mono text-[15px] font-medium tracking-wide">{{ $order->number }}</span>
                    <button type="button" class="btn-glass grid size-8 place-items-center rounded-lg" aria-label="Copy order number"
                        @click="navigator.clipboard.writeText(@js($order->number)).then(() => { copied = true; setTimeout(() => copied = false, 1500) })">
                        <x-icon name="copy" class="size-4" x-show="!copied" /><x-icon name="check" class="size-4 text-emerald-300" x-show="copied" x-cloak />
                    </button>
                </div>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <section class="card p-6">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-ink">What happens next</h2>
                    <ol class="mt-5 space-y-5">
                        @foreach ([
                            ['phone-call', 'We call to confirm', 'Usually within an hour during business hours.'],
                            ['package', 'Packed & checked', 'Your items are tested, sealed and handed to the courier.'],
                            $order->is_pickup
                                ? ['building-store', 'Ready for pickup'.($order->delivery_eta ? ' · '.$order->delivery_eta : ''), 'Pay ৳'.number_format($order->total).' in cash when you collect it.']
                                : ['truck-delivery', 'Delivered'.($order->delivery_eta ? ' · '.$order->delivery_eta : ''), 'Pay ৳'.number_format($order->total).' in cash when it arrives.'],
                        ] as [$icon, $title, $text])
                            <li class="flex gap-3.5">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-card-2 ring-1 ring-line"><x-icon :name="$icon" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.7]" /></span>
                                <span><span class="block text-[14px] font-semibold text-ink">{{ $title }}</span><span class="block text-[13px] text-muted">{{ $text }}</span></span>
                            </li>
                        @endforeach
                    </ol>
                </section>

                <section class="card p-6">
                    <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Delivery details</h2>
                    <dl class="mt-4 divide-y divide-line text-[13.5px]">
                        @foreach (array_filter([
                            'Name' => $order->name,
                            'Mobile' => $order->phone,
                            'Email' => $order->email,
                            'Delivery' => $order->delivery_label,
                            'Address' => $order->is_pickup ? null : $order->fullAddress(),
                            'Payment' => $order->payment_method->getLabel(),
                            'Note' => $order->customer_note,
                        ]) as $label => $value)
                            <div class="grid grid-cols-[88px_minmax(0,1fr)] gap-3 py-2.5">
                                <dt class="text-muted">{{ $label }}</dt>
                                <dd @class(['text-ink', 'font-mono' => $label === 'Mobile'])>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </div>

            <section class="card mt-5 p-6">
                <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Order summary</h2>
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-3 py-3">
                            @if ($item->product)
                                <x-product.image :product="$item->product" icon-class="size-[50%]" class="size-12 shrink-0" />
                            @else
                                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-card-2 ring-1 ring-line"><x-icon name="package" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.4]" /></span>
                            @endif
                            <span class="min-w-0 flex-1">
                                @if ($item->product && ! $item->product->trashed())
                                    <a href="{{ $item->product->url }}" class="line-clamp-1 text-[13.5px] font-medium text-ink hover:text-highlight">{{ $item->label() }}</a>
                                @else
                                    <span class="line-clamp-1 text-[13.5px] font-medium text-ink">{{ $item->label() }}</span>
                                @endif
                                <span class="font-mono text-[12px] text-muted">{{ $item->qty }} × ৳{{ number_format($item->price) }}</span>
                            </span>
                            <span class="font-mono text-[13.5px] text-ink">৳{{ number_format($item->total) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="mt-2 space-y-2 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="font-mono text-ink">৳{{ number_format($order->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Delivery</dt><dd class="font-mono text-ink">{{ $order->delivery_fee ? '৳'.number_format($order->delivery_fee) : 'Free' }}</dd></div>
                    @if ($order->discount)
                        <div class="flex justify-between"><dt class="text-muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt><dd class="font-mono text-emerald-600 dark:text-emerald-400">− ৳{{ number_format($order->discount) }}</dd></div>
                    @endif
                    <div class="flex items-end justify-between border-t border-line pt-3"><dt class="font-medium text-ink">Total · cash on delivery</dt><dd class="price font-display text-2xl">৳{{ number_format($order->total) }}</dd></div>
                </dl>
            </section>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('home') }}" class="btn btn-lg btn-ink">Continue shopping <x-icon name="arrow-right" class="size-4" /></a>
                @if (Route::has('orders.track'))
                    <a href="{{ route('orders.track.show', $order->number) }}" class="btn btn-lg btn-outline"><x-icon name="truck-delivery" class="size-5" /> Track order</a>
                @endif
                <a href="{{ $whatsapp }}" class="btn btn-lg btn-whatsapp"><x-icon name="brand-whatsapp" class="size-5" /> WhatsApp us</a>
            </div>

            @if (! $order->customer_id && ! auth('customer')->check())
                <section class="mt-8 flex flex-col items-start gap-4 rounded-[24px] border border-dashed border-line-strong p-5 sm:flex-row sm:items-center">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-500/10 ring-1 ring-brand-500/25"><x-icon name="user-plus" class="size-6 text-highlight" /></span>
                    <div class="flex-1">
                        <p class="font-semibold text-ink">Save your details for next time</p>
                        <p class="text-[13px] text-muted">Create a password and this order goes into your account — next checkout fills itself in.</p>
                    </div>
                    <a href="{{ route('account.register') }}" class="btn btn-outline">Create account</a>
                </section>
            @endif
        </div>
    </div>
</x-layouts.storefront>
