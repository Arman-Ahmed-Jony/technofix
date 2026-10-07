<x-account.auth-card title="Create an account" eyebrow="Join" intro="Takes a minute. Next time, checkout fills itself in.">
    <form method="post" action="{{ route('account.register') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
        @csrf
        @if (array_key_exists((string) request('to'), \App\Http\Controllers\Account\AuthController::RETURN_TO))
            <input type="hidden" name="to" value="{{ request('to') }}">
        @endif
        <x-account.field name="name" label="Full name" :value="$prefill['name'] ?? null" autocomplete="name" placeholder="e.g. Rahim Ahmed" :required="true" autofocus />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-account.field name="phone" label="Mobile number" type="tel" inputmode="tel" :value="$prefill['phone'] ?? null" autocomplete="tel-national" placeholder="01XXXXXXXXX" :required="true" hint="You’ll sign in with this." />
            <x-account.field name="email" label="Email" type="email" :value="$prefill['email'] ?? null" autocomplete="email" placeholder="Optional" hint="Needed to reset your password by email." />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-account.field name="password" label="Password" type="password" autocomplete="new-password" :required="true" hint="At least 8 characters." />
            <x-account.field name="password_confirmation" label="Repeat password" type="password" autocomplete="new-password" :required="true" />
        </div>
        <x-turnstile form="register" />
        <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="sending"><span x-text="sending ? 'Creating…' : 'Create account'">Create account</span></button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">Already have an account? <a href="{{ route('account.login') }}" class="link font-semibold">Sign in</a></p>
</x-account.auth-card>
