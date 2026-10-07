{{-- Slide-over cart. Prototype: items live in the browser; the server-side cart arrives in Phase 4. --}}
<div x-show="$store.ui.cartOpen" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Shopping cart"
    @keydown.escape.window="$store.ui.cartOpen = false">
    <div x-show="$store.ui.cartOpen" x-transition.opacity class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" @click="$store.ui.cartOpen = false"></div>

    <aside x-show="$store.ui.cartOpen" x-trap.noscroll="$store.ui.cartOpen"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-line bg-canvas shadow-2xl">
        <div class="flex items-center justify-between border-b border-line px-5 py-4">
            <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Your cart <span class="font-sans text-sm font-medium text-muted" x-text="'· ' + $store.cart.count + ($store.cart.count === 1 ? ' item' : ' items')"></span></h2>
            <button type="button" class="icon-btn" @click="$store.ui.cartOpen = false" aria-label="Close cart"><x-icon name="x" class="size-5" /></button>
        </div>

        <template x-if="$store.cart.items.length === 0">
            <div class="flex flex-1 flex-col items-center justify-center px-8 text-center">
                <span class="grid size-20 place-items-center rounded-3xl bg-card ring-1 ring-line"><x-icon name="shopping-bag" class="size-9 [stroke:url(#tf-grad)] [stroke-width:1.25]" /></span>
                <p class="mt-5 font-display text-lg font-semibold text-ink">Your cart is empty</p>
                <p class="mt-1 text-sm text-muted">Browse our categories and add something you like.</p>
                <button type="button" class="btn btn-ink mt-6" @click="$store.ui.cartOpen = false">Continue shopping</button>
            </div>
        </template>

        <ul class="flex-1 space-y-2.5 overflow-y-auto overscroll-contain p-4" x-show="$store.cart.items.length">
            <template x-for="item in $store.cart.items" :key="item.key">
                <li class="flex gap-3 rounded-2xl border border-line bg-card p-3">
                    <a :href="item.url" class="grid size-18 shrink-0 place-items-center rounded-xl bg-card-2" aria-hidden="true" tabindex="-1">
                        <x-icon name="package" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.25]" />
                    </a>
                    <div class="min-w-0 flex-1">
                        <div class="flex gap-2">
                            <a :href="item.url" class="line-clamp-2 flex-1 text-[13.5px] leading-snug font-medium text-ink hover:text-highlight" x-text="item.name"></a>
                            <button type="button" class="self-start p-0.5 text-faint transition hover:text-rose-500" @click="$store.cart.remove(item.key)" aria-label="Remove"><x-icon name="trash" class="size-4" /></button>
                        </div>
                        <span x-show="item.color" x-cloak class="mt-0.5 block text-[12px] text-muted" x-text="'Color: ' + item.color"></span>
                        <div class="mt-2.5 flex items-center justify-between">
                            <div class="flex h-8 items-center rounded-lg bg-card-2 ring-1 ring-line">
                                <button type="button" class="grid h-full w-8 place-items-center text-muted hover:text-ink" @click="$store.cart.setQty(item.key, item.qty - 1)" aria-label="Decrease"><x-icon name="minus" class="size-3.5" /></button>
                                <span class="w-7 text-center font-mono text-[13px] font-medium text-ink" x-text="item.qty"></span>
                                <button type="button" class="grid h-full w-8 place-items-center text-muted hover:text-ink" @click="$store.cart.setQty(item.key, item.qty + 1)" aria-label="Increase"><x-icon name="plus" class="size-3.5" /></button>
                            </div>
                            <span class="price text-[15px]" x-text="'৳' + (item.price * item.qty).toLocaleString('en-US')"></span>
                        </div>
                    </div>
                </li>
            </template>
        </ul>

        <div class="border-t border-line bg-card px-5 pt-4 pb-[calc(1rem+env(safe-area-inset-bottom))]" x-show="$store.cart.items.length">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted">Subtotal</span>
                <span class="price font-display text-xl" x-text="'৳' + $store.cart.subtotal.toLocaleString('en-US')"></span>
            </div>
            <p class="mt-1 text-xs text-faint">Delivery charge is calculated at checkout.</p>
            <div class="mt-4 grid grid-cols-2 gap-2.5">
                <a href="{{ url('cart') }}" class="btn btn-outline">View cart</a>
                <a href="{{ url('checkout') }}" class="btn btn-primary">Checkout <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <p class="mt-3 flex items-center justify-center gap-1.5 text-xs text-muted"><x-icon name="shield-check" class="size-4 text-emerald-500" /> Cash on delivery · Genuine products</p>
        </div>
    </aside>
</div>
