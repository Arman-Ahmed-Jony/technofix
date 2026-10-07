@php
    $store = app(\App\Settings\StoreSettings::class);
    $first = \Illuminate\Support\Str::of($claim->customer_name)->before('(')->trim()->before(' ')->lower()->ucfirst();
    $serial = \App\Models\WarrantyClaim::maskSerial($claim->serial);
@endphp
<x-mail::message>
# {{ $created ? 'We’ve received your product' : $event->status->customerLabel() }}

Hi {{ $first ?: 'there' }}, {{ $created ? 'thank you for bringing your product to us. Here is your warranty claim:' : 'here is the latest on your warranty claim:' }}

<x-mail::panel>
**{{ $claim->product }}**@if ($claim->qty > 1) × {{ $claim->qty }}@endif<br>
Claim no. {{ $claim->number }}@if ($serial) · Serial {{ $serial }}@endif<br>
Status: **{{ $event->status->customerLabel() }}**
</x-mail::panel>

{{ $event->status->customerText() }}

@if ($event->note)
**Note from our team:**

{{ $event->note }}
@endif

<x-mail::button :url="route('warranty')">
Check warranty status
</x-mail::button>

Check any time on our website with your mobile number, or call {{ $store->hotline }} and mention claim no. {{ $claim->number }}.
</x-mail::message>
