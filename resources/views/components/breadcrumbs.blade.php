@props(['items'])
<nav aria-label="Breadcrumb" {{ $attributes->class('text-[13px] text-muted') }}>
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
        <li><a href="{{ route('home') }}" class="flex items-center gap-1 transition hover:text-ink"><x-icon name="home" class="size-4" /><span class="sr-only">Home</span></a></li>
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                <x-icon name="chevron-right" class="size-3.5 text-faint" />
                @if ($item['url'] && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="transition hover:text-ink">{{ $item['label'] }}</a>
                @else
                    <span class="line-clamp-1 font-medium text-ink" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
{{ \App\Support\Seo::breadcrumbSchema($items) }}
