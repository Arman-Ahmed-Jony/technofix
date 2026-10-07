@php($store = app(\App\Settings\StoreSettings::class))
<x-account.auth-card title="Forgot your password?" eyebrow="Password" intro="Enter the email on your account and we’ll send you a link to choose a new password.">
    <form method="post" action="{{ route('account.password.email') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
        @csrf
        <x-account.field name="email" label="Email" type="email" autocomplete="email" :required="true" autofocus />
        <x-turnstile form="password" />
        <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="sending">Send reset link</button>
    </form>
    <p class="mt-6 rounded-2xl bg-card-2 p-4 text-[13px] text-muted ring-1 ring-line">No email on your account? Call <a href="{{ $store->telHref() }}" class="font-mono text-ink">{{ $store->hotline }}</a> and we’ll help you back in.</p>
    <p class="mt-4 text-center text-sm"><a href="{{ route('account.login') }}" class="link">Back to sign in</a></p>
</x-account.auth-card>
