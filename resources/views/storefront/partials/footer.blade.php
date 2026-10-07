@php
    $store = app(\App\Settings\StoreSettings::class);
    $contact = $store->toArray();
    $social = $store->socialLinks();
    $tel = $store->telHref();
    // Text, badges and the expert banner: Admin → Content → Footer. Payment badges follow the active payment methods.
    $footer = app(\App\Settings\FooterSettings::class);
    $payments = app(\App\Payments\PaymentGateways::class)->available();
    // Pages and services come from Admin → Content (cached, see App\Support\SiteNav).
    // Links to features that don't exist yet appear automatically once their route is registered.
    $nav = \App\Support\SiteNav::footer();
    $link = fn (string $route, string $label) => Route::has($route) ? ['label' => $label, 'url' => route($route)] : null;
    $columns = [
        'Shop' => array_values(array_filter([
            ...$menu->take(7)->map(fn ($item) => ['label' => $item->name, 'url' => $item->url])->all(),
            $link('brand.index', 'All brands'),
        ])),
        'Services' => array_values(array_filter([
            ...collect($nav['services'])->map(fn ($s) => ['label' => $s['name'], 'url' => route('services.show', $s['slug'])])->all(),
            $link('site-visit', 'Free site visit'),
            $link('pc-builder', 'PC Builder'),
            $link('offers', 'Offers'),
        ])),
        'Help' => array_values(array_filter([
            $link('orders.track', 'Track order'),
            app(\App\Settings\WarrantySettings::class)->lookup_enabled ? $link('warranty', 'Warranty status') : null,
            $link('quote', 'Request a quote'),
            ...collect($nav['pages']['help'] ?? [])->map(fn ($p) => ['label' => $p['title'], 'url' => route('page.show', $p['slug'])])->all(),
            $link('faq', 'FAQ'),
        ])),
        'Company' => array_values(array_filter([
            ...collect($nav['pages']['company'] ?? [])->map(fn ($p) => ['label' => $p['title'], 'url' => route('page.show', $p['slug'])])->all(),
            $link('contact', 'Contact us'),
            $link('blog', 'Blog'),
        ])),
    ];
    $siteName = app(\App\Support\Seo::class)->siteName();
    // Facebook card: pulled up into the space under Help/Company, ending 22px below their last link (a link row ≈ 30.5px).
    $spareRows = max(count($columns['Shop']), count($columns['Services'])) - max(count($columns['Help']), count($columns['Company']));
    $fbPull = (int) round(22 - max(0, $spareRows) * 30.5);
@endphp

{{-- Concierge CTA --}}
@if ($footer->cta_enabled)
<section class="container-x mt-16 lg:mt-24">
    <div class="mesh-dark grain relative isolate overflow-hidden rounded-[28px] px-6 py-10 sm:px-10 lg:flex lg:items-center lg:justify-between lg:gap-10 lg:px-14 lg:py-12">
        <div class="grid-lines absolute inset-0 -z-10"></div>
        <div class="relative max-w-xl">
            @if ($footer->cta_eyebrow)<p class="eyebrow text-accent-300">{{ $footer->cta_eyebrow }}</p>@endif
            <h2 class="mt-3 font-display text-[26px] leading-tight font-semibold tracking-[-0.025em] text-white sm:text-[34px]">{{ $footer->cta_title }} @if ($footer->cta_highlight)<span class="text-gradient">{{ $footer->cta_highlight }}</span>@endif</h2>
            @if ($footer->cta_text)<p class="mt-3 text-[15px] leading-relaxed text-white/65">{{ $footer->cta_text }}</p>@endif
        </div>
        <div class="relative mt-7 flex flex-wrap gap-3 lg:mt-0 lg:shrink-0">
            <a href="{{ $tel }}" class="btn btn-lg btn-primary"><x-icon name="phone-call" class="size-5" /> Call {{ $contact['hotline'] }}</a>
            <a href="{{ $contact['whatsapp'] ? 'https://wa.me/'.$contact['whatsapp'] : url('contact') }}" class="btn btn-lg btn-glass"><x-icon name="brand-whatsapp" class="size-5 text-whatsapp" /> WhatsApp</a>
        </div>
    </div>
</section>
@endif

