@php($store = app(\App\Settings\StoreSettings::class))
    {{-- ─── Repair & services ────────────────────────────────── --}}
    <section class="container-x mt-12 lg:mt-16">
        <x-section-heading :eyebrow="$section->eyebrow" :title="$section->title ?: 'Repair & services'" :subtitle="$section->subtitle" />
        <div class="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
            @foreach ($services as $service)
                <a href="{{ $service->url() }}" class="group relative isolate overflow-hidden rounded-2xl border border-line bg-card p-6 shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-500/35 hover:shadow-lift">
                    <x-icon :name="$service->icon" class="absolute -right-6 -bottom-6 -z-10 size-32 text-line transition duration-500 group-hover:-rotate-6 [stroke-width:1]" />
                    <span class="grid size-12 place-items-center rounded-2xl bg-card-2 ring-1 ring-line transition group-hover:ring-brand-500/40"><x-icon :name="$service->icon" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
                    <h3 class="mt-5 text-[16px] font-semibold text-ink">{{ $service->name }}</h3>
                    <p class="mt-1.5 max-w-xs text-[13.5px] leading-relaxed text-muted">{{ $service->summary }}</p>
                    <span class="mt-4 inline-flex items-center gap-1 text-[13px] font-semibold text-highlight">Ask for a quote <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" /></span>
                </a>
            @endforeach
        </div>
        <div class="mt-4 flex flex-col items-start justify-between gap-4 rounded-2xl border border-line bg-card-2 p-5 sm:flex-row sm:items-center">
            <div class="flex items-center gap-3.5">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/20 dark:text-accent-400"><x-icon name="headset" class="size-5.5" /></span>
                <div>
                    <p class="font-semibold text-ink">Tell us what’s wrong — we’ll reply with a quote.</p>
                    <p class="text-sm text-muted">{{ $store->hours }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ $store->telHref() }}" class="btn btn-ink"><x-icon name="phone-call" class="size-4" /> Call us</a>
                <a href="{{ $store->whatsappUrl() }}" class="btn btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
            </div>
        </div>
    </section>

