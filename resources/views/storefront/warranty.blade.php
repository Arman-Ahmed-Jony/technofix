@php
    $store = app(\App\Settings\StoreSettings::class);
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
@endphp

<x-layouts.storefront page-key="warranty">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Warranty status', 'url' => null]]" />

        <div class="mx-auto mt-6 grid max-w-4xl items-stretch gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
            <section class="mesh-dark grain relative isolate overflow-hidden rounded-[28px] px-6 py-9 text-white sm:px-9">
                <div class="grid-lines absolute inset-0 -z-10"></div>
                <span class="glass grid size-14 place-items-center rounded-2xl"><x-icon name="shield-check" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                <p class="eyebrow mt-6 text-accent-300">Warranty service</p>
                <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] sm:text-[36px]">Check your warranty claim</h1>
                <p class="mt-3 max-w-sm text-[15px] text-white/65">{{ $settings->page_intro }}</p>
                <p class="mt-8 text-[13px] text-white/55">Questions? Call <a href="{{ $store->telHref() }}" class="font-mono text-white">{{ $store->hotline }}</a> and mention your claim number.</p>
            </section>

            <section class="card p-6 sm:p-8">
                @if ($settings->lookup_enabled)
                    <form method="post" action="{{ route('warranty.lookup') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
                        @csrf
                        <div>
                            <label for="phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number</label>
                            <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" required autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input font-mono {{ $invalid('phone') }}">
                            <x-form.error for="phone" />
                            <p class="mt-1.5 text-[12.5px] text-muted">The number you gave us when you dropped off the product.</p>
                        </div>
                        <x-turnstile form="warranty" />
                        <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="sending">
                            <x-icon name="search" class="size-5" /> <span x-text="sending ? 'Checking…' : 'Check status'">Check status</span>
                        </button>
                    </form>
                    <ul class="mt-6 space-y-2.5 border-t border-line pt-5 text-[13px] text-muted">
                        <li class="flex gap-2.5"><x-icon name="circle-check" class="mt-0.5 size-4 shrink-0 text-emerald-500" /> See every product we’re handling for you, with its latest status.</li>
                        <li class="flex gap-2.5"><x-icon name="circle-check" class="mt-0.5 size-4 shrink-0 text-emerald-500" /> Gave us your email? We’ll also email you when the status changes.</li>
                    </ul>
                @else
                    <div class="grid h-full place-items-center text-center">
                        <div>
                            <x-icon name="phone-call" class="mx-auto size-8 text-highlight" />
                            <p class="mt-3 font-semibold text-ink">Please call us for your warranty status</p>
                            <p class="mt-1 text-sm text-muted">Our team will check it for you right away.</p>
                            <a href="{{ $store->telHref() }}" class="btn btn-primary mt-5"><x-icon name="phone-call" class="size-4" /> {{ $store->hotline }}</a>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.storefront>
