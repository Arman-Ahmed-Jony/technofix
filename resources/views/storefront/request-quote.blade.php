@use('App\Http\Requests\Storefront\EnquiryRequest')
@php
    $store = app(\App\Settings\StoreSettings::class);
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
    $prefill = $product ? $product->name.' — qty: ' : '';
@endphp

<x-layouts.storefront page-key="request-quote">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Request a quote', 'url' => null]]" />

        <header class="mt-4 max-w-2xl">
            <p class="eyebrow">Quotes &amp; bulk orders</p>
            <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[40px]">Get a quote</h1>
            <p class="mt-3 text-[15.5px] text-muted">Tell us what you need — one item or a full office setup. We reply with prices, stock and delivery time.</p>
        </header>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-8">
            <section class="card p-6 sm:p-8">
                @if (session('enquiry_sent'))
                    @include('storefront.partials.enquiry-sent', ['what' => 'quote request'])
                @else
                    <form method="post" action="{{ route('enquiry.store') }}" x-data="{ sending: false, buyer: @js(old('buyer', 'personal')) }" @submit="sending = true" novalidate class="space-y-5">
                        @csrf
                        <input type="hidden" name="type" value="quote">
                        <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                        <fieldset>
                            <legend class="mb-2 text-[13px] font-medium text-ink">I’m buying for <span class="text-accent-600">*</span></legend>
                            <div class="grid gap-2 sm:grid-cols-3">
                                @foreach (EnquiryRequest::BUYERS as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-line p-3 text-[13.5px] text-ink transition hover:border-line-strong has-checked:border-brand-500 has-checked:bg-brand-500/[0.05] has-checked:ring-4 has-checked:ring-brand-500/10">
                                        <input type="radio" name="buyer" value="{{ $value }}" x-model="buyer" class="size-4 accent-brand-600"> {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <x-form.error for="buyer" />
                        </fieldset>

                        <div x-show="buyer !== 'personal'" x-cloak>
                            <label for="q-company" class="mb-1.5 block text-[13px] font-medium text-ink">Company / shop name <span class="text-accent-600">*</span></label>
                            <input id="q-company" name="company" value="{{ old('company') }}" autocomplete="organization" placeholder="e.g. Rahman Traders Ltd." class="input {{ $invalid('company') }}">
                            <x-form.error for="company" />
                        </div>

                        <div>
                            <label for="q-items" class="mb-1.5 block text-[13px] font-medium text-ink">What do you need? <span class="text-accent-600">*</span></label>
                            <textarea id="q-items" name="items" rows="5" maxlength="2000" placeholder="Products and quantities, e.g.&#10;10 × Core i5 office desktops&#10;1 × 16-port gigabit switch&#10;8 × 2MP IP cameras with NVR"
                                class="input h-auto min-h-32 resize-y py-2.5 {{ $invalid('items') }}">{{ old('items', $prefill) }}</textarea>
                            <x-form.error for="items" />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="q-budget" class="mb-1.5 block text-[13px] font-medium text-ink">Budget <span class="font-normal text-faint">(optional)</span></label>
                                <input id="q-budget" name="budget" value="{{ old('budget') }}" placeholder="e.g. ৳5 lakh" class="input">
                            </div>
                            <div>
                                <label for="q-district" class="mb-1.5 block text-[13px] font-medium text-ink">Delivery district <span class="font-normal text-faint">(optional)</span></label>
                                <input id="q-district" name="district" value="{{ old('district') }}" placeholder="e.g. Dhaka" class="input">
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="q-name" class="mb-1.5 block text-[13px] font-medium text-ink">Your name <span class="text-accent-600">*</span></label>
                                <input id="q-name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="e.g. Rahim Ahmed" class="input {{ $invalid('name') }}">
                                <x-form.error for="name" />
                            </div>
                            <div>
                                <label for="q-phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number <span class="text-accent-600">*</span></label>
                                <input id="q-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input font-mono {{ $invalid('phone') }}">
                                <x-form.error for="phone" />
                            </div>
                        </div>

                        <div>
                            <label for="q-email" class="mb-1.5 block text-[13px] font-medium text-ink">Email <span class="font-normal text-faint">(optional — for a written quotation)</span></label>
                            <input id="q-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@company.com" class="input {{ $invalid('email') }}">
                            <x-form.error for="email" />
                        </div>

                        <div>
                            <label for="q-message" class="mb-1.5 block text-[13px] font-medium text-ink">Anything else? <span class="font-normal text-faint">(optional)</span></label>
                            <textarea id="q-message" name="message" rows="2" maxlength="2000" placeholder="Deadline, brand preferences, installation needed…" class="input h-auto resize-y py-2.5">{{ old('message') }}</textarea>
                        </div>

                        <x-turnstile form="enquiry" />
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[12px] text-faint">We’ll call or email you back — no spam, ever.</p>
                            <button type="submit" class="btn btn-lg btn-primary" :disabled="sending">
                                <x-icon name="send" class="size-4" /> <span x-text="sending ? 'Sending…' : 'Request a quote'">Request a quote</span>
                            </button>
                        </div>
                    </form>
                @endif
            </section>

            <aside class="space-y-4 lg:sticky lg:top-[138px]">
                <div class="mesh-dark grain relative isolate overflow-hidden rounded-[24px] p-6 text-white sm:p-7">
                    <div class="grid-lines absolute inset-0 -z-10"></div>
                    <p class="eyebrow text-accent-300">How it works</p>
                    <ol class="mt-4 space-y-4">
                        @foreach ([
                            ['list-details', 'Tell us what you need', 'Models, quantities or just the job — “CCTV for a 3-floor office” is fine.'],
                            ['file-invoice', 'Get prices & availability', 'We reply with a quote, stock status and delivery time.'],
                            ['truck-delivery', 'Delivery & setup', 'Delivered across Bangladesh; installation on request.'],
                        ] as [$icon, $title, $text])
                            <li class="flex gap-3.5">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white/10 ring-1 ring-white/15"><x-icon :name="$icon" class="size-5 text-brand-300" /></span>
                                <span>
                                    <span class="block text-[14.5px] font-semibold">{{ $title }}</span>
                                    <span class="mt-0.5 block text-[13px] text-white/65">{{ $text }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="card p-5">
                    <p class="font-display text-[15px] font-semibold text-ink">Prefer to talk?</p>
                    <p class="mt-1 text-[13px] text-muted">{{ $store->hours }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-soft"><x-icon name="phone" class="size-4" /> Call</a>
                        <a href="{{ $store->whatsappUrl('Hi TechnoFix, I’d like a quote.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.storefront>
