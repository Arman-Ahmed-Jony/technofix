{{-- End of <body>: this page's analytics events (read by resources/js/tracking.js) and the owner's extra code. --}}
@php
    $tracking = app(\App\Support\Tracking::class);
@endphp
@if ($tracking->enabled())
    @if ($gtm = app(\App\Settings\TrackingSettings::class)->gtm_id)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <script>window.tfEvents = {{ \App\Support\Tracking::js($tracking->events()) }};</script>
@endif
{!! app(\App\Settings\TrackingSettings::class)->body_code !!}
