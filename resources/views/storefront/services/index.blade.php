@php($store = app(\App\Settings\StoreSettings::class))
@php($download = app(\App\Settings\DownloadSettings::class))

<x-layouts.storefront page-key="services">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Repair & IT Services', 'url' => null]]" />

        {{-- Hero --}}
        <header class="mesh-dark grain relative isolate mt-4 overflow-hidden rounded-[28px] px-6 py-10 text-white sm:px-12 sm:py-14 lg:flex lg:items-center lg:justify-between lg:gap-10">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <div class="max-w-xl">
                <p class="eyebrow text-accent-300">Repair & IT Services</p>
                <h1 class="mt-3 font-display text-[30px] leading-[1.1] font-semibold tracking-[-0.03em] sm:text-[44px]">We fix it, set it up and <span class="text-gradient">keep it running.</span></h1>
                <p class="mt-4 text-[15.5px] leading-relaxed text-white/65">Trained technicians for laptops, desktops, printers, CCTV and office networks. Tell us the problem — you get a clear quote before any work starts.</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ $store->telHref() }}" class="btn btn-lg btn-primary"><x-icon name="phone-call" class="size-5" /> Call {{ $store->hotline }}</a>
                    <a href="{{ $store->whatsappUrl('Hi, I need a repair / service quote.') }}" class="btn btn-lg btn-glass"><x-icon name="brand-whatsapp" class="size-5 text-whatsapp" /> WhatsApp</a>
                    <a href="#request" class="btn btn-lg btn-glass">Send a request</a>
                </div>
                @if ($download->shows('services'))
                    {{-- Drivers / software for customers (Admin → Content → Software download). --}}
                    <div class="mt-8 flex flex-col gap-4 border-t border-white/10 pt-7 sm:flex-row sm:items-center sm:gap-6">
                        <x-download-button class="self-start sm:self-auto" />
                        @if ($download->title || $download->text)
                            <p class="text-[13.5px] leading-relaxed text-white/65">
                                @if ($download->title)<span class="block font-semibold text-white">{{ $download->title }}</span>@endif
                                {{ $download->text }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
            <ul class="mt-9 grid grid-cols-3 gap-3 lg:mt-0 lg:w-[360px] lg:shrink-0">
                @foreach ([['receipt', 'Quote first'], ['shield-check', 'Genuine parts'], ['clock', 'Quick turnaround']] as [$icon, $label])
                    <li class="glass flex flex-col items-center gap-2 rounded-2xl px-2 py-5 text-center text-[12.5px] font-medium">
                        <x-icon :name="$icon" class="size-7 [stroke:url(#tf-grad-accent)] [stroke-width:1.5]" /> {{ $label }}
                    </li>
                @endforeach
            </ul>
        </header>

        {{-- Services --}}
        <section class="mt-12">
            <x-section-heading eyebrow="What we do" title="Our services" subtitle="No fixed price list — every job is different. Ask us and we’ll quote before starting." />
            <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                @forelse ($services as $service)
                    <a href="{{ $service->url() }}" id="{{ $service->slug }}" class="group relative isolate scroll-mt-40 overflow-hidden rounded-2xl border border-line bg-card p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-500/35 hover:shadow-lift">
                        <x-icon :name="$service->icon" class="absolute -right-6 -bottom-6 -z-10 size-32 text-line transition duration-500 group-hover:-rotate-6 [stroke-width:1]" />
                        <span class="grid size-12 place-items-center rounded-2xl bg-card-2 ring-1 ring-line transition group-hover:ring-brand-500/40"><x-icon :name="$service->icon" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                        <h2 class="mt-5 text-[16px] font-semibold text-ink">{{ $service->name }}</h2>
                        <p class="mt-1.5 max-w-xs text-[13.5px] leading-relaxed text-muted">{{ $service->summary }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-[13px] font-semibold text-highlight">Details &amp; quote <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" /></span>
                    </a>
                @empty
                    <p class="card px-6 py-10 text-center text-muted sm:col-span-2 lg:col-span-3">Services will be listed here soon. Call us in the meantime.</p>
                @endforelse
            </div>
        </section>

        {{-- How it works + request form --}}
        <section id="request" class="mt-12 scroll-mt-40 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)] lg:gap-10">
            <div>
                <x-section-heading eyebrow="How it works" title="Simple, transparent, no surprises" />
                <ol class="space-y-3">
                    @foreach ([
                        ['message-circle', 'Tell us the problem', 'Call, WhatsApp or send the form — photos help.'],
                        ['receipt', 'Get a clear quote', 'We diagnose first. No work starts without your OK.'],
                        ['tool', 'We fix it properly', 'Genuine parts, tested before we hand it back.'],
                        ['truck-delivery', 'Pick up or delivery', 'Collect from our store or get it delivered.'],
                    ] as [$icon, $title, $text])
                        <li class="flex gap-4 rounded-2xl border border-line bg-card p-4 shadow-card">
                            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon :name="$icon" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.7]" /></span>
                            <span>
                                <span class="flex items-center gap-2 text-[15px] font-semibold text-ink"><span class="font-mono text-xs text-faint">0{{ $loop->iteration }}</span> {{ $title }}</span>
                                <span class="mt-0.5 block text-[13.5px] text-muted">{{ $text }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
                @if (Route::has('site-visit'))
                    <a href="{{ route('site-visit') }}" class="group mt-6 flex items-center gap-4 rounded-2xl border border-line bg-card p-4 shadow-card transition hover:-translate-y-0.5 hover:border-brand-500/35 hover:shadow-lift">
                        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/20 dark:text-accent-400"><x-icon name="calendar-check" class="size-5" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[14.5px] font-semibold text-ink">Installing CCTV or a network?</span>
                            <span class="block text-[13px] text-muted">Book a free site visit — we survey the place and quote.</span>
                        </span>
                        <x-icon name="arrow-right" class="size-4 text-faint transition group-hover:translate-x-0.5 group-hover:text-ink" />
                    </a>
                @endif
            </div>
            <div class="card p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Request a call back</h2>
                <p class="mt-1 mb-6 text-sm text-muted">Tell us what you need and we’ll call you with a quote.</p>
                @include('storefront.partials.enquiry-form', ['type' => 'service_request', 'services' => $services])
            </div>
        </section>

        @if ($faqs->isNotEmpty())
            <section class="mx-auto mt-14 max-w-3xl">
                <x-section-heading eyebrow="Good to know" title="Repair questions" :href="route('faq')" link-label="All FAQs" />
                <div class="divide-y divide-line overflow-hidden rounded-[22px] border border-line bg-card shadow-card">
                    @foreach ($faqs as $faq)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-4 text-[15px] font-medium text-ink hover:bg-card-2">
                                {{ $faq->question }}
                                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-card-2 ring-1 ring-line transition group-open:rotate-45"><x-icon name="plus" class="size-3.5" /></span>
                            </summary>
                            <p class="px-6 pb-5 text-[14.5px] leading-relaxed text-muted">{{ $faq->answer }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.storefront>
