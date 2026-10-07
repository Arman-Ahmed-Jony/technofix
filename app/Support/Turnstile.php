<?php

namespace App\Support;

use App\Settings\SecuritySettings;
use Closure;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare Turnstile — a privacy-friendly "I'm not a robot" check (usually invisible) on public forms.
 * Admin → Settings → Security: keys + which forms. Off, or without keys, every form works as before.
 *
 * Views: <x-turnstile form="checkout" /> inside the <form>. Validation: ...Turnstile::rules('checkout').
 */
class Turnstile
{
    public const FIELD = 'cf-turnstile-response';

    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** Forms that can be protected (key => label in the admin). */
    public const FORMS = [
        'checkout' => 'Checkout (place order)',
        'enquiry' => 'Enquiry forms (contact, quote, site visit, product price / stock alerts)',
        'pc_builder' => 'PC Builder — send build to our team',
        'register' => 'Create account',
        'login' => 'Customer sign-in',
        'password' => 'Forgot password',
        'track_order' => 'Track order',
        'warranty' => 'Warranty status check',
    ];

    public const MESSAGE = 'Please wait a moment while we check you’re not a robot, then try again.';

    public static function enabled(string $form): bool
    {
        $settings = rescue(fn () => app(SecuritySettings::class), null, report: false);

        return $settings
            && $settings->turnstile_enabled
            && filled($settings->turnstile_site_key)
            && filled($settings->turnstile_secret_key)
            && in_array($form, $settings->turnstile_forms, true);
    }

    public static function siteKey(): ?string
    {
        return app(SecuritySettings::class)->turnstile_site_key;
    }

    /** Validation rules to merge into a form's rules (empty when the form isn't protected). */
    public static function rules(string $form): array
    {
        if (! self::enabled($form)) {
            return [];
        }

        return [self::FIELD => ['bail', 'required', 'string', 'max:2048', function (string $attribute, mixed $value, Closure $fail) use ($form) {
            if (! self::verify((string) $value, $form)) {
                $fail(self::MESSAGE);
            }
        }]];
    }

    /** Validation messages for the field. */
    public static function messages(): array
    {
        return [self::FIELD.'.required' => self::MESSAGE];
    }

    /**
     * Ask Cloudflare whether the token is genuine. If Cloudflare can't be reached the form goes through (a lost order
     * costs more than one spam message) and the problem is reported in the log.
     */
    public static function verify(string $token, string $form): bool
    {
        try {
            $response = Http::asForm()->timeout(6)->post(self::VERIFY_URL, [
                'secret' => app(SecuritySettings::class)->turnstile_secret_key,
                'response' => $token,
                'remoteip' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return true;
        }

        if (! $response->successful()) {
            report(new \RuntimeException('Turnstile verification failed with HTTP '.$response->status()));

            return true;
        }

        if (! $response->json('success')) {
            return false;
        }

        // The widget tells Cloudflare which form it was on; a token from another form doesn't count.
        $action = $response->json('action');

        return blank($action) || $action === $form;
    }
}
