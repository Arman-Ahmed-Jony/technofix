@props(['name'])
{{-- A sprite icon whose name comes from Alpine, e.g. <x-icon-bound name="product.icon" />. The icon must already be in
     the page sprite (register it with IconSprite::use() when rendering the page). --}}
<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->class(['tf-icon', 'size-5' => ! $attributes->has('class')])->merge(['aria-hidden' => 'true']) }}><use :href="'#i-' + ({{ $name }})"/></svg>
