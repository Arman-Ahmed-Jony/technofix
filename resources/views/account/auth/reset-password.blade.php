<x-account.auth-card title="Choose a new password" eyebrow="Password">
    <form method="post" action="{{ route('account.password.update') }}" novalidate class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-account.field name="email" label="Email" type="email" :value="$email" autocomplete="email" :required="true" />
        <x-account.field name="password" label="New password" type="password" autocomplete="new-password" :required="true" hint="At least 8 characters." autofocus />
        <x-account.field name="password_confirmation" label="Repeat new password" type="password" autocomplete="new-password" :required="true" />
        <button type="submit" class="btn btn-lg btn-primary w-full">Save password</button>
    </form>
</x-account.auth-card>
