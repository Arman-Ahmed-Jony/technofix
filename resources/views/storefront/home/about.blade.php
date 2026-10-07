{{-- Short text about the shop (good for search engines). --}}
@if (filled($section->title) || filled($section->body))
    <section class="container-x mt-14">
        <div class="mx-auto max-w-4xl text-center">
            @if ($section->title)
                <h2 class="font-display text-lg font-semibold tracking-tight text-ink">{{ $section->title }}</h2>
            @endif
            @foreach (preg_split('/\n\s*\n/', trim((string) $section->body)) as $paragraph)
                @if (filled($paragraph))
                    <p class="mt-3 text-sm leading-relaxed text-muted">{{ $paragraph }}</p>
                @endif
            @endforeach
        </div>
    </section>
@endif
