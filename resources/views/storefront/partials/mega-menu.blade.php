{{-- Desktop category bar + mega menu (hover intent, keyboard support). One shared panel; only the category column swaps. --}}
<nav class="relative hidden border-t border-line lg:block" aria-label="Categories"
    x-data="{ open: null, timer: null, show(slug) { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = slug, this.open ? 0 : 120) }, hide() { clearTimeout(this.timer); this.timer = setTimeout(() => this.open = null, 150) } }"
    @mouseleave="hide()" @keydown.escape="open = null">
    <div class="container-x">
        <ul class="-mx-2.5 flex h-12 items-center gap-0.5">
            @foreach ($menu as $item)
                <li @mouseenter="show('{{ $item->slug }}')" @focusin="show('{{ $item->slug }}')">
                    <a href="{{ $item->url }}"
                        class="flex h-9 items-center gap-1 rounded-lg px-2.5 text-[13.5px] font-medium whitespace-nowrap transition hover:bg-card-2 hover:text-ink"
                        :class="open === '{{ $item->slug }}' && 'bg-card-2 text-ink ring-1 ring-line'"
                        :aria-expanded="open === '{{ $item->slug }}'">
                        {{ $item->name }}
                        <x-icon name="chevron-down" class="hidden size-3.5 text-faint transition xl:block" ::class="open === '{{ $item->slug }}' && 'rotate-180'" />
                    </a>
                </li>
            @endforeach
            <li class="ml-auto hidden min-[1200px]:block">
                <a href="{{ route('services.index') }}" class="flex h-9 items-center gap-1.5 rounded-lg px-3 text-[13.5px] font-semibold text-accent-700 transition hover:bg-accent-500/10 dark:text-accent-400">
                    <x-icon name="tool" class="size-4" /> Repair Service
                </a>
            </li>
        </ul>
    </div>

    <div x-show="open" x-cloak @mouseenter="show(open)"
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0 -translate-y-1.5"
        x-transition:leave="transition duration-100 ease-in" x-transition:leave-end="opacity-0"
        class="absolute inset-x-0 top-full z-50 pt-2">
        <div class="container-x">
            <div class="grid grid-cols-12 gap-8 rounded-3xl border border-line bg-elevated p-7 shadow-pop">
                <div class="col-span-9">
                    @foreach ($menu as $item)
                        <div x-show="open === '{{ $item->slug }}'">
                            <div class="mb-6 flex items-center justify-between">
                                <a href="{{ $item->url }}" class="group flex items-center gap-3">
                                    <span class="grid size-11 place-items-center rounded-2xl bg-brand-500/10 text-highlight ring-1 ring-brand-500/15"><x-icon :name="$item->icon" class="size-5" /></span>
                                    <span>
                                        <span class="block font-display text-lg font-semibold tracking-tight text-ink group-hover:text-highlight">{{ $item->name }}</span>
                                        <span class="block text-xs text-muted">Genuine products · official warranty</span>
                                    </span>
                                </a>
                                <a href="{{ $item->url }}" class="btn btn-sm btn-soft">View all <x-icon name="arrow-right" class="size-3.5" /></a>
                            </div>

                            @if ($item->children->isNotEmpty())
                                <div class="columns-4 gap-8 text-[13.5px]">
                                    @foreach ($item->children as $child)
                                        <div class="mb-5 break-inside-avoid">
                                            <a href="{{ $child->url }}" class="flex items-center gap-2 font-semibold text-ink hover:text-highlight"><x-icon :name="$child->icon" class="size-4 text-faint" /> {{ $child->name }}</a>
                                            @if ($child->links->isNotEmpty())
                                                <ul class="mt-2 ml-2 space-y-1.5 border-l border-line pl-3.5 text-[13px] text-muted">
                                                    @foreach ($child->links->take(8) as $link)
                                                        <li><a href="{{ $link->url }}" class="transition hover:text-highlight">{{ $link->name }}</a></li>
                                                    @endforeach
                                                    @if ($child->links->count() > 8)
                                                        <li><a href="{{ $child->url }}" class="font-semibold text-highlight">+ {{ $child->links->count() - 8 }} more</a></li>
                                                    @endif
                                                </ul>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="mb-3 text-[11px] font-semibold tracking-[0.14em] text-faint uppercase">Shop by brand</p>
                                <div class="grid grid-cols-4 gap-3">
                                    @foreach ($item->brandLinks as $brand)
                                        <a href="{{ $brand->url }}" class="rounded-2xl border border-line bg-card-2 px-4 py-4 text-center font-display text-[15px] font-semibold tracking-tight text-ink transition hover:-translate-y-0.5 hover:border-brand-500/50 hover:text-highlight">{{ $brand->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="col-span-3">
                    <a href="{{ url('pc-builder') }}" class="mesh-dark grain group relative flex h-full min-h-60 flex-col justify-end overflow-hidden rounded-2xl p-6 text-white">
                        <div class="grid-lines absolute inset-0"></div>
                        <span class="glass absolute top-5 right-5 grid size-14 place-items-center rounded-2xl transition duration-500 group-hover:rotate-6"><x-icon name="cpu" class="size-7 text-accent-300" /></span>
                        <span class="relative chip w-fit bg-accent-500 text-white">New</span>
                        <p class="relative mt-3 font-display text-xl leading-tight font-semibold tracking-tight">Build your dream PC</p>
                        <p class="relative mt-1.5 text-sm text-white/65">Compatible parts, live total, assembled for you.</p>
                        <span class="relative mt-4 inline-flex items-center gap-1 text-sm font-semibold text-accent-300">Start building <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-0.5" /></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
