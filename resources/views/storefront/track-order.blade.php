@php
    $store = app(\App\Settings\StoreSettings::class);
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
@endphp

<x-layouts.storefront page-key="track-order">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Track order', 'url' => null]]" />

        <div class="mx-auto mt-6 grid max-w-4xl items-stretch gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
            <section class="mesh-dark grain relative isolate overflow-hidden rounded-[28px] px-6 py-9 text-white sm:px-9">
                <div class="grid-lines absolute inset-0 -z-10"></div>
                <span class="glass grid size-14 place-items-center rounded-2xl"><x-icon name="truck-delivery" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                <p class="eyebrow mt-6 text-accent-300">Order status</p>
                <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] sm:text-[36px]">Track your order</h1>
                <p class="mt-3 max-w-sm text-[15px] text-white/65">Enter the order number from your confirmation (like <span class="font-mono text-white">TF-260929-001</span>) and the mobile number you ordered with.</p>
                <p class="mt-8 text-[13px] text-white/55">Can’t find your order number? Call <a href="{{ $store->telHref() }}" class="font-mono text-white">{{ $store->hotline }}</a>.</p>
            </section>

            <section class="card p-6 sm:p-8">
                <form method="post" action="{{ route('orders.track.lookup') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
                    @csrf
                    <div>
                        <label for="number" class="mb-1.5 block text-[13px] font-medium text-ink">Order number</label>
                        <input id="number" name="number" value="{{ old('number', request('number')) }}" required autocomplete="off" placeholder="TF-260929-001" class="input font-mono uppercase {{ $invalid('number') }}">
                        <x-form.error for="number" />
                    </div>
                    <div>
                        <label for="phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number</label>
                        <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" required autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input font-mono {{ $invalid('phone') }}">
                        <x-form.error for="phone" />
                    </div>
                    <x-turnstile form="track_order" />
                    <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="sending">
                        <x-icon name="search" class="size-5" /> <span x-text="sending ? 'Looking…' : 'Track order'">Track order</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.storefront>
