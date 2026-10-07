@php
    $contact = app(\App\Settings\StoreSettings::class)->toArray();
    $tel = 'tel:'.preg_replace('/[^0-9+]/', '', $contact['hotline']);
    app(\App\Support\Tracking::class)->push('view_cart', ['items' => 'cart']); // filled in from the browser cart
@endphp

<x-layouts.storefront title="Your cart" :noindex="true">
    <div class="container-x pt-5 lg:pt-7" x-data="{ freeOver: @js($freeOver) }">
        <x-breadcrumbs :items="[['label' => 'Cart', 'url' => null]]" />

        <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="eyebrow">Shopping cart</p>
                <h1 class="mt-2.5 font-display text-[28px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[36px]">
                    Your cart <span class="font-mono text-lg font-medium text-faint" x-text="'(' + $store.cart.count + ')'"></span>
                </h1>
            </div>
            <a href="{{ route('home') }}" class="btn btn-sm btn-soft"><x-icon name="arrow-left" class="size-3.5" /> Continue shopping</a>
        </div>

        {{-- Empty --}}
        <div x-show="$store.cart.count === 0" x-cloak class="mesh-dark grain relative isolate mt-6 overflow-hidden rounded-[28px] px-6 py-14 text-center text-white">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <span class="glass mx-auto grid size-20 place-items-center rounded-3xl"><x-icon name="shopping-bag" class="size-9 [stroke:url(#tf-grad)] [stroke-width:1.3]" /></span>
            <p class="mt-6 font-display text-2xl font-semibold tracking-tight">Your cart is empty</p>
            <p class="mx-auto mt-2 max-w-sm text-sm text-white/65">Browse today’s deals or build your own PC — we’ll keep your picks here.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}#happy-hour" class="btn btn-lg btn-primary">See today’s deals</a>
                <a href="{{ url('pc-builder') }}" class="btn btn-lg btn-glass"><x-icon name="cpu" class="size-5" /> PC Builder</a>
            </div>
        </div>

        <div x-show="$store.cart.count > 0" class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:gap-10">
            {{-- Items --}}
            <ul class="space-y-3">
                <template x-for="item in $store.cart.items" :key="item.key">
                    <li class="flex gap-4 rounded-[22px] border border-line bg-card p-4 shadow-card sm:items-center sm:p-5">
                        <a :href="item.url" class="grid size-20 shrink-0 place-items-center rounded-2xl bg-card-2 ring-1 ring-line sm:size-24" tabindex="-1" aria-hidden="true">
                            <x-icon name="package" class="size-9 [stroke:url(#tf-grad)] [stroke-width:1.2]" />
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                            <div class="min-w-0 flex-1">
                                <a :href="item.url" class="line-clamp-2 text-[14.5px] leading-snug font-medium text-ink hover:text-highlight" x-text="item.name"></a>
                                <span x-show="item.color" x-cloak class="mt-0.5 block text-[12px] text-muted" x-text="'Color: ' + item.color"></span>
                                <p class="mt-1 font-mono text-[12px] text-muted" x-text="'৳' + item.price.toLocaleString('en-US') + ' each'"></p>
                            </div>
                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div class="flex h-10 items-center rounded-xl bg-card-2 ring-1 ring-line">
                                    <button type="button" class="grid h-full w-9 place-items-center text-muted hover:text-ink" @click="$store.cart.setQty(item.key, item.qty - 1)" aria-label="Decrease"><x-icon name="minus" class="size-3.5" /></button>
                                    <span class="w-8 text-center font-mono text-[14px] font-medium text-ink" x-text="item.qty"></span>
                                    <button type="button" class="grid h-full w-9 place-items-center text-muted hover:text-ink" @click="$store.cart.setQty(item.key, item.qty + 1)" aria-label="Increase"><x-icon name="plus" class="size-3.5" /></button>
                                </div>
                                <span class="price w-24 text-right text-[16px]" x-text="'৳' + (item.price * item.qty).toLocaleString('en-US')"></span>
                                <button type="button" class="icon-btn text-faint hover:text-rose-500" @click="$store.cart.remove(item.key)" aria-label="Remove item"><x-icon name="trash" class="size-[18px]" /></button>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>

            {{-- Summary --}}
            <aside class="rounded-[24px] border border-line bg-card p-6 shadow-card lg:sticky lg:top-[138px]">
                <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Summary</h2>

                <template x-if="freeOver">
                    <div class="mt-4 rounded-2xl bg-card-2 p-3.5 ring-1 ring-line">
                        <p class="text-[12.5px] text-ink-2" x-text="$store.cart.subtotal >= freeOver ? 'You’ve unlocked free delivery!' : 'Add ৳' + (freeOver - $store.cart.subtotal).toLocaleString('en-US') + ' more for free delivery'"></p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-[linear-gradient(90deg,var(--color-brand-500),var(--color-accent-500))] transition-[width] duration-500" :style="{ width: Math.min(100, $store.cart.subtotal / freeOver * 100) + '%' }"></div></div>
                    </div>
                </template>

                <dl class="mt-5 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="font-mono text-ink" x-text="'৳' + $store.cart.subtotal.toLocaleString('en-US')"></dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Delivery</dt><dd class="text-ink-2">From <span class="font-mono">৳{{ number_format($lowestFee ?? 0) }}</span> · at checkout</dd></div>
                    <div class="flex items-end justify-between border-t border-line pt-3.5">
                        <dt class="font-medium text-ink">Estimated total</dt>
                        <dd class="price font-display text-[26px]" x-text="'৳' + $store.cart.subtotal.toLocaleString('en-US')"></dd>
                    </div>
                </dl>

                <a href="{{ route('checkout') }}" class="btn btn-lg btn-primary mt-6 w-full"><x-icon name="lock" class="size-4" /> Checkout</a>
                <a href="{{ $tel }}" class="btn btn-outline mt-2.5 w-full"><x-icon name="phone-call" class="size-4" /> Order by phone</a>

                <ul class="mt-5 space-y-2.5 border-t border-line pt-5 text-[12.5px] text-muted">
                    <li class="flex items-center gap-2.5"><x-icon name="cash" class="size-4 [stroke:url(#tf-grad)]" /> Cash on delivery all over Bangladesh</li>
                    <li class="flex items-center gap-2.5"><x-icon name="shield-check" class="size-4 [stroke:url(#tf-grad)]" /> Official brand warranty</li>
                    <li class="flex items-center gap-2.5"><x-icon name="headset" class="size-4 [stroke:url(#tf-grad)]" /> Expert help before and after you buy</li>
                </ul>
            </aside>
        </div>

        <section class="mt-14 lg:mt-20">
            <x-section-heading eyebrow="Popular add-ons" title="Customers also bought" />
            <x-product.scroller :products="$suggestions" />
        </section>
    </div>
</x-layouts.storefront>
