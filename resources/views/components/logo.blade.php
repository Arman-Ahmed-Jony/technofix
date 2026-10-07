@props(['class' => 'h-10'])
{{-- Full-colour logo on light backgrounds; light-text version in dark mode (lazy, so light-mode visitors never download it). --}}
{{-- Logos from Admin → Settings → Branding (App\Support\Brand). --}}
<img src="{{ \App\Support\Brand::logo() }}" alt="TechnoFix" width="210" height="40" class="{{ $class }} w-auto dark:hidden">
<img src="{{ \App\Support\Brand::logoDark() }}" alt="TechnoFix" width="210" height="40" loading="lazy" class="{{ $class }} hidden w-auto dark:block">
