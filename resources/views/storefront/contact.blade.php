@php
    $store = app(\App\Settings\StoreSettings::class);
    $mapUrl = 'https://maps.google.com/maps?q='.rawurlencode($store->address).'&output=embed';
@endphp

<x-layouts.storefront page-key="contact">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Contact us', 'url' => null]]" />

        <header class="mt-4 text-center">
            <p class="eyebrow justify-center">Contact</p>
            <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[42px]">We’d love to help</h1>
            <p class="mx-auto mt-3 max-w-lg text-[15.5px] text-muted">Questions about a product, an order, a repair or a bulk quote — reach us the way that suits you.</p>
        </header>

        {{-- Channels (WhatsApp only when a number is set in Admin → Settings) --}}
        @php
            $channels = array_values(array_filter([
                ...array_map(fn ($phone) => ['phone-call', $phone['label'] === 'Hotline' ? 'Call us' : $phone['label'], $phone['number'], $phone['href'], 'font-mono'], $store->phones()),
                $store->whatsapp ? ['brand-whatsapp', 'WhatsApp', 'Chat with us', $store->whatsappUrl(), ''] : null,
                ['mail', 'Email', $store->email, 'mailto:'.$store->email, ''],
                ['clock', 'Opening hours', $store->hours, null, ''],
            ]));
        @endphp
        <div @class(['mt-8 grid gap-3 sm:grid-cols-2', 'lg:grid-cols-4' => count($channels) >= 4, 'lg:grid-cols-3' => count($channels) === 3])>
            @foreach ($channels as [$icon, $label, $value, $href, $mono])
                <a @if ($href) href="{{ $href }}" @endif @class(['group flex items-center gap-4 rounded-2xl border border-line bg-card p-5 shadow-card transition', 'hover:-translate-y-0.5 hover:border-brand-500/35 hover:shadow-lift' => $href])>
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon :name="$icon" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.6]" /></span>
                    <span class="min-w-0">
                        <span class="block text-[12px] font-medium text-muted">{{ $label }}</span>
                        <span class="block truncate text-[14.5px] font-semibold text-ink {{ $mono }}">{{ $value }}</span>
                    </span>
                </a>
            @endforeach
        </div>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
            <section class="card p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Send us a message</h2>
                <p class="mt-1 mb-6 text-sm text-muted">We usually reply within a few hours during opening times.</p>
                @include('storefront.partials.enquiry-form', ['type' => 'contact'])
            </section>

            <section class="overflow-hidden rounded-[24px] border border-line bg-card shadow-card">
                <div class="aspect-[4/3] bg-card-2">
                    <iframe src="{{ $mapUrl }}" title="Map showing the TechnoFix store" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="size-full border-0 dark:opacity-85 dark:[filter:invert(0.9)_hue-rotate(180deg)]"></iframe>
                </div>
                <div class="flex items-start gap-3 p-5">
                    <x-icon name="map-pin" class="mt-0.5 size-5 text-accent-600" />
                    <div>
                        <p class="font-semibold text-ink">Visit our store</p>
                        <p class="mt-0.5 text-sm text-muted">{{ $store->address }}</p>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($store->address) }}" target="_blank" rel="noopener" class="link mt-2 inline-block text-sm">Get directions →</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.storefront>
