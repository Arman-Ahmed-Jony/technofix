@props(['name'])
{{-- Tabler icon via the page sprite (App\Support\IconSprite). Usage: <x-icon name="cpu" class="size-5" /> --}}
<svg {!! app(\App\Support\IconSprite::class)->use($name) !!} {{ $attributes->class(['tf-icon', 'size-5' => ! $attributes->has('class')])->merge(['aria-hidden' => 'true']) }}><use href="#i-{{ $name }}"/></svg>
