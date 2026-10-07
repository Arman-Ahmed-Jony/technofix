<x-account.shell title="Profile & password" active="account.profile" :breadcrumbs="[['label' => 'Profile & password', 'url' => null]]">
    <div class="grid items-start gap-5 xl:grid-cols-2">
        <section class="card p-6 sm:p-7">
            <h1 class="font-display text-xl font-semibold tracking-tight text-ink">Your details</h1>
            <form method="post" action="{{ route('account.profile.update') }}" novalidate class="mt-5 space-y-4">
                @csrf
                @method('put')
                <x-account.field name="name" label="Full name" :value="$customer->name" autocomplete="name" :required="true" />
                <x-account.field name="phone" label="Mobile number" type="tel" inputmode="tel" :value="$customer->phone" autocomplete="tel-national" :required="true" hint="You sign in with this." />
                <x-account.field name="email" label="Email" type="email" :value="$customer->email" autocomplete="email" placeholder="Optional" hint="For order emails and resetting your password." />
                <button type="submit" class="btn btn-ink">Save details</button>
            </form>
        </section>

        <section class="card p-6 sm:p-7">
            <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Change password</h2>
            <form method="post" action="{{ route('account.password.change') }}" novalidate class="mt-5 space-y-4">
                @csrf
                @method('put')
                <x-account.field name="current_password" label="Current password" type="password" autocomplete="current-password" :required="true" bag="password" />
                <x-account.field name="password" label="New password" type="password" autocomplete="new-password" :required="true" bag="password" hint="At least 8 characters." />
                <x-account.field name="password_confirmation" label="Repeat new password" type="password" autocomplete="new-password" :required="true" bag="password" />
                <button type="submit" class="btn btn-ink">Change password</button>
            </form>
        </section>
    </div>
</x-account.shell>
