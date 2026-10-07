<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Blocklist;
use App\Support\Phone;
use App\Support\Turnstile;
use App\Support\Tracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Customer sign-in (mobile number or email + password), registration and sign-out.
 */
class AuthController extends Controller
{
    /** Pages a sign-in link can send the customer back to (`?to=`), by route name. */
    public const RETURN_TO = ['checkout' => 'checkout', 'pc-builder' => 'pc-builder'];

    public function loginForm(Request $request): View
    {
        $this->rememberReturn($request);

        return view('account.auth.login');
    }

    /**
     * "Sign in to write a review" links pass ?back=/product-page#reviews: after signing in (or creating an account)
     * the customer lands there again. Only addresses on this site are accepted.
     */
    protected function rememberReturn(Request $request): void
    {
        $back = (string) $request->query('back', '');

        if (str_starts_with($back, '/') && ! str_starts_with($back, '//') && ! str_contains($back, '\\')) {
            $request->session()->put('url.intended', url($back));
        }
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(
            ['login' => ['required', 'string', 'max:120'], 'password' => ['required', 'string'], ...Turnstile::rules('login')],
            ['login.required' => 'Enter your mobile number or email.', 'password.required' => 'Enter your password.', ...Turnstile::messages()],
        );

        $login = trim($request->input('login'));
        $field = str_contains($login, '@') ? 'email' : 'phone';
        $value = $field === 'email' ? Str::lower($login) : Phone::normalize($login);
        $key = 'customer-login:'.$value.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.']);
        }

        if (! Auth::guard('customer')->attempt([$field => $value, 'password' => $request->input('password'), 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['login' => 'That mobile number / email and password don’t match an account.']);
        }

        RateLimiter::clear($key);

        return $this->signedIn($request, Auth::guard('customer')->user());
    }

    /** Right after a guest checkout, the form starts with the details from that order. */
    public function registerForm(Request $request): View
    {
        $this->rememberReturn($request);
        $order = Order::find($request->session()->get('last_order_id'));

        return view('account.auth.register', [
            'prefill' => $order ? $order->only('name', 'phone', 'email') : [],
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')), 'email' => Str::lower(trim((string) $request->input('email'))) ?: null]);

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'phone' => ['required', 'regex:'.Phone::PATTERN, Blocklist::rule(), Rule::unique('customers', 'phone')],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('customers', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
            ...Turnstile::rules('register'),
        ], [
            ...Turnstile::messages(),
            'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.',
            'phone.unique' => 'An account already uses this mobile number. Sign in instead, or reset your password.',
            'email.unique' => 'An account already uses this email. Sign in instead.',
        ]);

        $customer = Customer::create($data);
        Auth::guard('customer')->login($customer, remember: true);
        app(Tracking::class)->signUp($customer, $request);

        return $this->signedIn($request, $customer);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        // Keep the session (a staff member may be signed in to the admin in the same browser); just rotate its token.
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function signedIn(Request $request, Customer $customer): RedirectResponse
    {
        $request->session()->regenerate();
        $customer->forceFill(['last_login_at' => now()])->save();

        // Orders this browser just placed or looked up as a guest (and that match the account's mobile) join the account.
        $ids = array_filter([$request->session()->get('last_order_id'), ...(array) $request->session()->get('tracked_orders', [])]);
        if ($ids) {
            Order::whereKey($ids)->whereNull('customer_id')->where('phone', $customer->phone)->update(['customer_id' => $customer->id]);
        }

        // "Sign in for faster checkout" / "sign in to save your build" come back there; otherwise the page that asked
        // for sign-in, or the account.
        $fallback = route(self::RETURN_TO[$request->input('to')] ?? 'account');

        return redirect()->intended($fallback);
    }
}
