@use('App\Http\Requests\Storefront\EnquiryRequest')
@php
    $store = app(\App\Settings\StoreSettings::class);
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
@endphp

<x-layouts.storefront page-key="site-visit">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Free site visit', 'url' => null]]" />

        <header class="mt-4 max-w-2xl">
            <p class="eyebrow">Installation</p>
            <h1 class="mt-3 font-display text-[30px] leading-tight font-semibold tracking-[-0.03em] text-ink sm:text-[40px]">Book a free site visit</h1>
            <p class="mt-3 text-[15.5px] text-muted">Planning CCTV, WiFi or an office network? A technician visits, checks the place and gives you a clear quote — no obligation.</p>
        </header>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)] lg:gap-8">
            <section class="card p-6 sm:p-8">
                @if (session('enquiry_sent'))
                    @include('storefront.partials.enquiry-sent', ['what' => 'booking'])
                @else
                    <form method="post" action="{{ route('enquiry.store') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-5">
                        @csrf
                        <input type="hidden" name="type" value="site_visit">
                        <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="v-service" class="mb-1.5 block text-[13px] font-medium text-ink">What do you need? <span class="text-accent-600">*</span></label>
                                <select id="v-service" name="site_service" class="input cursor-pointer {{ $invalid('site_service') }}">
                                    <option value="">Choose</option>
                                    @foreach (EnquiryRequest::SITE_SERVICES as $option)
                                        <option @selected(old('site_service', request('need')) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <x-form.error for="site_service" />
                            </div>
                            <div>
                                <label for="v-property" class="mb-1.5 block text-[13px] font-medium text-ink">Type of place <span class="text-accent-600">*</span></label>
                                <select id="v-property" name="property" class="input cursor-pointer {{ $invalid('property') }}">
                                    <option value="">Choose</option>
                                    @foreach (EnquiryRequest::PROPERTIES as $option)
                                        <option @selected(old('property') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <x-form.error for="property" />
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="v-district" class="mb-1.5 block text-[13px] font-medium text-ink">District <span class="text-accent-600">*</span></label>
                                <select id="v-district" name="district" class="input cursor-pointer {{ $invalid('district') }}">
                                    <option value="">Choose your district</option>
                                    @foreach ($districts as $district)
                                        <option @selected(old('district') === $district)>{{ $district }}</option>
                                    @endforeach
                                </select>
                                <x-form.error for="district" />
                            </div>
                            <div>
                                <label for="v-area" class="mb-1.5 block text-[13px] font-medium text-ink">Area / thana <span class="text-accent-600">*</span></label>
                                <input id="v-area" name="area" value="{{ old('area') }}" placeholder="e.g. Mirpur 10" class="input {{ $invalid('area') }}">
                                <x-form.error for="area" />
                            </div>
                        </div>

                        <div>
                            <label for="v-address" class="mb-1.5 block text-[13px] font-medium text-ink">Address <span class="font-normal text-faint">(optional — we’ll confirm by phone)</span></label>
                            <input id="v-address" name="address" value="{{ old('address') }}" autocomplete="street-address" placeholder="House, road, landmark" class="input">
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="v-date" class="mb-1.5 block text-[13px] font-medium text-ink">Preferred date <span class="font-normal text-faint">(optional)</span></label>
                                <input id="v-date" name="preferred_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date') }}" class="input cursor-pointer {{ $invalid('preferred_date') }}">
                                <x-form.error for="preferred_date" />
                            </div>
                            <div>
                                <label for="v-slot" class="mb-1.5 block text-[13px] font-medium text-ink">Preferred time <span class="font-normal text-faint">(optional)</span></label>
                                <select id="v-slot" name="time_slot" class="input cursor-pointer">
                                    <option value="">Any time</option>
                                    @foreach (EnquiryRequest::TIME_SLOTS as $slot)
                                        <option @selected(old('time_slot') === $slot)>{{ $slot }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="v-name" class="mb-1.5 block text-[13px] font-medium text-ink">Your name <span class="text-accent-600">*</span></label>
                                <input id="v-name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="e.g. Rahim Ahmed" class="input {{ $invalid('name') }}">
                                <x-form.error for="name" />
                            </div>
                            <div>
                                <label for="v-phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number <span class="text-accent-600">*</span></label>
                                <input id="v-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input font-mono {{ $invalid('phone') }}">
                                <x-form.error for="phone" />
                            </div>
                        </div>

                        <div>
                            <label for="v-message" class="mb-1.5 block text-[13px] font-medium text-ink">Anything we should know? <span class="font-normal text-faint">(optional)</span></label>
                            <textarea id="v-message" name="message" rows="3" maxlength="2000" placeholder="e.g. 3 floors, about 8 cameras, need phone viewing" class="input h-auto resize-y py-2.5">{{ old('message') }}</textarea>
                        </div>

                        <x-turnstile form="enquiry" />
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[12px] text-faint">We’ll call to confirm the time.</p>
                            <button type="submit" class="btn btn-lg btn-primary" :disabled="sending">
                                <x-icon name="calendar-check" class="size-4" /> <span x-text="sending ? 'Sending…' : 'Book the visit'">Book the visit</span>
                            </button>
                        </div>
                    </form>
                @endif
            </section>

            <aside class="space-y-4 lg:sticky lg:top-[138px]">
                <div class="mesh-dark grain relative isolate overflow-hidden rounded-[24px] p-6 text-white sm:p-7">
                    <div class="grid-lines absolute inset-0 -z-10"></div>
                    <p class="eyebrow text-accent-300">What happens</p>
                    <ol class="mt-4 space-y-4">
                        @foreach ([
                            ['calendar-check', 'We confirm the time', 'We call you to agree a day and time that suits you.'],
                            ['map-pin', 'A technician visits', 'We check the place — camera spots, cable runs, WiFi coverage.'],
                            ['file-invoice', 'You get a clear quote', 'Equipment and installation, itemised — no obligation.'],
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
                    <p class="font-display text-[15px] font-semibold text-ink">Questions first?</p>
                    <p class="mt-1 text-[13px] text-muted">{{ $store->hours }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-soft"><x-icon name="phone" class="size-4" /> Call</a>
                        <a href="{{ $store->whatsappUrl('Hi TechnoFix, I’d like a site visit.') }}" target="_blank" rel="noopener" class="btn btn-sm btn-soft"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.storefront>
