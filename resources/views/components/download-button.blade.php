@props(['size' => 'lg'])
{{-- Glowing "Download software" button (Admin → Content → Software download). Opens the link in a new tab. --}}
@php($download = app(\App\Settings\DownloadSettings::class))
<a href="{{ $download->url }}" target="_blank" rel="noopener" {{ $attributes->class(['btn-neon', 'btn-neon-sm' => $size === 'sm']) }}>
    <x-icon name="cloud-download" @class(['size-5' => $size !== 'sm', 'size-4' => $size === 'sm']) />
    <span>{{ $download->label }}</span>
    <x-icon name="external-link" class="size-3.5 opacity-60" />
    <span class="sr-only">(opens in a new tab)</span>
</a>
