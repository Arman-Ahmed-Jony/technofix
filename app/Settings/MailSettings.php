<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Email sending — Admin → Settings → Email sending. When on, it replaces the .env mail settings (App\Support\MailSetup).
 */
class MailSettings extends Settings
{
    public bool $enabled;

    public ?string $host;

    public int $port;

    /** tls (STARTTLS, usually port 587) | ssl (usually 465) | none */
    public string $encryption;

    public ?string $username;

    public ?string $password;

    public ?string $from_address;

    public ?string $from_name;

    public static function group(): string
    {
        return 'mail';
    }

    public static function encrypted(): array
    {
        return ['password'];
    }
}
