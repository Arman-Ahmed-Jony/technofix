@props(['form'])
{{-- Cloudflare Turnstile for a protected form (App\Support\Turnstile). Usually invisible: it only shows a box when
     Cloudflare wants the visitor to tick it. The script loads when the form comes into view (resources/js/app.js). --}}
@if (\App\Support\Turnstile::enabled($form))
    <div data-turnstile="{{ \App\Support\Turnstile::siteKey() }}" data-action="{{ $form }}" {{ $attributes }}></div>
@endif
@error(\App\Support\Turnstile::FIELD)
    <p class="mt-2 text-[13px] font-medium text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>
@enderror
