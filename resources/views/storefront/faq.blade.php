@php
    $store = app(\App\Settings\StoreSettings::class);
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $groups->flatten()->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer],
        ])->values()->all(),
    ];
@endphp

<x-layouts.storefront page-key="faq">
    <x-slot:head>
        {{ \App\Support\Seo::jsonLd($schema) }}
    </x-slot:head>

    <div class="container-x pt-5 lg:pt-7" x-data="{ q: '' }">
        <x-breadcrumbs :items="[['label' => 'FAQ', 'url' => null]]" />

        <header class="mesh-dark grain relative isolate mt-4 overflow-hidden rounded-[28px] px-6 py-10 text-center text-white sm:py-14">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <p class="eyebrow justify-center text-accent-300">Help centre</p>
            <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] sm:text-[42px]">How can we help?</h1>
            <p class="mx-auto mt-3 max-w-lg text-[15px] text-white/65">Quick answers about orders, delivery, payment, warranty and repairs.</p>
            <label class="relative mx-auto mt-7 flex max-w-lg items-center">
                <span class="sr-only">Search questions</span>
                <x-icon name="search" class="absolute left-4 size-5 text-white/50" />
                <input type="search" x-model="q" placeholder="Search questions…" class="h-12 w-full rounded-2xl bg-white/10 pr-4 pl-12 text-[15px] text-white ring-1 ring-white/15 backdrop-blur placeholder:text-white/40 focus:ring-2 focus:ring-brand-400/60 focus:outline-none">
            </label>
        </header>

        <div class="mx-auto mt-8 max-w-3xl space-y-8">
            @forelse ($groups as $group => $faqs)
                <section x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())">
                    <h2 class="eyebrow mb-3">{{ $group }}</h2>
                    <div class="divide-y divide-line overflow-hidden rounded-[22px] border border-line bg-card shadow-card">
                        @foreach ($faqs as $faq)
                            <details class="group" x-show="!q || $el.textContent.toLowerCase().includes(q.toLowerCase())">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[15px] font-medium text-ink transition hover:bg-card-2 sm:px-6">
                                    {{ $faq->question }}
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full bg-card-2 ring-1 ring-line transition group-open:rotate-45 group-open:bg-ink group-open:text-canvas"><x-icon name="plus" class="size-3.5" /></span>
                                </summary>
                                <p class="px-5 pb-5 text-[14.5px] leading-relaxed text-muted sm:px-6">{!! nl2br(e($faq->answer)) !!}</p>
                            </details>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="card px-6 py-10 text-center text-muted">No questions yet.</p>
            @endforelse

            <div class="flex flex-col items-center gap-4 rounded-[24px] border border-line bg-card-2 p-7 text-center">
                <p class="font-display text-lg font-semibold text-ink">Didn’t find your answer?</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <a href="{{ $store->telHref() }}" class="btn btn-ink"><x-icon name="phone-call" class="size-4" /> Call {{ $store->hotline }}</a>
                    <a href="{{ $store->whatsappUrl() }}" class="btn btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                    <a href="{{ route('contact') }}" class="btn btn-outline">Send a message</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
