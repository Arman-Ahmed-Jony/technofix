@php
    $store = app(\App\Settings\StoreSettings::class);
    $group = \App\Models\Page::FOOTER_GROUPS[$page->footer_group] ?? 'TechnoFix';
@endphp

<x-layouts.storefront :title="$page->meta_title ?: $page->title" :description="$page->meta_description ?: $page->excerpt">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => $page->title, 'url' => null]]" />

        <header class="relative isolate mt-4 overflow-hidden rounded-[28px] border border-line bg-card p-7 shadow-card sm:p-10">
            <div class="absolute -top-24 -right-16 -z-10 size-80 rounded-full bg-brand-500/10 blur-3xl"></div>
            <div class="absolute -bottom-28 left-1/3 -z-10 size-72 rounded-full bg-accent-500/[0.07] blur-3xl"></div>
            <p class="eyebrow">{{ $group === 'Help' ? 'Help centre' : $group }}</p>
            <h1 class="mt-3 max-w-3xl font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[42px]">{{ $page->title }}</h1>
            @if ($page->excerpt)
                <p class="mt-3 max-w-2xl text-[15.5px] leading-relaxed text-muted">{{ $page->excerpt }}</p>
            @endif
            <p class="mt-5 flex items-center gap-1.5 text-xs text-faint"><x-icon name="calendar" class="size-3.5" /> Updated {{ $page->updated_at->format('j F Y') }}</p>
        </header>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-10">
            <aside class="order-2 lg:order-1 lg:sticky lg:top-[138px]">
                <nav class="card p-3" aria-label="Help and company pages">
                    @foreach ($siblings->groupBy('footer_group') as $key => $items)
                        <p class="px-3 pt-3 pb-1.5 text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">{{ \App\Models\Page::FOOTER_GROUPS[$key] ?? $key }}</p>
                        <ul class="space-y-0.5">
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ route('page.show', $item->slug) }}" @class([
                                        'flex items-center justify-between rounded-xl px-3 py-2 text-[13.5px] transition',
                                        'bg-card-2 font-semibold text-ink ring-1 ring-line' => $item->slug === $page->slug,
                                        'text-ink-2 hover:bg-card-2 hover:text-ink' => $item->slug !== $page->slug,
                                    ]) @if ($item->slug === $page->slug) aria-current="page" @endif>
                                        {{ $item->title }}
                                        @if ($item->slug === $page->slug) <x-icon name="chevron-right" class="size-4 text-highlight" /> @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                    <ul class="mt-2 space-y-0.5 border-t border-line pt-2">
                        <li><a href="{{ route('faq') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-[13.5px] text-ink-2 hover:bg-card-2 hover:text-ink"><x-icon name="help-circle" class="size-4 text-faint" /> FAQ</a></li>
                        <li><a href="{{ route('contact') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-[13.5px] text-ink-2 hover:bg-card-2 hover:text-ink"><x-icon name="mail" class="size-4 text-faint" /> Contact us</a></li>
                    </ul>
                </nav>
            </aside>

            <div class="order-1 min-w-0 lg:order-2">
                <article class="card px-6 py-7 sm:px-10 sm:py-10">
                    <div class="prose-tf max-w-3xl">{!! $page->html() !!}</div>
                </article>

                <div class="mt-5 flex flex-col items-start justify-between gap-4 rounded-2xl border border-line bg-card-2 p-5 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3.5">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-accent-500/10 text-accent-700 ring-1 ring-accent-500/20 dark:text-accent-400"><x-icon name="headset" class="size-5" /></span>
                        <div>
                            <p class="font-semibold text-ink">Still have a question?</p>
                            <p class="text-sm text-muted">We’re here {{ $store->hours }}.</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-ink"><x-icon name="phone-call" class="size-4" /> Call</a>
                        <a href="{{ $store->whatsappUrl() }}" class="btn btn-sm btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                        <a href="{{ route('contact') }}" class="btn btn-sm btn-outline">Message us</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
