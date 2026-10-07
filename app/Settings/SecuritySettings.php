<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Spam and fraud protection — Admin → Settings → Security. Used through App\Support\Turnstile and App\Support\Blocklist.
 */
class SecuritySettings extends Settings
{
    /** Cloudflare Turnstile ("I'm not a robot" check) on public forms. */
    public bool $turnstile_enabled;

    public ?string $turnstile_site_key;

    /** Stored encrypted. */
    public ?string $turnstile_secret_key;

    /** Keys of App\Support\Turnstile::FORMS. */
    public array $turnstile_forms;

    /** Rows of phone + note. */
    public array $blocked_phones;

    /** Rows of ip + note: single addresses or ranges (203.0.113.0/24). */
    public array $blocked_ips;

    /** Shown when a blocked number or address tries to order or send a form ({hotline} = the hotline). */
    public string $blocked_message;

    public static function group(): string
    {
        return 'security';
    }

    public static function encrypted(): array
    {
        return ['turnstile_secret_key'];
    }
}
