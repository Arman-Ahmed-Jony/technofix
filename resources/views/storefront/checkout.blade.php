@php
    $contact = app(\App\Settings\StoreSettings::class)->toArray();
    app(\App\Support\Tracking::class)->push('begin_checkout', ['items' => 'cart']); // filled in from the browser cart
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
    $hasAreaRules = collect($deliveryOptions)->contains(fn ($option) => $option['type'] === 'delivery' && $option['area'] !== 'all');
    // Signed-in customers: their details and default address are filled in (validated on the server as usual).
    $customer ??= null;
    $addresses ??= [];
    $default = $addresses[0] ?? null;
    $optionIcon = fn (array $option) => match (true) {
        $option['type'] === 'pickup' => 'building-store',
        $option['area'] === 'only' => 'truck-delivery',
        default => 'truck',
    };
@endphp

<x-layouts.storefront title="Checkout" :minimal="true">
    {{-- Delivery options, charges and free-delivery threshold come from Admin → Settings → Delivery charges.
         draftUrl: the form is saved as it's filled in, so staff can call customers who leave (Settings → Stock & orders). --}}
    <div class="container-x pt-6 lg:pt-10"
        x-data="checkout({ options: @js($deliveryOptions), freeOver: @js($freeOver), district: @js(old('district', $default['district'] ?? '')), delivery: @js(old('delivery')),
            couponUrl: @js(route('checkout.coupon')), couponCode: @js(old('coupon', '')), couponError: @js($errors->first('coupon')),
            draftUrl: @js($draftUrl ?? null), restore: @js($restoreCart ?? null) })"
        @visibilitychange.document="document.visibilityState === 'hidden' && saveDraft(true)" @pagehide.window="saveDraft(true)">

        {{-- Progress --}}
        <ol class="mx-auto flex max-w-lg items-center justify-center gap-2 text-[12.5px] font-medium" aria-label="Checkout progress">
            <li><a href="{{ route('cart') }}" class="flex items-center gap-2 text-muted hover:text-ink"><span class="grid size-6 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check" class="size-3.5 [stroke-width:3]" /></span> Cart</a></li>
            <li class="h-px w-10 bg-line-strong sm:w-16" aria-hidden="true"></li>
            <li class="flex items-center gap-2 text-ink" aria-current="step"><span class="grid size-6 place-items-center rounded-full bg-ink font-mono text-[11px] text-canvas">2</span> Details</li>
            <li class="h-px w-10 bg-line sm:w-16" aria-hidden="true"></li>
            <li class="flex items-center gap-2 text-faint"><span class="grid size-6 place-items-center rounded-full bg-card-2 font-mono text-[11px] ring-1 ring-line">3</span> Done</li>
        </ol>

        <div class="mt-7 text-center">
            <h1 class="font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[38px]">Checkout</h1>
            @if ($customer)
                <p class="mt-2 text-sm text-muted">Signed in as <span class="font-medium text-ink">{{ $customer->name }}</span> · pay in cash when your order arrives</p>
            @else
                <p class="mt-2 text-sm text-muted">No account needed · pay in cash when your order arrives · <a href="{{ route('account.login', ['to' => 'checkout']) }}" class="link">Sign in</a> for faster checkout</p>
            @endif
        </div>

        {{-- Empty cart --}}
        <div x-show="$store.cart.count === 0" x-cloak class="card mx-auto mt-10 max-w-md px-6 py-12 text-center">
            <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-card-2 ring-1 ring-line"><x-icon name="shopping-bag" class="size-8 [stroke:url(#tf-grad)] [stroke-width:1.4]" /></span>
            <p class="mt-5 font-display text-lg font-semibold text-ink">Your cart is empty</p>
            <p class="mt-1 text-sm text-muted">Add a few products, then come back to check out.</p>
            <a href="{{ route('home') }}" class="btn btn-ink mt-6">Browse products</a>
        </div>

        <form id="checkout-form" method="post" action="{{ route('checkout.store') }}" x-show="$store.cart.count > 0" @submit="submitting = true" @input="queueDraft()" @change="queueDraft()" novalidate
            class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-10">
            @csrf
            <input type="hidden" name="items" :value="itemsJson">

            {{-- ─── Order summary (first on mobile, collapsible; sticky sidebar on desktop) ─── --}}
            <aside class="lg:sticky lg:top-24 lg:order-2" aria-label="Order summary">
                <div class="overflow-hidden rounded-[24px] border border-line bg-card shadow-card">
                    <button type="button" class="flex w-full items-center justify-between gap-3 p-4 text-left lg:hidden" @click="summaryOpen = !summaryOpen" :aria-expanded="summaryOpen">
                        <span class="flex items-center gap-2 text-sm font-medium text-ink">
                            <x-icon name="shopping-bag" class="size-[18px] text-highlight" />
                            <span x-text="summaryOpen ? 'Hide order summary' : 'Show order summary'">Order summary</span>
                            <x-icon name="chevron-down" class="size-4 text-faint transition" ::class="summaryOpen && 'rotate-180'" />
                        </span>
                        <span class="price font-display text-lg" x-text="money(total)"></span>
                    </button>

                    <div class="border-t border-line p-5 sm:p-6 lg:border-t-0" :class="summaryOpen ? '' : 'max-lg:hidden'">
                        <h2 class="hidden font-display text-lg font-semibold tracking-tight text-ink lg:block">Order summary</h2>

                        {{-- Scrolls when the cart is long; the padding keeps the quantity badges and frames from being clipped. --}}
                        <ul class="scrollbar-none -mx-1.5 -mt-2 max-h-80 space-y-3.5 overflow-y-auto px-1.5 pt-2 pb-1 lg:mt-3">
                            <template x-for="item in $store.cart.items" :key="item.key">
                                <li class="flex items-center gap-3">
                                    <span class="relative grid size-14 shrink-0 place-items-center rounded-xl bg-card-2 ring-1 ring-line">
                                        <x-icon name="package" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.4]" />
                                        <span class="absolute -top-1.5 -right-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-ink px-1 font-mono text-[10.5px] text-canvas" x-text="item.qty"></span>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="line-clamp-2 text-[13px] leading-snug font-medium text-ink" x-text="item.name"></span>
                                        <span x-show="item.color" x-cloak class="mt-0.5 block text-[12px] text-muted" x-text="'Color: ' + item.color"></span>
                                        <span class="mt-0.5 block font-mono text-[11.5px] text-muted" x-text="money(item.price) + ' each'"></span>
                                    </span>
                                    <span class="font-mono text-[13px] text-ink" x-text="money(item.price * item.qty)"></span>
                                </li>
                            </template>
                        </ul>

                        {{-- Coupon: checked by the server (checkout.coupon) and checked again when the order is placed. --}}
                        <div class="mt-5 border-t border-line pt-5">
                            <input type="hidden" name="coupon" :value="coupon?.code ?? ''">
                            <div x-show="! coupon" class="flex gap-2">
                                <label for="coupon-code" class="sr-only">Coupon code</label>
                                <input id="coupon-code" type="text" placeholder="Coupon code" x-model="couponCode" @keydown.enter.prevent="applyCoupon()" @input="couponError = ''"
                                    class="input h-10 font-mono text-[13px] uppercase placeholder:font-sans placeholder:normal-case" autocomplete="off" maxlength="40"
                                    :class="couponError && 'border-rose-500 ring-4 ring-rose-500/10'" :aria-invalid="couponError ? 'true' : 'false'" aria-describedby="coupon-message">
                                <button type="button" class="btn btn-sm btn-soft h-10 min-w-[74px]" @click="applyCoupon()" :disabled="couponBusy">
                                    <span x-show="! couponBusy">Apply</span>
                                    <x-icon name="loader-2" class="size-4 animate-spin" x-show="couponBusy" x-cloak />
                                </button>
                            </div>
                            <div x-show="coupon" x-cloak class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/[0.06] px-3 py-2.5">
                                <x-icon name="discount" class="size-[18px] shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <span class="min-w-0 flex-1 text-[13px] leading-snug">
                                    <span class="font-mono font-semibold text-ink" x-text="coupon?.code"></span>
                                    <span class="text-muted" x-text="'· ' + (coupon?.summary ?? '')"></span>
                                </span>
                                <button type="button" class="text-[12.5px] font-medium text-muted underline-offset-2 hover:text-ink hover:underline" @click="removeCoupon()">Remove</button>
                            </div>
                            <p id="coupon-message" class="mt-2 text-[12.5px]" aria-live="polite"
                                :class="couponError ? 'text-rose-600 dark:text-rose-400' : 'text-amber-700 dark:text-amber-400'"
                                x-show="couponError || couponNote" x-text="couponError || couponNote" x-cloak></p>
                        </div>

                        <dl class="mt-5 space-y-2.5 border-t border-line pt-5 text-sm">
                            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="font-mono text-ink" x-text="money(subtotal)"></dd></div>
                            <div class="flex justify-between">
                                <dt class="text-muted">Delivery</dt>
                                <dd class="font-mono" :class="deliveryFee === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-ink'" x-text="deliveryFee === 0 ? 'Free' : money(deliveryFee)"></dd>
                            </div>
                            <div class="flex justify-between" x-show="discount > 0" x-cloak>
                                <dt class="text-muted">Discount <span class="font-mono text-[12px]" x-text="'(' + (coupon?.code ?? '') + ')'"></span></dt>
                                <dd class="font-mono text-emerald-600 dark:text-emerald-400" x-text="'− ' + money(discount)"></dd>
                            </div>
                            <div class="flex items-end justify-between border-t border-line pt-3.5">
                                <dt class="font-medium text-ink">Total <span class="block text-[11.5px] font-normal text-faint">Pay on delivery</span></dt>
                                <dd class="price font-display text-[26px]" x-text="money(total)"></dd>
                            </div>
                        </dl>

                        <button type="submit" class="btn btn-lg btn-primary mt-6 hidden w-full lg:flex" :disabled="submitting">
                            <span x-show="!submitting" class="flex items-center gap-2"><x-icon name="lock" class="size-4" /> Place order</span>
                            <span x-show="submitting" x-cloak>Placing order…</span>
                        </button>
                        <p class="mt-3 hidden text-center text-[11.5px] text-faint lg:block">Prices are confirmed by our system when you place the order.</p>

                        <ul class="mt-5 grid grid-cols-3 gap-2 border-t border-line pt-5 text-center text-[11px] text-muted">
                            @foreach ([['lock', 'Secure checkout'], ['rosette-discount-check', '100% genuine'], ['cash', 'Pay on delivery']] as [$icon, $label])
                                <li class="flex flex-col items-center gap-1.5"><x-icon :name="$icon" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.6]" /> {{ $label }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </aside>

            {{-- ─── Details ─── --}}
            <div class="space-y-5 lg:order-1">
                @if ($errors->any())
                    <div class="flex items-start gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-700 dark:text-rose-300" role="alert">
                        <x-icon name="alert-triangle" class="mt-0.5 size-5 shrink-0" />
                        <div>
                            <p class="font-semibold">Please check the highlighted fields.</p>
                            @error('items') <p class="mt-1">{{ $message }} <a href="{{ route('cart') }}" class="font-semibold underline">Review cart</a></p> @enderror
                            @error('payment') <p class="mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- 1. Contact --}}
                <section class="card p-5 sm:p-7">
                    <h2 class="flex items-center gap-3 font-display text-lg font-semibold tracking-tight text-ink">
                        <span class="grid size-8 place-items-center rounded-full bg-ink font-mono text-[13px] text-canvas">1</span> Contact details
                    </h2>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="mb-1.5 block text-[13px] font-medium text-ink">Full name <span class="text-accent-600">*</span></label>
                            <input id="name" name="name" value="{{ old('name', $default['name'] ?? $customer?->name) }}" autocomplete="name" required placeholder="e.g. Rahim Ahmed" class="input {{ $invalid('name') }}">
                            <x-form.error for="name" />
                        </div>
                        <div>
                            <label for="phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number <span class="text-accent-600">*</span></label>
                            <div @class([
                                'flex h-11 overflow-hidden rounded-xl border bg-card focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-500/15',
                                'border-rose-500 ring-4 ring-rose-500/10' => $errors->has('phone'),
                                'border-line-strong' => ! $errors->has('phone'),
                            ])>
                                <span class="grid place-items-center border-r border-line bg-card-2 px-3 font-mono text-[13px] text-muted">+88</span>
                                <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $default['phone'] ?? $customer?->phone) }}" autocomplete="tel-national" required placeholder="01XXXXXXXXX"
                                    class="min-w-0 flex-1 bg-transparent px-3 font-mono text-sm text-ink placeholder:text-faint focus:outline-none">
                            </div>
                            <x-form.error for="phone" />
                            @unless ($errors->has('phone'))
                                <p class="mt-1.5 text-[12px] text-faint">We’ll call this number to confirm your order.</p>
                            @endunless
                        </div>
                        <div>
                            <label for="email" class="mb-1.5 block text-[13px] font-medium text-ink">Email <span class="font-normal text-faint">(optional)</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email', $customer?->email) }}" autocomplete="email" placeholder="For order updates" class="input {{ $invalid('email') }}">
                            <x-form.error for="email" />
                        </div>
                    </div>
                </section>

                {{-- 2. Delivery --}}
                <section class="card p-5 sm:p-7">
                    <h2 class="flex items-center gap-3 font-display text-lg font-semibold tracking-tight text-ink">
                        <span class="grid size-8 place-items-center rounded-full bg-ink font-mono text-[13px] text-canvas">2</span> Delivery
                    </h2>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Delivery method">
                        @foreach ($deliveryOptions as $i => $option)
                            {{-- Options that don't serve the chosen district are hidden. --}}
                            <label x-show="serves(options[{{ $i }}])" class="relative flex cursor-pointer flex-col gap-2 rounded-2xl border border-line bg-card p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-500/[0.04] has-checked:ring-4 has-checked:ring-brand-500/10">
                                <span class="flex items-center justify-between">
                                    <x-icon :name="$optionIcon($option)" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.7]" />
                                    <input type="radio" name="delivery" value="{{ $option['code'] }}" x-model="delivery" class="size-4 accent-brand-600">
                                </span>
                                <span class="text-[14px] font-semibold text-ink">{{ $option['label'] }}</span>
                                <span class="flex items-center justify-between gap-2 text-[12.5px]">
                                    <span class="text-muted">{{ $option['eta'] }}</span>
                                    <span class="font-mono font-medium text-ink" x-text="feeLabel(options[{{ $i }}])">{{ $option['fee'] ? '৳'.number_format($option['fee']) : 'Free' }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-form.error for="delivery" />
                    @if ($hasAreaRules)
                        <p class="mt-2.5 text-[12px] text-faint" x-show="!district" x-cloak>Charges depend on your district — choose it below.</p>
                    @endif
                    @if ($freeOver)
                        <p class="mt-2.5 flex items-center gap-1.5 text-[12.5px] text-emerald-700 dark:text-emerald-400">
                            <x-icon name="gift" class="size-4" />
                            <span x-text="subtotal >= freeOver ? 'Your order qualifies for free home delivery!' : 'Free home delivery on orders over ' + money(freeOver) + '.'"></span>
                        </p>
                    @endif

                    <div x-show="needsAddress" x-collapse>
                        @if ($addresses)
                            <div class="mt-5" @unless (old('address')) x-init="addressId = @js($default['id'])" @endunless>
                                <p class="mb-2 text-[13px] font-medium text-ink">Your saved addresses</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($addresses as $saved)
                                        <button type="button" @click="useAddress(@js($saved))"
                                            class="rounded-xl bg-card-2 px-3 py-2 text-left text-[12.5px] ring-1 ring-line transition hover:ring-brand-500/50"
                                            :class="addressId === {{ $saved['id'] }} && 'ring-2 ring-brand-500'">
                                            <span class="block font-semibold text-ink">{{ $saved['label'] ?: 'Address' }}</span>
                                            <span class="block text-muted">{{ collect([$saved['area'], $saved['district']])->filter()->join(', ') }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="district" class="mb-1.5 block text-[13px] font-medium text-ink">District <span class="text-accent-600">*</span></label>
                                <select id="district" name="district" x-model="district" @change="pickDistrict()" class="input cursor-pointer {{ $invalid('district') }}">
                                    <option value="">Select your district</option>
                                    @foreach ($districts as $division => $list)
                                        <optgroup label="{{ $division }} Division">
                                            @foreach ($list as $name)
                                                <option value="{{ $name }}" @selected(old('district', $default['district'] ?? null) === $name)>{{ $name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <x-form.error for="district" />
                            </div>
                            <div>
                                <label for="area" class="mb-1.5 block text-[13px] font-medium text-ink">Area / Thana <span class="text-accent-600">*</span></label>
                                <input id="area" name="area" value="{{ old('area', $default['area'] ?? null) }}" autocomplete="address-level3" placeholder="e.g. Mirpur 10" class="input {{ $invalid('area') }}">
                                <x-form.error for="area" />
                            </div>
                            <div class="sm:col-span-2">
                                <label for="address" class="mb-1.5 block text-[13px] font-medium text-ink">Full address <span class="text-accent-600">*</span></label>
                                <textarea id="address" name="address" rows="2" autocomplete="street-address" placeholder="House, road, block and a nearby landmark"
                                    class="input h-auto min-h-20 resize-y py-2.5 {{ $invalid('address') }}">{{ old('address', $default['address'] ?? null) }}</textarea>
                                <x-form.error for="address" />
                            </div>
                            @if ($customer)
                                <label class="flex cursor-pointer items-center gap-2.5 text-[13px] text-ink-2 sm:col-span-2">
                                    <input type="checkbox" name="save_address" value="1" class="checkbox" @checked(old('save_address', ! $addresses))> Save this address to my account
                                </label>
                            @endif
                        </div>
                    </div>

                    <div x-show="!needsAddress" x-collapse x-cloak>
                        <div class="mt-5 flex items-start gap-3 rounded-2xl bg-card-2 p-4 ring-1 ring-line">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-card ring-1 ring-line"><x-icon name="building-store" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.7]" /></span>
                            <div class="text-[13.5px]">
                                <p class="font-semibold text-ink">Pick up from TechnoFix</p>
                                <p class="mt-0.5 text-muted">{{ $contact['address'] }} · {{ $contact['hours'] }}</p>
                                <p class="mt-1 text-[12.5px] text-faint">We’ll call you when your order is ready.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3. Payment --}}
                <section class="card p-5 sm:p-7">
                    <h2 class="flex items-center gap-3 font-display text-lg font-semibold tracking-tight text-ink">
                        <span class="grid size-8 place-items-center rounded-full bg-ink font-mono text-[13px] text-canvas">3</span> Payment
                    </h2>
                    {{-- Methods: App\Payments\PaymentGateways; names, text and "coming soon" list: Admin → Settings → Payments. --}}
                    <div class="mt-5 space-y-3">
                        @foreach ($paymentMethods as $gateway)
                            <label class="flex cursor-pointer items-center gap-4 rounded-2xl border border-line p-4 transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-500/[0.04] has-checked:ring-4 has-checked:ring-brand-500/10">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-emerald-500/10 ring-1 ring-emerald-500/20"><x-icon :name="$gateway->icon()" class="size-6 text-emerald-600 dark:text-emerald-400" /></span>
                                <span class="flex-1">
                                    <span class="block text-[14.5px] font-semibold text-ink">{{ $gateway->label() }}</span>
                                    @if (filled($gateway->description()))
                                        <span class="block text-[12.5px] text-muted">{{ $gateway->description() }}</span>
                                    @endif
                                </span>
                                <input type="radio" name="payment" value="{{ $gateway->method()->value }}" class="size-4 accent-brand-600"
                                    @checked(old('payment', $paymentMethods[0]->method()->value) === $gateway->method()->value)>
                            </label>
                        @endforeach
                    </div>
                    @if (filled($comingSoon))
                        <div class="mt-3 grid grid-cols-3 gap-2">
                            @foreach ($comingSoon as $method)
                                <div class="flex flex-col items-center gap-1 rounded-xl border border-dashed border-line-strong px-2 py-3 text-center opacity-70">
                                    <span class="text-[13px] font-semibold text-ink-2">{{ $method }}</span>
                                    <span class="text-[10.5px] tracking-wider text-faint uppercase">Coming soon</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Note --}}
                <section class="card p-5 sm:p-7" x-data="{ open: {{ old('note') ? 'true' : 'false' }} }">
                    <button type="button" class="flex w-full items-center justify-between text-left" @click="open = !open" :aria-expanded="open">
                        <span class="flex items-center gap-3 text-[15px] font-semibold text-ink"><x-icon name="message-circle" class="size-5 text-faint" /> Add a note for us <span class="font-normal text-faint">(optional)</span></span>
                        <x-icon name="plus" class="size-4 text-faint transition" ::class="open && 'rotate-45'" />
                    </button>
                    <div x-show="open" x-collapse x-cloak>
                        <textarea name="note" rows="3" maxlength="500" placeholder="Delivery instructions, preferred call time, etc." class="input mt-4 h-auto resize-y py-2.5">{{ old('note') }}</textarea>
                    </div>
                </section>

                <x-turnstile form="checkout" />
                <p class="text-center text-[12px] text-faint lg:text-left">
                    By placing your order you agree to our <a href="{{ url('page/terms') }}" class="underline hover:text-ink">terms</a> and
                    <a href="{{ url('page/refund-policy') }}" class="underline hover:text-ink">return policy</a>.
                </p>
            </div>
        </form>

        {{-- Mobile: sticky place-order bar --}}
        <div x-show="$store.cart.count > 0" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-canvas/90 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur-xl lg:hidden">
            <button type="submit" form="checkout-form" class="btn btn-lg btn-primary w-full" :disabled="submitting">
                <x-icon name="lock" class="size-4" />
                <span x-text="submitting ? 'Placing order…' : 'Place order · ' + money(total)">Place order</span>
            </button>
        </div>
    </div>
</x-layouts.storefront>
