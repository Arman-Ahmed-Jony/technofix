    {{-- ─── Quick categories ─────────────────────────────────── --}}
    <section class="container-x mt-12 lg:mt-16">
        <x-section-heading :eyebrow="$section->eyebrow" :title="$section->title ?: 'Shop by category'" />
        <div class="scrollbar-none -mx-4 -mt-2 flex gap-2.5 overflow-x-auto px-4 pt-2 pb-3 sm:mx-0 sm:mt-0 sm:grid sm:grid-cols-6 sm:gap-3 sm:overflow-visible sm:px-0 sm:pt-0 sm:pb-2 lg:grid-cols-8">
            @foreach ($tiles as $tile)
                <a href="{{ $tile->url }}" class="group flex w-24 shrink-0 flex-col items-center gap-2.5 rounded-2xl border border-line bg-card px-2 py-4 text-center shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-500/40 hover:shadow-lift sm:w-auto">
                    <span class="grid size-12 place-items-center rounded-2xl bg-card-2 ring-1 ring-line transition duration-300 group-hover:scale-110 group-hover:ring-brand-500/40">
                        <x-icon :name="$tile->icon" class="size-6 [stroke:url(#tf-grad)] [stroke-width:1.5]" />
                    </span>
                    <span class="line-clamp-2 text-[12px] leading-tight font-medium text-ink sm:text-[13px]">{{ \App\Support\HomePage::shortName($tile->name) }}</span>
                </a>
            @endforeach
        </div>
    </section>

