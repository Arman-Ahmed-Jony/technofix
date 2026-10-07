{{-- Thank-you after an enquiry form. $what = "request" / "message"… --}}
@php($store = app(\App\Settings\StoreSettings::class))
<div class="flex flex-col items-center rounded-[24px] border border-emerald-500/25 bg-emerald-500/[0.06] px-6 py-10 text-center" role="status">
    <span class="grid size-16 place-items-center rounded-full bg-emerald-500 text-white shadow-[0_12px_30px_-10px_rgb(16_185_129/0.7)]"><x-icon name="check" class="size-8 [stroke-width:2.6]" /></span>
    <p class="mt-5 font-display text-xl font-semibold tracking-tight text-ink">Thanks — we’ve got your {{ $what ?? 'request' }}!</p>
    <p class="mt-2 max-w-sm text-sm text-muted">Our team will call you back soon ({{ $store->hours }}). Need it faster? Call <a href="{{ $store->telHref() }}" class="font-mono font-medium text-ink">{{ $store->hotline }}</a>.</p>
    <a href="{{ url()->current() }}" class="btn btn-sm btn-soft mt-6">Send another</a>
</div>
