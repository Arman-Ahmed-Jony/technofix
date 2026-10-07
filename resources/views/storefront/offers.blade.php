<x-layouts.storefront page-key="offers">
    <div class="container-x pt-6 lg:pt-8">
        <x-breadcrumbs :items="[['label' => 'Offers', 'url' => null]]" />

        <div class="mt-5 max-w-2xl">
            <p class="eyebrow">Offers</p>
            <h1 class="mt-2 font-display text-[30px] leading-[1.08] font-semibold tracking-[-0.03em] text-ink sm:text-[40px]">Offers &amp; deals</h1>
            <p class="mt-3 text-[15px] leading-relaxed text-muted">Sale prices are charged automatically at checkout until the timer runs out — no code needed.</p>
        </div>

        {{-- Running campaigns --}}
        @foreach ($live as $row)
            @php($campaign = $row['campaign'])
            <section class="mt-8 lg:mt-10" aria-labelledby="campaign-{{ $campaign->id }}">
                <div class="mesh-dark grain relative isolate overflow-hidden rounded-[28px] px-4 py-7 sm:px-7 lg:px-10 lg:py-9">
                    <div class="grid-lines absolute inset-0 -z-10"></div>
                    <div class="relative mb-6 flex flex-wrap items-end justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <span class="btn-primary grid size-12 place-items-center rounded-2xl"><x-icon name="bolt" class="size-6" /></span>
                            <div>
                                <p class="eyebrow text-accent-300">{{ $campaign->badge !== $campaign->name ? $campaign->badge : 'Live now' }}</p>
                                <h2 id="campaign-{{ $campaign->id }}" class="mt-1.5 font-display text-[24px] leading-tight font-semibold tracking-[-0.025em] text-white sm:text-[30px]">{{ $campaign->name }}</h2>
                                @if ($campaign->headline)
                                    <p class="mt-1 text-[14px] text-white/65">{{ $campaign->headline }}</p>
                                @endif
                            </div>
                        </div>
                        <x-countdown :ends-at="$campaign->ends_at" />
                    </div>
                    <div class="relative grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5">
                        @foreach ($row['products'] as $product)
                            <x-product.card :product="$product" />
                        @endforeach
                    </div>
                </div>
            </section>
        @endforeach

        {{-- Coming soon --}}
        @if ($upcoming->isNotEmpty())
            <section class="mt-10 lg:mt-12" aria-labelledby="coming-soon">
                <x-section-heading eyebrow="Coming soon" title="Next sales" id="coming-soon" />
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($upcoming as $campaign)
                        <div class="relative isolate overflow-hidden rounded-[24px] border border-line bg-card p-5 shadow-card">
                            <div class="absolute -top-16 -right-12 -z-10 size-44 rounded-full bg-accent-500/15 blur-3xl"></div>
                            <p class="flex items-center gap-2 text-[11.5px] font-semibold tracking-[0.12em] text-accent-700 uppercase dark:text-accent-400"><x-icon name="calendar-event" class="size-4" /> {{ $campaign->badge }}</p>
                            <p class="mt-2 font-display text-[19px] font-semibold tracking-tight text-ink">{{ $campaign->name }}</p>
                            @if ($campaign->headline)
                                <p class="mt-0.5 text-[13.5px] text-muted">{{ $campaign->headline }}</p>
                            @endif
                            <p class="mt-3 text-[12.5px] text-muted">Starts {{ $campaign->starts_at->format('D j M, g:i A') }}</p>
                            <x-countdown :ends-at="$campaign->starts_at" label="Starts in" :dark="false" size="sm" class="mt-3" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Hot deals (products marked "Hot deal") --}}
        @if ($hotDeals->isNotEmpty())
            <section class="mt-10 lg:mt-12" aria-labelledby="hot-deals">
                <x-section-heading eyebrow="Hot deals" :title="$live->isEmpty() ? 'Today’s best prices' : 'More great prices'" id="hot-deals" />
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5">
                    @foreach ($hotDeals as $product)
                        <x-product.card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($live->isEmpty() && $upcoming->isEmpty() && $hotDeals->isEmpty())
            <div class="card mx-auto mt-10 max-w-lg px-6 py-14 text-center">
                <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-card-2 ring-1 ring-line"><x-icon name="discount" class="size-8 [stroke:url(#tf-grad)] [stroke-width:1.4]" /></span>
                <p class="mt-5 font-display text-lg font-semibold text-ink">No offers running right now</p>
                <p class="mt-1 text-sm text-muted">New deals start often — check back soon, or follow us for sale alerts.</p>
                <a href="{{ route('home') }}" class="btn btn-ink mt-6">Browse products</a>
            </div>
        @endif
    </div>
</x-layouts.storefront>
