@use('App\Enums\WarrantyStatus')
@use('App\Models\WarrantyClaim')
@php
    $store = app(\App\Settings\StoreSettings::class);
    $masked = substr($phone, 0, 3).'•••••'.substr($phone, -3);
    $whatsapp = $store->whatsapp ? $store->whatsappUrl('Hi, about my warranty claim') : null;
    // Customer-facing progress; "Closed" is shown on its own.
    $steps = [WarrantyStatus::Received, WarrantyStatus::Claimed, WarrantyStatus::Ready, WarrantyStatus::Delivered];
    $chip = fn (WarrantyStatus $status) => match ($status) {
        WarrantyStatus::Received => 'bg-card-2 text-ink-2 ring-line',
        WarrantyStatus::Claimed => 'bg-amber-500/10 text-amber-700 ring-amber-500/25 dark:text-amber-300',
        WarrantyStatus::Ready => 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/25 dark:text-emerald-300',
        WarrantyStatus::Delivered => 'bg-brand-500/10 text-highlight ring-brand-500/25',
        WarrantyStatus::Closed => 'bg-card-2 text-muted ring-line',
    };
@endphp

<x-layouts.storefront title="Your warranty claims" :noindex="true">
    <div class="container-x pt-5 lg:pt-7">
        <x-breadcrumbs :items="[['label' => 'Warranty status', 'url' => route('warranty')], ['label' => 'Your claims', 'url' => null]]" />

        <div class="mx-auto mt-5 max-w-4xl">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Warranty status</p>
                    <h1 class="mt-2 font-display text-[26px] font-semibold tracking-tight text-ink sm:text-[32px]">Your warranty claims</h1>
                    <p class="mt-1 text-sm text-muted">For mobile number <span class="font-mono text-ink">{{ $masked }}</span> · {{ $claims->count() }} {{ Str::plural('product', $claims->count()) }}</p>
                </div>
                <a href="{{ route('warranty') }}" class="btn btn-sm btn-outline"><x-icon name="search" class="size-4" /> Check another number</a>
            </header>

            @forelse ($claims as $claim)
                @php
                    $current = array_search($claim->status, $steps, true);
                    $serial = WarrantyClaim::maskSerial($claim->serial);
                    $newSerial = WarrantyClaim::maskSerial($claim->new_serial);
                    $updates = $claim->events->filter(fn ($event) => $event->note || $event->status !== WarrantyStatus::Received)->reverse();
                @endphp
                <article class="card mt-5 p-5 sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-mono text-[12px] text-faint">Claim no. {{ $claim->number }}@if ($claim->received_on) · received {{ $claim->received_on->format('d M Y') }}@endif</p>
                            <h2 class="mt-1 font-display text-[18px] leading-snug font-semibold tracking-tight text-ink sm:text-[20px]">
                                {{ $claim->product }}@if ($claim->qty > 1) <span class="font-mono text-[14px] text-muted">× {{ $claim->qty }}</span>@endif
                            </h2>
                        </div>
                        <span class="chip py-1.5 text-[13px] ring-1 {{ $chip($claim->status) }}">{{ $claim->status->customerLabel() }}</span>
                    </div>

                    <div class="mt-5 grid gap-6 md:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                        <div>
                            @if ($current === false)
                                <div class="flex gap-3 rounded-2xl bg-card-2 p-4 ring-1 ring-line">
                                    <x-icon name="archive" class="mt-0.5 size-5 shrink-0 text-muted" />
                                    <p class="text-sm text-muted">{{ $claim->status->customerText() }}</p>
                                </div>
                            @else
                                <ol>
                                    @foreach ($steps as $i => $step)
                                        @php
                                            $done = $i <= $current;
                                            $isCurrent = $i === $current;
                                        @endphp
                                        <li class="relative flex gap-3.5 pb-4 last:pb-0">
                                            @unless ($loop->last)
                                                <span @class(['absolute top-8 left-[15px] h-[calc(100%-2rem)] w-0.5', 'bg-emerald-500' => $done && ! $isCurrent, 'bg-line' => ! $done || $isCurrent])></span>
                                            @endunless
                                            <span @class([
                                                'relative grid size-8 shrink-0 place-items-center rounded-full ring-1',
                                                'bg-emerald-500 text-white ring-emerald-500' => $done && ! $isCurrent,
                                                'bg-brand-500 text-white ring-brand-500 shadow-[0_0_0_5px_rgb(0_159_227/0.15)]' => $isCurrent,
                                                'bg-card-2 text-faint ring-line' => ! $done,
                                            ])>
                                                @if ($done && ! $isCurrent)
                                                    <x-icon name="check" class="size-4 [stroke-width:2.6]" />
                                                @else
                                                    <span class="font-mono text-[11.5px]">{{ $i + 1 }}</span>
                                                @endif
                                            </span>
                                            <div class="pt-1">
                                                <p @class(['text-[14px] font-semibold', 'text-ink' => $done, 'text-muted' => ! $done])>{{ $step->customerLabel() }}</p>
                                                @if ($isCurrent)
                                                    <p class="mt-0.5 text-[13px] text-muted">{{ $step->customerText() }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>

                        <div class="space-y-4">
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-2xl bg-card-2 p-4 text-[13px] ring-1 ring-line">
                                @if ($serial)
                                    <div><dt class="text-muted">Serial</dt><dd class="font-mono text-ink">{{ $serial }}</dd></div>
                                @endif
                                @if ($claim->claimed_on)
                                    <div><dt class="text-muted">Sent for claim</dt><dd class="text-ink">{{ $claim->claimed_on->format('d M Y') }}</dd></div>
                                @endif
                                @if ($claim->returned_on)
                                    <div><dt class="text-muted">Back at our shop</dt><dd class="text-ink">{{ $claim->returned_on->format('d M Y') }}</dd></div>
                                @endif
                                @if ($newSerial)
                                    <div><dt class="text-muted">New serial</dt><dd class="font-mono text-ink">{{ $newSerial }}</dd></div>
                                @endif
                                @if ($claim->problem)
                                    <div class="col-span-2"><dt class="text-muted">Problem you reported</dt><dd class="text-ink">{{ Str::ucfirst(Str::lower($claim->problem)) }}</dd></div>
                                @endif
                            </dl>

                            @if ($claim->customer_note)
                                <div class="rounded-2xl bg-brand-500/[0.06] p-4 ring-1 ring-brand-500/20">
                                    <p class="flex items-center gap-1.5 text-[12px] font-semibold tracking-wide text-highlight uppercase"><x-icon name="message-circle" class="size-4" /> Note from our team</p>
                                    <p class="mt-1.5 text-[14px] whitespace-pre-line text-ink">{{ $claim->customer_note }}</p>
                                </div>
                            @endif

                            @if ($updates->isNotEmpty())
                                <details class="group text-[13px]">
                                    <summary class="flex cursor-pointer list-none items-center gap-1.5 font-medium text-muted hover:text-ink">
                                        <x-icon name="chevron-right" class="size-4 transition group-open:rotate-90" /> Updates ({{ $updates->count() }})
                                    </summary>
                                    <ul class="mt-2 space-y-2.5 border-l border-line pl-4">
                                        @foreach ($updates as $update)
                                            <li>
                                                <p class="text-ink"><span class="font-medium">{{ $update->status->customerLabel() }}</span> <span class="font-mono text-[11.5px] text-faint">{{ $update->created_at->format('d M Y') }}</span></p>
                                                @if ($update->note)
                                                    <p class="mt-0.5 whitespace-pre-line text-muted">{{ $update->note }}</p>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <section class="card mt-6 grid place-items-center px-6 py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-2xl bg-card-2 ring-1 ring-line"><x-icon name="shield-search" class="size-7 text-muted" /></span>
                    <h2 class="mt-4 font-display text-lg font-semibold text-ink">No warranty claims for this number</h2>
                    <p class="mt-1 max-w-md text-sm text-muted">We couldn’t find a product registered with {{ $masked }}. Maybe we noted a different number — call us and we’ll check it for you.</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('warranty') }}" class="btn btn-sm btn-outline"><x-icon name="search" class="size-4" /> Try another number</a>
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-primary"><x-icon name="phone-call" class="size-4" /> {{ $store->hotline }}</a>
                    </div>
                </section>
            @endforelse

            @if ($claims->isNotEmpty())
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-card-2 p-4 text-[13.5px] ring-1 ring-line">
                    <p class="text-muted">Questions about a claim? Mention its claim number.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $store->telHref() }}" class="btn btn-sm btn-outline"><x-icon name="phone-call" class="size-4" /> Call us</a>
                        @if ($whatsapp)
                            <a href="{{ $whatsapp }}" class="btn btn-sm btn-whatsapp"><x-icon name="brand-whatsapp" class="size-4" /> WhatsApp</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.storefront>
