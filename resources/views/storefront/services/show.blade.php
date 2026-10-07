@php($store = app(\App\Settings\StoreSettings::class))

<x-layouts.storefront :title="$service->meta_title ?: $service->name.' in Bangladesh'" :description="$service->meta_description ?: $service->summary">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Repair & IT Services', 'url' => route('services.index')], ['label' => $service->name, 'url' => null]]" />

        <header class="relative isolate mt-4 overflow-hidden rounded-[28px] border border-line bg-card p-7 shadow-card sm:p-10">
            <div class="absolute -top-24 -right-16 -z-10 size-80 rounded-full bg-brand-500/10 blur-3xl"></div>
            <x-icon :name="$service->icon" class="absolute top-1/2 right-8 -z-10 hidden size-40 -translate-y-1/2 [stroke:url(#tf-grad)] [stroke-width:0.7] md:block lg:right-14" />
            <span class="grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon :name="$service->icon" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.5]" /></span>
            <p class="eyebrow mt-5">Repair & IT Services</p>
            <h1 class="mt-2.5 max-w-2xl font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[40px]">{{ $service->name }}</h1>
            <p class="mt-3 max-w-xl text-[15.5px] leading-relaxed text-muted">{{ $service->summary }}</p>
            <div class="mt-6 flex flex-wrap gap-2.5">
                <a href="{{ $store->telHref() }}" class="btn btn-primary"><x-icon name="phone-call" class="size-4" /> Call for a quote</a>
                <a href="{{ $store->whatsappUrl("Hi, I need help with: {$service->name}") }}" class="btn btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
            </div>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-10">
            <div class="min-w-0 space-y-5">
                @if ($service->content)
                    <article class="card px-6 py-7 sm:px-10 sm:py-9">
                        <div class="prose-tf max-w-3xl">{!! $service->html() !!}</div>
                    </article>
                @endif

                @if ($services->count() > 1)
                    <section>
                        <h2 class="eyebrow mb-3">Other services</h2>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($services->where('id', '!=', $service->id) as $other)
                                <a href="{{ $other->url() }}" class="group flex items-center gap-3 rounded-2xl border border-line bg-card p-4 shadow-card transition hover:border-brand-500/35">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-card-2 ring-1 ring-line"><x-icon :name="$other->icon" class="size-5 [stroke:url(#tf-grad)] [stroke-width:1.6]" /></span>
                                    <span class="flex-1 text-[14px] font-medium text-ink group-hover:text-highlight">{{ $other->name }}</span>
                                    <x-icon name="arrow-right" class="size-4 text-faint transition group-hover:translate-x-0.5" />
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="card p-6 sm:p-7 lg:sticky lg:top-[138px]" id="request">
                <h2 class="font-display text-lg font-semibold tracking-tight text-ink">Request a call back</h2>
                <p class="mt-1 mb-5 text-sm text-muted">Describe the problem — we’ll call you with a quote.</p>
                @include('storefront.partials.enquiry-form', ['type' => 'service_request', 'services' => $services, 'selected' => $service->id])
            </aside>
        </div>
    </div>
</x-layouts.storefront>
