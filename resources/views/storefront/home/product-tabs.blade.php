    {{-- ─── Top picks (segmented tabs) ───────────────────────── --}}
    <section class="container-x mt-12 lg:mt-16" x-data="{ tab: @js(array_key_first($tabs)), seen: [@js(array_key_first($tabs))], show(key) { if (this.seen.includes(key)) return (this.tab = key); this.seen.push(key); this.$nextTick(() => (this.tab = key)); } }">
        <x-section-heading :eyebrow="$section->eyebrow" :title="$section->title ?: 'Top picks for you'" class="flex-wrap">
            {{-- Phones: its own centred row under the title. The padding (offset by the negative margin) keeps the
                 rounded border, the active tab's shadow and the focus outline from being clipped by the scroll box. --}}
            <div class="scrollbar-none -m-1.5 flex w-full justify-center-safe overflow-x-auto p-1.5 sm:w-auto">
                <div class="inline-flex rounded-2xl bg-card-2 p-1 ring-1 ring-line" role="tablist">
                    @foreach ($tabs as $key => $tab)
                        <button type="button" role="tab" @click="show('{{ $key }}')" :aria-selected="tab === '{{ $key }}'"
                            class="h-9 rounded-xl px-4 text-[13px] font-semibold whitespace-nowrap transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500"
                            :class="tab === '{{ $key }}' ? 'bg-card text-ink shadow-card ring-1 ring-line' : 'text-muted hover:text-ink'">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </x-section-heading>
        {{-- Only the first tab is in the page; the others are added when first opened. --}}
        @foreach ($tabs as $key => $tab)
            @unless ($loop->first)<template x-if="seen.includes('{{ $key }}')">@endunless
            <div role="tabpanel" x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-2"
                class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 xl:grid-cols-5">
                @foreach ($tab['products'] as $product)
                    <x-product.card :product="$product" />
                @endforeach
            </div>
            @unless ($loop->first)</template>@endunless
        @endforeach
    </section>

