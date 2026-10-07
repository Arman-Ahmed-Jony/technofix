<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Logos and icon — Admin → Settings → Branding. Paths on the public disk; empty = the built-in files. Read through
 * App\Support\Brand.
 */
class BrandSettings extends Settings
{
    /** For light backgrounds (header, admin, invoices). */
    public ?string $logo;

    /** For dark backgrounds (dark mode, footer, maintenance page). */
    public ?string $logo_dark;

    /** Square icon: browser tab, phone home screen, Google. */
    public ?string $icon;

    /** PNG/JPG logo for emails and PDF quotes (email apps don't show SVG). */
    public ?string $email_logo;

    public static function group(): string
    {
        return 'brand';
    }
}
