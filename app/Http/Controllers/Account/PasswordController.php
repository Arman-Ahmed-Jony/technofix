<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Turnstile;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * "Forgot password" by email. Customers without an email ask the shop (staff can set a new password in the admin);
 * SMS reset comes with the SMS provider.
 */
class PasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('account.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email'], ...Turnstile::rules('password')], ['email.required' => 'Enter the email on your account.', ...Turnstile::messages()]);

        Password::broker('customers')->sendResetLink(['email' => Str::lower($request->input('email'))]);

        // Same message whether or not the email exists, so the form can't be used to find accounts.
        return back()->with('status', 'If an account uses that email, we’ve sent a link to reset your password. Check your inbox (and spam).');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('account.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($customer));
                Auth::guard('customer')->login($customer);
            },
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('account')->with('status', 'Your password was changed.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Please ask for a new one.']);
    }
}
