@php
    $store = app(\App\Settings\StoreSettings::class);
    $bySection = collect($slots)->groupBy('section');
@endphp

<x-layouts.storefront :title="$settings->title.' — PC Builder'" :description="$settings->intro">
    {{-- Slots, rules and text: Admin → Catalog → PC Builder / PC Builder rules. Prices and checks come from the server. --}}
    <div class="container-x pt-6 pb-24 lg:pt-8 lg:pb-16"
        x-data="pcBuilder({ slots: @js($slots), initial: @js($initial), customer: @js($customer), csrf: @js(csrf_token()), urls: @js([
            'state' => route('pc-builder.state'), 'options' => route('pc-builder.options', ['slot' => '__SLOT__']), 'quote' => route('pc-builder.quote'),
            'save' => route('pc-builder.save'), 'enquiry' => route('pc-builder.enquiry'),
        ]) })"
        @keydown.escape.window="picker.open && closePicker()">

        <x-breadcrumbs :items="[['label' => 'PC Builder', 'url' => null]]" />

        <div class="mt-5 flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <p class="eyebrow">PC Builder</p>
                <h1 class="mt-2 font-display text-[30px] leading-[1.08] font-semibold tracking-[-0.03em] text-ink sm:text-[40px]">{{ $settings->title }}</h1>
                @if (filled($settings->intro))
                    <p class="mt-3 text-[15px] leading-relaxed text-muted">{{ $settings->intro }}</p>
                @endif
            </div>
            <button type="button" class="btn btn-sm btn-soft" x-show="state.count > 0" x-cloak @click="reset()">
                <x-icon name="refresh" class="size-4" /> Start over
            </button>
        </div>

        <div class="mt-7 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-8">

            {{-- ─── Slots ─── --}}
            <div class="min-w-0 space-y-7">
                @foreach ($sections as $sectionKey => $sectionLabel)
                    @continue(! $bySection->has($sectionKey))
                    <section aria-labelledby="section-{{ $sectionKey }}">
                        <h2 id="section-{{ $sectionKey }}" class="mb-3 flex items-end justify-between gap-3 px-1">
                            <span class="font-display text-[18px] font-semibold tracking-tight text-ink">{{ $sectionLabel }}</span>
                            @if ($sectionKey === 'core')
                                <span class="pb-0.5 font-mono text-[12px] text-muted" x-cloak x-show="requiredCount">
                                    <span x-text="requiredDone"></span>/<span x-text="requiredCount"></span> required parts
                                </span>
                            @endif
                        </h2>

                        <ul class="divide-y divide-line overflow-hidden rounded-[24px] border border-line bg-card shadow-card">
                            @foreach ($bySection[$sectionKey] as $slot)
                                @php $id = $slot['id']; @endphp
                                <li id="slot-{{ $id }}" class="relative p-4 transition-colors sm:px-5"
                                    :class="{ 'bg-rose-500/[0.04]': level({{ $id }}) === 'error', 'bg-amber-500/[0.04]': level({{ $id }}) === 'warning' }">
                                    <div class="flex items-center gap-3.5 sm:gap-4">
                                        {{-- Picture: the part, or the slot icon --}}
                                        <div class="relative size-14 shrink-0 sm:size-16">
                                            <template x-if="item({{ $id }})">
                                                <a :href="item({{ $id }}).url" target="_blank" class="block size-full overflow-hidden rounded-2xl ring-1 ring-line"
                                                    :class="item({{ $id }}).image ? 'bg-white' : 'grid place-items-center bg-card-2'">
                                                    <template x-if="item({{ $id }}).image"><img :src="item({{ $id }}).image" :alt="item({{ $id }}).name" class="size-full object-contain p-1.5" loading="lazy"></template>
                                                    <template x-if="! item({{ $id }}).image"><x-icon-bound name="item({{ $id }}).icon" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.4]" /></template>
                                                </a>
                                            </template>
                                            <span x-show="! item({{ $id }})" class="grid size-full place-items-center rounded-2xl bg-card-2 ring-1 ring-line">
                                                <x-icon :name="$slot['icon']" class="size-7 text-faint [stroke-width:1.5]" />
                                            </span>
                                            <span x-cloak x-show="item({{ $id }}) && ! level({{ $id }})" class="absolute -right-1 -bottom-1 grid size-5 place-items-center rounded-full bg-emerald-500 text-white ring-2 ring-card"><x-icon name="check" class="size-3 [stroke-width:3]" /></span>
                                            <span x-cloak x-show="level({{ $id }}) === 'error'" class="absolute -right-1 -bottom-1 grid size-5 place-items-center rounded-full bg-rose-500 text-white ring-2 ring-card"><x-icon name="x" class="size-3 [stroke-width:3]" /></span>
                                            <span x-cloak x-show="level({{ $id }}) === 'warning'" class="absolute -right-1 -bottom-1 grid size-5 place-items-center rounded-full bg-amber-500 text-white ring-2 ring-card"><x-icon name="exclamation-mark" class="size-3 [stroke-width:3]" /></span>
                                        </div>

                                        {{-- Name + the picked part --}}
                                        <div class="min-w-0 flex-1">
                                            <p class="flex flex-wrap items-center gap-x-2 gap-y-0.5"
                                                :class="item({{ $id }}) ? 'text-[11.5px] font-bold tracking-[0.08em] text-highlight uppercase' : 'text-[15px] font-semibold text-ink'">
                                                {{ $slot['name'] }}
                                                @if ($slot['required'])
                                                    <span class="rounded-full bg-accent-500/10 px-1.5 py-px text-[10px] font-semibold tracking-wider text-accent-700 uppercase ring-1 ring-accent-500/20 dark:text-accent-400" x-show="! item({{ $id }})">Required</span>
                                                @endif
                                            </p>
                                            <template x-if="item({{ $id }})">
                                                <div>
                                                    <a :href="item({{ $id }}).url" target="_blank" class="mt-0.5 line-clamp-2 text-[14px] leading-snug font-medium text-ink transition hover:text-highlight" x-text="item({{ $id }}).name"></a>
                                                    <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                                        <template x-for="spec in item({{ $id }}).specs" :key="spec"><span class="spec-chip" x-text="spec"></span></template>
                                                        <span class="ml-1 flex items-center gap-1.5 text-[11.5px] font-medium text-muted"><span class="size-1.5 rounded-full" :class="item({{ $id }}).stock.dot"></span><span x-text="item({{ $id }}).stock.label"></span></span>
                                                    </div>
                                                </div>
                                            </template>
                                            <p x-show="! item({{ $id }})" class="mt-0.5 text-[14px] text-muted">{{ $slot['description'] ?: ($slot['required'] ? 'Needed for a complete PC' : 'Optional') }}</p>
                                        </div>

                                        {{-- Price + actions (desktop) --}}
                                        <div class="hidden shrink-0 items-center gap-3 sm:flex">
                                            <template x-if="item({{ $id }})">
                                                <div class="flex items-center gap-3">
                                                    @if ($slot['max'] > 1)
                                                        <div class="flex h-9 items-center rounded-xl ring-1 ring-line" role="group" aria-label="Quantity">
                                                            <button type="button" class="grid h-full w-8 place-items-center text-muted hover:text-ink disabled:opacity-40" @click="setQty({{ $id }}, item({{ $id }}).qty - 1)" :disabled="item({{ $id }}).qty <= 1" aria-label="Fewer"><x-icon name="minus" class="size-3.5" /></button>
                                                            <span class="w-6 text-center font-mono text-[13px] text-ink" x-text="item({{ $id }}).qty"></span>
                                                            <button type="button" class="grid h-full w-8 place-items-center text-muted hover:text-ink disabled:opacity-40" @click="setQty({{ $id }}, item({{ $id }}).qty + 1)" :disabled="item({{ $id }}).qty >= {{ $slot['max'] }}" aria-label="More"><x-icon name="plus" class="size-3.5" /></button>
                                                        </div>
                                                    @endif
                                                    <div class="w-[92px] text-right">
                                                        <p class="price font-display text-[18px] leading-tight" x-text="money(item({{ $id }}).price * item({{ $id }}).qty)"></p>
                                                        <p class="font-mono text-[11px] text-faint" x-show="item({{ $id }}).qty > 1" x-text="item({{ $id }}).qty + ' × ' + money(item({{ $id }}).price)"></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <button type="button" x-show="! item({{ $id }})" @click="openPicker({{ $id }})"
                                                class="btn btn-sm {{ $slot['required'] ? 'btn-primary' : 'btn-soft' }}">
                                                <x-icon name="plus" class="size-4" /> <span>Choose</span>
                                            </button>
                                            <template x-if="item({{ $id }})">
                                                <div class="flex items-center gap-1">
                                                    <button type="button" class="icon-btn" @click="openPicker({{ $id }})" aria-label="Change {{ strtolower($slot['name']) }}" title="Change"><x-icon name="replace" class="size-[18px]" /></button>
                                                    <button type="button" class="icon-btn hover:text-rose-600" @click="remove({{ $id }})" aria-label="Remove {{ strtolower($slot['name']) }}" title="Remove"><x-icon name="trash" class="size-[18px]" /></button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Price + quantity (mobile) --}}
                                    <template x-if="item({{ $id }})">
                                        <div class="mt-3 flex items-center justify-between pl-[70px] sm:hidden">
                                            @if ($slot['max'] > 1)
                                                <div class="flex h-8 items-center rounded-lg ring-1 ring-line" role="group" aria-label="Quantity">
                                                    <button type="button" class="grid h-full w-8 place-items-center text-muted disabled:opacity-40" @click="setQty({{ $id }}, item({{ $id }}).qty - 1)" :disabled="item({{ $id }}).qty <= 1" aria-label="Fewer"><x-icon name="minus" class="size-3.5" /></button>
                                                    <span class="w-6 text-center font-mono text-[13px] text-ink" x-text="item({{ $id }}).qty"></span>
                                                    <button type="button" class="grid h-full w-8 place-items-center text-muted disabled:opacity-40" @click="setQty({{ $id }}, item({{ $id }}).qty + 1)" :disabled="item({{ $id }}).qty >= {{ $slot['max'] }}" aria-label="More"><x-icon name="plus" class="size-3.5" /></button>
                                                </div>
                                            @else
                                                <span></span>
                                            @endif
                                            <p class="price font-display text-[18px] leading-tight" x-text="money(item({{ $id }}).price * item({{ $id }}).qty)"></p>
                                        </div>
                                    </template>

                                    {{-- Problems with this part --}}
                                    <template x-for="issue in issuesFor({{ $id }})" :key="issue.message">
                                        <p class="mt-3 flex items-start gap-2 rounded-xl px-3 py-2 text-[12.5px] leading-snug sm:ml-[80px]"
                                            :class="issue.level === 'error' ? 'bg-rose-500/10 text-rose-700 dark:text-rose-300' : 'bg-amber-500/10 text-amber-800 dark:text-amber-300'">
                                            <x-icon name="alert-triangle" class="mt-px size-4 shrink-0" /> <span x-text="issue.message"></span>
                                        </p>
                                    </template>
                                    <p x-cloak x-show="item({{ $id }}) && ! item({{ $id }}).available" class="mt-3 flex items-start gap-2 rounded-xl bg-card-2 px-3 py-2 text-[12.5px] text-muted sm:ml-[80px]">
                                        <x-icon name="info-circle" class="mt-px size-4 shrink-0" /> Not available to order right now — choose another, or send us your build and we’ll suggest one.
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>

            {{-- ─── Summary ─── --}}
            <aside id="build-summary" class="lg:sticky lg:top-[138px]" aria-label="Build summary">
                <div class="overflow-hidden rounded-[24px] border border-line bg-card shadow-card">
                    <div class="relative isolate p-5 sm:p-6">
                        <div class="absolute -top-20 -right-16 -z-10 size-56 rounded-full bg-brand-500/10 blur-3xl"></div>
                        <p class="text-[12px] font-semibold tracking-[0.12em] text-muted uppercase">Your build</p>
                        <p class="mt-2 flex items-end justify-between gap-3">
                            <span class="price font-display text-[32px] leading-none" x-text="money(state.subtotal)">৳0</span>
                            <span class="pb-1 text-[13px] text-muted"><span class="font-mono text-ink" x-text="state.count">0</span> <span x-text="state.count === 1 ? 'part' : 'parts'">parts</span></span>
                        </p>

                        {{-- Compatibility --}}
                        <div class="mt-5">
                            <div x-show="state.count === 0" class="flex items-start gap-3 rounded-2xl bg-card-2 p-3.5 text-[13px] text-muted ring-1 ring-line">
                                <x-icon name="shield-check" class="mt-px size-5 shrink-0 text-faint" /> Pick parts and we’ll check they work together.
                            </div>
                            <div x-cloak x-show="state.count > 0 && ! state.issues.length" class="flex items-start gap-3 rounded-2xl bg-emerald-500/10 p-3.5 text-[13px] text-emerald-800 ring-1 ring-emerald-500/20 dark:text-emerald-300">
                                <x-icon name="circle-check" class="mt-px size-5 shrink-0" />
                                <span><span class="font-semibold">No compatibility problems found</span> <span class="block text-emerald-700/80 dark:text-emerald-300/80">with the parts chosen so far.</span></span>
                            </div>
                            <div x-cloak x-show="errors.length" class="rounded-2xl bg-rose-500/10 p-3.5 text-[13px] text-rose-800 ring-1 ring-rose-500/20 dark:text-rose-300">
                                <p class="flex items-center gap-2 font-semibold"><x-icon name="alert-triangle" class="size-5" /> <span x-text="errors.length === 1 ? '1 compatibility problem' : errors.length + ' compatibility problems'"></span></p>
                                <ul class="mt-2 space-y-1.5 pl-7">
                                    <template x-for="issue in errors" :key="issue.message"><li x-text="issue.message"></li></template>
                                </ul>
                            </div>
                            <div x-cloak x-show="warnings.length" class="mt-2 rounded-2xl bg-amber-500/10 p-3.5 text-[13px] text-amber-900 ring-1 ring-amber-500/20 dark:text-amber-200">
                                <template x-for="issue in warnings" :key="issue.message"><p class="flex items-start gap-2"><x-icon name="bolt" class="mt-px size-4 shrink-0" /> <span x-text="issue.message"></span></p></template>
                            </div>
                        </div>

                        {{-- Power --}}
                        <div class="mt-4 rounded-2xl p-3.5 ring-1 ring-line" x-cloak x-show="state.power.known">
                            <div class="flex items-center justify-between text-[13px]">
                                <span class="flex items-center gap-2 font-medium text-ink"><x-icon name="bolt" class="size-4 text-accent-500" /> Estimated power</span>
                                <span class="font-mono text-ink"><span x-text="state.power.estimate"></span> W</span>
                            </div>
                            <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-card-2 ring-1 ring-line">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="powerPercent >= 100 ? 'bg-rose-500' : (state.power.psu && state.power.psu < state.power.recommended ? 'bg-amber-500' : 'bg-gradient-to-r from-brand-500 to-emerald-500')"
                                    :style="`width: ${powerPercent}%`"></div>
                            </div>
                            <p class="mt-2 text-[12px] text-muted">
                                Recommended power supply: <span class="font-mono whitespace-nowrap text-ink" x-text="state.power.recommended + ' W'"></span>
                                <template x-if="state.power.psu"><span class="whitespace-nowrap"> · yours: <span class="font-mono text-ink" x-text="state.power.psu + ' W'"></span></span></template>
                            </p>
                        </div>

                        {{-- Still to choose --}}
                        <div class="mt-4" x-cloak x-show="state.missing.length && state.count">
                            <p class="text-[12.5px] text-muted">Still needed:</p>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="slotId in state.missing" :key="slotId">
                                    <button type="button" class="chip gap-1 bg-accent-500/10 py-1 pr-2.5 pl-2 text-[12px] text-accent-700 ring-1 ring-accent-500/20 transition hover:bg-accent-500/15 dark:text-accent-400" @click="openPicker(slotId)">
                                        <x-icon name="plus" class="size-3.5" /> <span x-text="slotName(slotId)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <button type="button" class="btn btn-lg btn-primary mt-6 w-full" @click="addAllToCart()" :disabled="! state.count || busy">
                            <x-icon name="shopping-bag-plus" class="size-5" /> Add all to cart
                        </button>
                        <div class="mt-2.5 grid grid-cols-3 gap-2">
                            <button type="button" class="btn btn-sm btn-soft h-auto flex-col gap-1 py-2.5" @click="share()" :disabled="! state.count"><x-icon name="share" class="size-[18px]" /> Share</button>
                            <button type="button" class="btn btn-sm btn-soft h-auto flex-col gap-1 py-2.5" @click="openDialog('save')" :disabled="! state.count"><x-icon name="bookmark" class="size-[18px]" /> Save</button>
                            <a :href="state.count ? quoteUrl : null" download class="btn btn-sm btn-soft h-auto flex-col gap-1 px-1.5 py-2.5 whitespace-nowrap" :class="! state.count && 'pointer-events-none opacity-50'" :aria-disabled="(! state.count).toString()"
                                @click="state.count && $store.toast.show('Preparing your PDF quotation…', 'info')" title="Download a PDF quotation of this build"><x-icon name="file-download" class="size-[18px]" /> Download PDF</a>
                        </div>
                        <button type="button" class="btn btn-outline mt-2 w-full" @click="openDialog('enquiry')" :disabled="! state.count">
                            <x-icon name="headset" class="size-[18px]" /> Send to our team for advice
                        </button>
                        <p class="mt-2.5 text-center text-[12px] text-faint">Prices and compatibility are checked by our system.</p>
                    </div>

                    <div class="border-t border-line bg-card-2/60 p-5 sm:px-6">
                        <p class="text-[13px] font-medium text-ink">Not sure what to pick?</p>
                        <p class="mt-0.5 text-[12.5px] text-muted">Our technicians will suggest parts for your budget — and assemble and test the PC for you.</p>
                        <div class="mt-3 flex gap-2">
                            <a href="{{ $store->telHref() }}" class="btn btn-sm btn-outline flex-1"><x-icon name="phone" class="size-4" /> Call</a>
                            <a href="{{ $store->whatsappUrl('Hi TechnoFix, I need help building a PC.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline flex-1"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        {{-- Mobile: total + add to cart --}}
        <div class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-30 border-t border-line bg-canvas/85 px-4 py-2.5 backdrop-blur-xl lg:hidden">
            <div class="flex items-center gap-3">
                <a href="#build-summary" class="min-w-0 flex-1">
                    <span class="block text-[11.5px] text-muted"><span x-text="state.count">0</span> parts ·
                        <span x-show="errors.length" class="font-medium text-rose-600" x-text="errors.length + ' problem' + (errors.length > 1 ? 's' : '')"></span>
                        <span x-show="! errors.length && state.count" class="font-medium text-emerald-600">compatible</span>
                    </span>
                    <span class="price block font-display text-[20px] leading-tight" x-text="money(state.subtotal)">৳0</span>
                </a>
                <button type="button" class="btn btn-primary shrink-0" @click="addAllToCart()" :disabled="! state.count || busy">
                    <x-icon name="shopping-bag-plus" class="size-4" /> Add to cart
                </button>
            </div>
        </div>

        {{-- ─── Picker: full-screen sheet on mobile, side panel on desktop ─── --}}
        <div x-show="picker.open" x-cloak class="fixed inset-0 z-50" role="dialog" aria-modal="true" :aria-label="'Choose ' + (picker.slot?.name ?? '')">
            <div class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" x-show="picker.open" x-transition.opacity @click="closePicker()"></div>
            <div class="absolute inset-y-0 right-0 flex w-full flex-col bg-canvas shadow-2xl sm:w-[min(760px,94vw)] sm:rounded-l-[28px] sm:border-l sm:border-line"
                x-show="picker.open" x-trap.noscroll="picker.open"
                x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                <div class="border-b border-line px-4 pt-4 pb-3 sm:px-6 sm:pt-5">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-card-2 ring-1 ring-line"><x-icon-bound name="picker.slot?.icon ?? 'package'" class="size-5 text-highlight" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-lg leading-tight font-semibold text-ink" x-text="picker.slot?.name ?? ''"></p>
                            <p class="text-[12.5px] text-muted">Choose <span x-text="(picker.slot?.max ?? 1) > 1 ? 'one (set the quantity after)' : 'one'"></span> · <span x-text="picker.total"></span> <span x-text="picker.total === 1 ? 'product' : 'products'"></span></p>
                        </div>
                        <button type="button" class="icon-btn" @click="closePicker()" aria-label="Close"><x-icon name="x" class="size-5" /></button>
                    </div>

                    <div class="mt-3.5 flex gap-2">
                        <label class="relative flex-1">
                            <span class="sr-only">Search</span>
                            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-faint" />
                            <input type="search" x-ref="pickerSearch" x-model="picker.q" @input="refilter()" placeholder="Search by name or model" class="input h-10 pl-9 text-[13.5px]" autocomplete="off">
                        </label>
                        <select x-model="picker.sort" @change="fetchOptions()" class="input h-10 w-auto cursor-pointer py-0 pr-8 pl-3 text-[13px] font-medium" aria-label="Sort">
                            <option value="fit">Best match</option>
                            <option value="price_asc">Price: low to high</option>
                            <option value="price_desc">Price: high to low</option>
                        </select>
                    </div>

                    <div class="scrollbar-none -mx-4 mt-2 flex gap-1.5 overflow-x-auto px-4 py-1 sm:-mx-6 sm:flex-wrap sm:overflow-visible sm:px-6">
                        <button type="button" @click="picker.compatible = ! picker.compatible; fetchOptions()" :aria-pressed="picker.compatible.toString()"
                            class="chip shrink-0 gap-1.5 py-1.5 pr-3 pl-2.5 text-[12.5px] ring-1 transition"
                            :class="picker.compatible ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/30 dark:text-emerald-300' : 'bg-card text-muted ring-line hover:text-ink'">
                            <x-icon name="shield-check" class="size-3.5" /> Compatible only
                        </button>
                        <button type="button" @click="picker.available = ! picker.available; fetchOptions()" :aria-pressed="picker.available.toString()"
                            class="chip shrink-0 gap-1.5 py-1.5 pr-3 pl-2.5 text-[12.5px] ring-1 transition"
                            :class="picker.available ? 'bg-brand-500/10 text-highlight ring-brand-500/30' : 'bg-card text-muted ring-line hover:text-ink'">
                            <x-icon name="package" class="size-3.5" /> In stock
                        </button>
                        <template x-for="brand in picker.brands" :key="brand.id">
                            <button type="button" @click="toggleBrand(brand.id)" :aria-pressed="picker.brand.includes(brand.id).toString()"
                                class="chip shrink-0 gap-1 py-1.5 px-3 text-[12.5px] ring-1 transition"
                                :class="picker.brand.includes(brand.id) ? 'bg-ink text-canvas ring-ink' : 'bg-card text-muted ring-line hover:text-ink'">
                                <span x-text="brand.name"></span> <span class="font-mono text-[11px] opacity-60" x-text="brand.count"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
                    {{-- Loading --}}
                    <div x-show="picker.loading && ! picker.products.length" class="space-y-2.5" aria-hidden="true">
                        <template x-for="n in 5" :key="n"><div class="h-[104px] animate-pulse rounded-2xl bg-card-2"></div></template>
                    </div>

                    <p x-show="! picker.loading && ! picker.products.length" class="py-16 text-center text-sm text-muted">No products match. Try another search or clear the filters.</p>

                    <ul class="space-y-2.5" :class="picker.loading && 'opacity-60 transition-opacity'">
                        <template x-for="product in picker.products" :key="product.id">
                            <li class="flex gap-3.5 rounded-2xl border p-3 transition sm:p-3.5"
                                :class="{
                                    'border-brand-500 ring-4 ring-brand-500/10 bg-card': isCurrent(product),
                                    'border-rose-500/25 bg-rose-500/[0.03]': ! isCurrent(product) && product.fit.status === 'conflict',
                                    'border-line bg-card hover:border-line-strong': ! isCurrent(product) && product.fit.status !== 'conflict',
                                }">
                                <a :href="product.url" target="_blank" class="size-[72px] shrink-0 overflow-hidden rounded-xl ring-1 ring-line sm:size-20"
                                    :class="product.image ? 'bg-white' : 'grid place-items-center bg-card-2'">
                                    <template x-if="product.image"><img :src="product.image" :alt="product.name" class="size-full object-contain p-1.5" loading="lazy"></template>
                                    <template x-if="! product.image"><x-icon-bound name="product.icon" class="size-8 [stroke:url(#tf-grad)] [stroke-width:1.3]" /></template>
                                </a>
                                <div class="min-w-0 flex-1">
                                    <p class="flex items-center gap-2 text-[10.5px] font-semibold tracking-[0.12em] text-muted uppercase">
                                        <span class="truncate" x-text="product.brand"></span>
                                        <span class="flex shrink-0 items-center gap-1 text-[11px] font-medium tracking-normal text-muted normal-case"><span class="size-1.5 rounded-full" :class="product.stock.dot"></span><span x-text="product.stock.label"></span></span>
                                    </p>
                                    <a :href="product.url" target="_blank" class="mt-0.5 line-clamp-2 text-[13.5px] leading-snug font-medium text-ink hover:text-highlight" x-text="product.name"></a>
                                    <div class="mt-1.5 hidden flex-wrap gap-1 sm:flex">
                                        <template x-for="spec in product.specs" :key="spec"><span class="spec-chip" x-text="spec"></span></template>
                                    </div>
                                    <p x-show="product.fit.status === 'ok'" class="mt-1.5 flex items-center gap-1 text-[12px] font-medium text-emerald-700 dark:text-emerald-400"><x-icon name="circle-check" class="size-3.5" /> Compatible with your build</p>
                                    <template x-for="reason in product.fit.reasons" :key="reason">
                                        <p class="mt-1.5 flex items-start gap-1 text-[12px] leading-snug" :class="product.fit.status === 'conflict' ? 'text-rose-700 dark:text-rose-300' : 'text-amber-800 dark:text-amber-300'">
                                            <x-icon name="alert-triangle" class="mt-px size-3.5 shrink-0" /> <span x-text="reason"></span>
                                        </p>
                                    </template>
                                </div>
                                <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                                    <div class="text-right">
                                        <p class="price font-display text-[17px] leading-tight" x-text="product.price !== null ? money(product.price) : 'Call'"></p>
                                        <p class="font-mono text-[11.5px] text-faint line-through" x-show="product.regular_price" x-text="money(product.regular_price)"></p>
                                    </div>
                                    <button type="button" x-show="! isCurrent(product)" @click="choose(product)" :disabled="! product.available"
                                        class="btn btn-sm" :class="product.fit.status === 'conflict' ? 'btn-soft' : 'btn-ink'">
                                        <span x-text="product.available ? 'Add' : 'Unavailable'"></span>
                                    </button>
                                    <span x-show="isCurrent(product)" class="chip gap-1 bg-brand-500/10 py-1 px-2.5 text-[12px] text-highlight ring-1 ring-brand-500/25"><x-icon name="check" class="size-3.5" /> Selected</span>
                                </div>
                            </li>
                        </template>
                    </ul>

                    <div class="mt-4 text-center" x-show="picker.page < picker.pages">
                        <button type="button" class="btn btn-sm btn-soft" @click="fetchOptions(true)" :disabled="picker.loading">Show more</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Save / sign in / send to our team ─── --}}
        <div x-show="dialog" x-cloak class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-navy-950/60 backdrop-blur-sm" x-show="dialog" x-transition.opacity @click="dialog = null"></div>
            <div class="relative w-full max-w-md rounded-t-[28px] border border-line bg-canvas p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl sm:rounded-[28px] sm:p-6"
                x-show="dialog" x-trap.noscroll="dialog" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-6 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                @keydown.escape.stop="dialog = null">
                <button type="button" class="icon-btn absolute top-3.5 right-3.5" @click="dialog = null" aria-label="Close"><x-icon name="x" class="size-5" /></button>

                {{-- Save --}}
                <form x-show="dialog === 'save'" @submit.prevent="saveBuild()" novalidate>
                    <p class="font-display text-lg font-semibold text-ink">Save this build</p>
                    <p class="mt-1 pr-8 text-[13px] text-muted">It goes to <span class="font-medium text-ink">My account → Saved PC builds</span>. Prices update to today’s when you open it.</p>
                    <label for="build-name" class="mt-4 mb-1.5 block text-[13px] font-medium text-ink">Name</label>
                    <input id="build-name" type="text" x-model="buildName" maxlength="80" class="input" :class="formErrors.name && 'border-rose-500'">
                    <p class="mt-1 text-[12.5px] text-rose-600" x-show="formErrors.name || formErrors.form" x-text="formErrors.name || formErrors.form"></p>
                    <button type="submit" class="btn btn-primary mt-5 w-full" :disabled="sending"><span x-text="sending ? 'Saving…' : 'Save build'"></span></button>
                </form>

                {{-- Guests: sign in to save --}}
                <div x-show="dialog === 'signin'">
                    <span class="grid size-12 place-items-center rounded-2xl bg-brand-500/10 ring-1 ring-brand-500/20"><x-icon name="bookmark" class="size-6 text-highlight" /></span>
                    <p class="mt-4 font-display text-lg font-semibold text-ink">Sign in to save builds</p>
                    <p class="mt-1 text-[13px] text-muted">Your build stays here in this browser while you sign in — then tap Save again.</p>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <a href="{{ route('account.login', ['to' => 'pc-builder']) }}" class="btn btn-ink">Sign in</a>
                        <a href="{{ route('account.register', ['to' => 'pc-builder']) }}" class="btn btn-soft">Create account</a>
                    </div>
                    <button type="button" class="btn btn-sm mt-3 w-full text-muted hover:text-ink" @click="share(); dialog = null"><x-icon name="link" class="size-4" /> Or copy a link to this build</button>
                </div>

                {{-- Send to our team (creates a lead in Admin → Leads) --}}
                <div x-show="dialog === 'enquiry'">
                    <template x-if="sent">
                        <div class="py-4 text-center">
                            <span class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-500/10 ring-1 ring-emerald-500/25"><x-icon name="circle-check" class="size-7 text-emerald-600" /></span>
                            <p class="mt-4 font-display text-lg font-semibold text-ink">Build sent</p>
                            <p class="mt-1 text-[13.5px] text-muted" x-text="sent"></p>
                            <button type="button" class="btn btn-soft mt-5" @click="dialog = null">Close</button>
                        </div>
                    </template>
                    <form x-show="! sent" @submit.prevent="sendEnquiry()" novalidate class="space-y-3.5">
                        <div class="pr-8">
                            <p class="font-display text-lg font-semibold text-ink">Send your build to our team</p>
                            <p class="mt-1 text-[13px] text-muted">A technician checks it and calls you with advice — no obligation.</p>
                        </div>
                        <input type="text" x-model="form.website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                        <div>
                            <label for="enq-name" class="mb-1.5 block text-[13px] font-medium text-ink">Your name <span class="text-accent-600">*</span></label>
                            <input id="enq-name" type="text" x-model="form.name" autocomplete="name" class="input" :class="formErrors.name && 'border-rose-500'">
                            <p class="mt-1 text-[12.5px] text-rose-600" x-show="formErrors.name" x-text="formErrors.name"></p>
                        </div>
                        <div class="grid gap-3.5 sm:grid-cols-2">
                            <div>
                                <label for="enq-phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile <span class="text-accent-600">*</span></label>
                                <input id="enq-phone" type="tel" inputmode="tel" x-model="form.phone" autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input" :class="formErrors.phone && 'border-rose-500'">
                                <p class="mt-1 text-[12.5px] text-rose-600" x-show="formErrors.phone" x-text="formErrors.phone"></p>
                            </div>
                            <div>
                                <label for="enq-email" class="mb-1.5 block text-[13px] font-medium text-ink">Email</label>
                                <input id="enq-email" type="email" x-model="form.email" autocomplete="email" placeholder="Optional" class="input" :class="formErrors.email && 'border-rose-500'">
                                <p class="mt-1 text-[12.5px] text-rose-600" x-show="formErrors.email" x-text="formErrors.email"></p>
                            </div>
                        </div>
                        <div>
                            <label for="enq-note" class="mb-1.5 block text-[13px] font-medium text-ink">Anything we should know?</label>
                            <textarea id="enq-note" x-model="form.note" rows="3" maxlength="1000" placeholder="Budget, what you’ll use it for (gaming, office, editing), questions…" class="input h-auto resize-y py-2.5"></textarea>
                        </div>
                        <x-turnstile form="pc_builder" />
                        <p class="text-[12.5px] text-rose-600" x-show="formErrors.form" x-text="formErrors.form"></p>
                        <button type="submit" class="btn btn-primary w-full" :disabled="sending"><x-icon name="send" class="size-4" /> <span x-text="sending ? 'Sending…' : 'Send build'"></span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.storefront>
