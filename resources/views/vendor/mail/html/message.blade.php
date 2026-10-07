@php($store = app(\App\Settings\StoreSettings::class))
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer: store details from Admin → Settings → Store & contact --}}
<x-slot:footer>
<x-mail::footer>
{{ config('app.name') }} · {{ $store->address }}<br>
Hotline {{ $store->hotline }} · {{ $store->email }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
