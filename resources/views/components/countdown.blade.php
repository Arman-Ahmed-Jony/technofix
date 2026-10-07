@props(['endsAt', 'label' => 'Ends in', 'dark' => true, 'size' => 'md', 'labelClass' => null])
{{-- Live countdown boxes (days appear only when more than a day is left). Alpine component: countdown() in app.js. --}}
@php($small = $size === 'sm')
<div {{ $attributes->class('flex items-center gap-3') }} x-data="countdown(@js($endsAt->toIso8601String()))">
    @if ($label)
        <span @class(['text-[11px] font-semibold tracking-[0.14em] uppercase', $labelClass ?? ($dark ? 'text-white/50' : 'text-muted')])>{{ $label }}</span>
    @endif
    <div @class(['flex items-center', 'gap-1.5' => ! $small, 'gap-1' => $small, 'text-white' => $dark, 'text-ink' => ! $dark])>
        @foreach (['d' => 'days', 'h' => 'hrs', 'm' => 'min', 's' => 'sec'] as $unit => $unitLabel)
            <span @if ($unit === 'd') x-show="parts.d > 0" x-cloak @endif @class([
                'grid place-items-center rounded-2xl',
                'glass' => $dark,
                'bg-card ring-1 ring-line' => ! $dark,
                'min-w-14 px-2 py-2' => ! $small,
                'min-w-11 rounded-xl px-1.5 py-1.5' => $small,
            ])>
                <span @class(['font-mono leading-none font-medium tabular-nums', 'text-xl' => ! $small, 'text-[15px]' => $small])
                    x-text="{{ $unit === 'd' ? 'parts.d' : 'parts.'.$unit }}">00</span>
                <span @class(['mt-1 text-[9.5px] tracking-wider uppercase', 'text-white/45' => $dark, 'text-faint' => ! $dark])>{{ $unitLabel }}</span>
            </span>
        @endforeach
    </div>
</div>
