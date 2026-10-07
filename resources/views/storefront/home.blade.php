{{-- Home page = the sections in Admin → Content → Home page, in order (App\Support\HomePage). --}}
@php($seo = app(\App\Support\Seo::class))
<x-layouts.storefront>
    <x-slot:head>{{ $seo->homeSchema() }}</x-slot:head>
    <h1 class="sr-only">{{ str_replace(' | ', ' — ', $seo->title(null)) }}</h1>

    @foreach ($sections as $item)
        @include('storefront.home.'.str_replace('_', '-', $item['type']), ['section' => $item['section'], ...$item['data']])
    @endforeach
</x-layouts.storefront>
