@php
    /** @var string $type contact|service_request */
    $services ??= collect();
    $selected ??= null;
    $isService = $type === 'service_request';
    $store = app(\App\Settings\StoreSettings::class);
    $invalid = fn (string $field) => $errors->has($field) ? 'border-rose-500 ring-4 ring-rose-500/10' : '';
@endphp

@if (session('enquiry_sent'))
    <div class="flex flex-col items-center rounded-[24px] border border-emerald-500/25 bg-emerald-500/[0.06] px-6 py-10 text-center" role="status">
        <span class="grid size-16 place-items-center rounded-full bg-emerald-500 text-white shadow-[0_12px_30px_-10px_rgb(16_185_129/0.7)]"><x-icon name="check" class="size-8 [stroke-width:2.6]" /></span>
        <p class="mt-5 font-display text-xl font-semibold tracking-tight text-ink">Thanks — we’ve got your {{ $isService ? 'request' : 'message' }}!</p>
        <p class="mt-2 max-w-sm text-sm text-muted">Our team will call you back soon ({{ $store->hours }}). Need it faster? Call <a href="{{ $store->telHref() }}" class="font-mono font-medium text-ink">{{ $store->hotline }}</a>.</p>
        <a href="{{ url()->current() }}" class="btn btn-sm btn-soft mt-6">Send another</a>
    </div>
@else
    <form method="post" action="{{ route('enquiry.store') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        {{-- Honeypot: invisible to people, bots fill it in. --}}
        <div class="absolute -left-[9999px]" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        @if ($isService)
            <div>
                <label for="service_id" class="mb-1.5 block text-[13px] font-medium text-ink">Service needed <span class="text-accent-600">*</span></label>
                <select id="service_id" name="service_id" class="input cursor-pointer {{ $invalid('service_id') }}">
                    <option value="">Choose a service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected((int) old('service_id', $selected) === $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
                <x-form.error for="service_id" />
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="enq-name" class="mb-1.5 block text-[13px] font-medium text-ink">Your name <span class="text-accent-600">*</span></label>
                <input id="enq-name" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="e.g. Rahim Ahmed" class="input {{ $invalid('name') }}">
                <x-form.error for="name" />
            </div>
            <div>
                <label for="enq-phone" class="mb-1.5 block text-[13px] font-medium text-ink">Mobile number <span class="text-accent-600">*</span></label>
                <input id="enq-phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel-national" placeholder="01XXXXXXXXX" class="input font-mono {{ $invalid('phone') }}">
                <x-form.error for="phone" />
            </div>
        </div>

        @if ($isService)
            <div>
                <label for="enq-device" class="mb-1.5 block text-[13px] font-medium text-ink">Device / model <span class="font-normal text-faint">(optional)</span></label>
                <input id="enq-device" name="device" value="{{ old('device') }}" placeholder="e.g. HP Pavilion 15, Hikvision 4-camera system" class="input {{ $invalid('device') }}">
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="enq-email" class="mb-1.5 block text-[13px] font-medium text-ink">Email <span class="font-normal text-faint">(optional)</span></label>
                    <input id="enq-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@example.com" class="input {{ $invalid('email') }}">
                    <x-form.error for="email" />
                </div>
                <div>
                    <label for="enq-subject" class="mb-1.5 block text-[13px] font-medium text-ink">Subject <span class="font-normal text-faint">(optional)</span></label>
                    <input id="enq-subject" name="subject" value="{{ old('subject') }}" placeholder="Product question, bulk order, warranty…" class="input {{ $invalid('subject') }}">
                </div>
            </div>
        @endif

        <div>
            <label for="enq-message" class="mb-1.5 block text-[13px] font-medium text-ink">{{ $isService ? 'What’s the problem?' : 'Your message' }} <span class="text-accent-600">*</span></label>
            <textarea id="enq-message" name="message" rows="4" maxlength="2000"
                placeholder="{{ $isService ? 'Describe the problem, e.g. laptop turns on but no display.' : 'How can we help?' }}"
                class="input h-auto min-h-28 resize-y py-2.5 {{ $invalid('message') }}">{{ old('message') }}</textarea>
            <x-form.error for="message" />
        </div>

        <x-turnstile form="enquiry" />
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-[12px] text-faint">We’ll call you back — no spam, ever.</p>
            <button type="submit" class="btn btn-lg btn-primary" :disabled="sending">
                <x-icon name="send" class="size-4" />
                <span x-text="sending ? 'Sending…' : @js($isService ? 'Request a call back' : 'Send message')">{{ $isService ? 'Request a call back' : 'Send message' }}</span>
            </button>
        </div>
    </form>
@endif
