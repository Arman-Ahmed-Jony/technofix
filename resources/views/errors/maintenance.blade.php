{{-- Maintenance mode page for customers (App\Http\Middleware\MaintenanceMode). Standalone: no menus or catalog queries. --}}
@php($store = app(\App\Settings\StoreSettings::class))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $settings->headline }} | TechnoFix</title>
    <link rel="icon" href="{{ \App\Support\Brand::icon() }}">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="mesh-dark grain relative isolate grid min-h-screen place-items-center px-5 py-16 font-sans text-white antialiased">
    <div class="grid-lines absolute inset-0 -z-10"></div>
    <main class="w-full max-w-xl text-center">
        <img src="{{ \App\Support\Brand::logoDark() }}" alt="TechnoFix" class="mx-auto h-11">
        <span class="mx-auto mt-10 grid size-16 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/15">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="size-8 text-accent-300"><path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5"/></svg>
        </span>
        <h1 class="mt-6 font-display text-[32px] leading-tight font-semibold tracking-[-0.03em] sm:text-[40px]">{{ $settings->headline }}</h1>
        @if ($settings->message)
            <p class="mx-auto mt-4 max-w-md text-[16px] leading-relaxed text-white/70">{{ $settings->message }}</p>
        @endif
        @if ($backAt)
            <p class="mt-5 inline-flex rounded-full bg-white/10 px-4 py-1.5 text-[14px] text-white/85 ring-1 ring-white/15">Expected back {{ $backAt->format('D j M, h:i A') }}</p>
        @endif
        <div class="mt-9 flex flex-wrap justify-center gap-3">
            @foreach ($store->phones() as $phone)
                <a href="{{ $phone['href'] }}" @class(['btn btn-lg', 'btn-primary' => $loop->first, 'btn-glass' => ! $loop->first])>Call {{ $phone['number'] }}</a>
            @endforeach
            @if ($store->whatsapp)
                <a href="{{ $store->whatsappUrl('Hi, I would like to order') }}" class="btn btn-lg btn-glass">WhatsApp</a>
            @endif
        </div>
    </main>
</body>
</html>
