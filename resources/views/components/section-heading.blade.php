@props(['title', 'eyebrow' => null, 'subtitle' => null, 'href' => null, 'linkLabel' => 'View all'])
<div {{ $attributes->class('mb-6 flex items-end justify-between gap-4') }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow mb-2">{{ $eyebrow }}</p>
        @endif
        <h2 class="section-title">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1.5 text-[14.5px] text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
    @if ($href)
        <a href="{{ $href }}" class="btn btn-sm btn-soft group shrink-0">
            {{ $linkLabel }} <x-icon name="arrow-right" class="size-3.5 transition group-hover:translate-x-0.5" />
        </a>
    @endif
</div>
