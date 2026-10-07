    {{-- ─── Deals: the running campaign (with a countdown to its end) or products marked "Hot deal" ─── --}}
    <section id="happy-hour" class="container-x mt-12 scroll-mt-40 lg:mt-16 lg:scroll-mt-36">
        <div class="mesh-dark grain relative isolate overflow-hidden rounded-[28px] px-4 py-7 sm:px-7 lg:px-10 lg:py-10">
            <div class="grid-lines absolute inset-0 -z-10"></div>
            <div class="relative mb-6 flex flex-wrap items-end justify-between gap-5">
                <div class="flex items-center gap-4">
                    <span class="btn-primary grid size-12 place-items-center rounded-2xl"><x-icon name="flame" class="size-6" /></span>
                    <div>
                        <p class="eyebrow text-accent-300">{{ $campaign?->name ?: ($section->eyebrow ?: 'Happy Hour Deals') }}</p>
                        <h2 class="mt-1.5 font-display text-[24px] leading-tight font-semibold tracking-[-0.025em] text-white sm:text-[30px]">{{ $campaign?->headline ?: ($section->title ?: 'Today’s hottest prices') }}</h2>
                    </div>
                </div>
                @if ($endsAt)
                    <x-countdown :ends-at="$endsAt" />
                @endif
            </div>
            <x-product.scroller :products="$products" :on-dark="true" class="relative" />
        </div>
    </section>

