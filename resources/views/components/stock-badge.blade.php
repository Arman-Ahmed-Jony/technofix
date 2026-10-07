@props(['status'])
<span {{ $attributes->class(['chip ring-1 ring-inset', $status->badgeClasses()]) }}>
    <span class="size-1.5 rounded-full bg-current opacity-70"></span>
    {{ $status->label() }}
</span>
