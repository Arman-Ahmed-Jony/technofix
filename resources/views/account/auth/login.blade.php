<x-account.auth-card title="Sign in" intro="Welcome back! Use your mobile number (or email) and password.">
    <form method="post" action="{{ route('account.login') }}" x-data="{ sending: false }" @submit="sending = true" novalidate class="space-y-4">
        @csrf
        @if (array_key_exists((string) request('to'), \App\Http\Controllers\Account\AuthController::RETURN_TO))
            <input type="hidden" name="to" value="{{ request('to') }}">
        @endif
        <x-account.field name="login" label="Mobile number or email" autocomplete="username" placeholder="01XXXXXXXXX" :required="true" autofocus />
        <x-account.field name="password" label="Password" type="password" autocomplete="current-password" :required="true" />
        <div class="flex items-center justify-between text-[13px]">
            <label class="flex cursor-pointer items-center gap-2 text-ink-2"><input type="checkbox" name="remember" value="1" class="checkbox" checked> Keep me signed in</label>
            <a href="{{ route('account.password.request') }}" class="link">Forgot password?</a>
        </div>
        <x-turnstile form="login" />
        <button type="submit" class="btn btn-lg btn-primary w-full" :disabled="sending"><span x-text="sending ? 'Signing in…' : 'Sign in'">Sign in</span></button>
    </form>
    <p class="mt-6 text-center text-sm text-muted">New to TechnoFix? <a href="{{ route('account.register', array_filter(['to' => array_key_exists((string) request('to'), \App\Http\Controllers\Account\AuthController::RETURN_TO) ? request('to') : null])) }}" class="link font-semibold">Create an account</a></p>
    <p class="mt-2 text-center text-[12.5px] text-faint">You can also order without an account.</p>
</x-account.auth-card>