<footer class="mt-10 bg-[#031019] text-[13.5px] text-white/60 lg:mt-14 dark:bg-[#02080e]">
    <div class="h-px bg-[linear-gradient(90deg,transparent,rgb(0_159_227/0.6),rgb(243_146_0/0.6),transparent)]"></div>

    @if ($footer->badges)
    <div class="border-b border-white/[0.07]">
        <div @class(['container-x grid grid-cols-2 gap-x-4 gap-y-6 py-8', 'lg:grid-cols-4' => count($footer->badges) >= 4, 'lg:grid-cols-3' => count($footer->badges) === 3])>
            @foreach ($footer->badges as $point)
                <div class="flex items-center gap-3.5">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white/[0.05] ring-1 ring-white/10"><x-icon :name="$point['icon'] ?: 'circle-check'" class="size-5 [stroke:url(#tf-grad-accent)]" /></span>
                    <span>
                        <span class="block font-semibold text-white">{{ $point['title'] }}</span>
                        <span class="block text-xs text-white/55">{{ $point['text'] ?? '' }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="container-x grid gap-12 py-14 lg:grid-cols-12">
        <div class="lg:col-span-4">
            <img src="{{ \App\Support\Brand::logoDark() }}" alt="TechnoFix" width="210" height="40" class="h-10 w-auto" loading="lazy">
            @if ($footer->about)<p class="mt-5 max-w-sm leading-relaxed">{{ $footer->about }}</p>@endif
            <ul class="mt-6 space-y-3">
                @foreach ($store->phones() as $phone)
                    <li class="flex items-center gap-2.5"><x-icon name="phone-call" class="size-4 text-accent-400" /> <a href="{{ $phone['href'] }}" class="font-mono font-medium text-white hover:text-accent-300">{{ $phone['number'] }}</a>@unless ($loop->first)<span class="text-xs text-white/55">{{ $phone['label'] }}</span>@endunless</li>
                @endforeach
                <li class="flex items-center gap-2.5"><x-icon name="mail" class="size-4 text-accent-400" /> <a href="mailto:{{ $contact['email'] }}" class="hover:text-white">{{ $contact['email'] }}</a></li>
                <li class="flex items-center gap-2.5"><x-icon name="map-pin" class="size-4 text-accent-400" /> {{ $contact['address'] }}</li>
                <li class="flex items-center gap-2.5"><x-icon name="clock" class="size-4 text-accent-400" /> {{ $contact['hours'] }}</li>
            </ul>
            @if (app(\App\Settings\DownloadSettings::class)->shows('footer'))
                <x-download-button size="sm" class="mt-6" />
            @endif
            @if ($social)
                <div class="mt-7 flex gap-2">
                    @foreach ($social as $network => $link)
                        <a href="{{ $link }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-xl bg-white/[0.05] text-white/70 ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:bg-white/10 hover:text-white" aria-label="TechnoFix on {{ ucfirst($network) }}">
                            <x-icon :name="'brand-'.$network" class="size-5" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="lg:col-span-8">
        <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
            @foreach ($columns as $heading => $links)
                <div>
                    <h3 class="font-display text-[13px] font-semibold tracking-wide text-white">{{ $heading }}</h3>
                    <ul class="mt-4 space-y-2.5">
                        @foreach ($links as $link)
                            <li><a href="{{ $link['url'] }}" class="transition hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        @if ($facebook = $footer->facebookBoxUrl())
            {{-- Facebook card (Content → Footer): uploaded cover photo as the background (else brand blue), logo + name, and a
                 link to the page. Facebook's own buttons don't work outside its full page box, so none are embedded. --}}
            {{-- lg–xl: the Help column is narrow and "Return & Refund Policy" wraps onto a second line (+20px). --}}
            <div class="mt-10 grid sm:mt-(--fb-pull) sm:grid-cols-4 lg:mt-[calc(var(--fb-pull)+20px)] xl:mt-(--fb-pull)" style="--fb-pull: {{ $fbPull }}px">
                <div class="sm:col-span-2 sm:col-start-3">
                    <a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="{{ $siteName }} on Facebook"
                        class="group relative flex h-[130px] w-full max-w-[340px] items-center overflow-hidden rounded-xl bg-[linear-gradient(125deg,#031a33,#0b4f8a_60%,#1d9be0)] shadow-[0_20px_40px_-24px_rgb(0_0_0/0.8)] ring-1 ring-white/10 transition duration-300 hover:-translate-y-0.5 hover:ring-white/25">
                        @if ($cover = $footer->facebookCoverUrl())
                            <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-105">
                            <span class="absolute inset-0 bg-[linear-gradient(90deg,rgb(3_16_25/0.9)_0%,rgb(3_16_25/0.72)_60%,rgb(3_16_25/0.2)_100%)]"></span>
                        @endif
                        <span class="relative flex w-full items-center gap-3 px-4">
                            <span class="grid size-[52px] shrink-0 place-items-center rounded-xl bg-white p-1.5 shadow-md ring-1 ring-black/10">
                                <img src="{{ \App\Support\Brand::appIcon() }}" alt="" class="max-h-full max-w-full object-contain">
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-display text-[17px] font-semibold text-white">{{ $siteName }}</span>
                                <span class="mt-0.5 flex items-center gap-1.5 text-[12.5px] text-white/80"><x-icon name="brand-facebook" class="size-3.5" /> Follow us on Facebook</span>
                            </span>
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#1877f2] text-white shadow-md ring-1 ring-white/20 transition duration-300 group-hover:translate-x-0.5">
                                <x-icon name="brand-facebook" class="size-5 [stroke-width:1.5]" />
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        @endif
        </div>
    </div>

    <div class="border-t border-white/[0.07]">
        <div class="container-x flex flex-col items-center justify-between gap-3 py-6 text-xs text-white/55 sm:flex-row">
            <p>{{ $footer->copyrightLine() }}</p>
            @if ($payments)
                <div class="flex flex-wrap items-center gap-2">
                    <span>We accept</span>
                    @foreach ($payments as $gateway)
                        <span class="chip bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-400/20"><x-icon :name="$gateway->icon()" class="size-3.5" /> {{ $gateway->label() }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</footer>
