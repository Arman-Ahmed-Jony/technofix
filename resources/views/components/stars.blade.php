@props(['rating' => 0, 'size' => 'size-4'])
{{-- Star rating, e.g. 4.5 → four and a half gold stars over grey ones. --}}
@php($percent = max(0, min(100, (float) $rating / 5 * 100)))
<span {{ $attributes->class('relative inline-flex shrink-0') }} role="img" aria-label="{{ rtrim(rtrim(number_format((float) $rating, 1), '0'), '.') }} out of 5 stars">
    <span class="flex text-line-strong" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)<x-icon name="star-filled" :class="$size" />@endfor
    </span>
    <span class="absolute inset-y-0 left-0 flex overflow-hidden text-amber-400" style="width: {{ $percent }}%" aria-hidden="true">
        @for ($i = 0; $i < 5; $i++)<x-icon name="star-filled" :class="$size.' shrink-0'" />@endfor
    </span>
</span>
