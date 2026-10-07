<?php

namespace App\Support;

use App\Settings\BrandSettings;
use Illuminate\Support\Facades\Storage;

/**
 * The shop's logos and icon (Admin → Settings → Branding), falling back to the built-in files in public/images/brand.
 */
class Brand
{
    public static function logo(): string
    {
        return self::url('logo', 'images/brand/logo.svg');
    }

    public static function logoDark(): string
    {
        return self::url('logo_dark', 'images/brand/logo-dark.svg');
    }

    public static function icon(): string
    {
        return self::url('icon', 'images/brand/favicon.png');
    }

    /** Large square icon (Google business details, phone home screen). */
    public static function appIcon(): string
    {
        return self::url('icon', 'images/brand/icon.png');
    }

    public static function emailLogo(): string
    {
        return self::url('email_logo', 'images/brand/logo-email.png');
    }

    /** The email logo as a data URI, for PDFs (dompdf needs the image inline). */
    public static function emailLogoData(): string
    {
        $path = self::file('email_logo') ?? public_path('images/brand/logo-email.png');
        $type = str_ends_with(strtolower($path), '.jpg') || str_ends_with(strtolower($path), '.jpeg') ? 'jpeg' : 'png';

        return 'data:image/'.$type.';base64,'.base64_encode((string) file_get_contents($path));
    }

    private static function url(string $key, string $fallback): string
    {
        $path = self::setting($key);

        return $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->url($path) : asset($fallback);
    }

    private static function file(string $key): ?string
    {
        $path = self::setting($key);

        return $path && Storage::disk('public')->exists($path) ? Storage::disk('public')->path($path) : null;
    }

    private static function setting(string $key): ?string
    {
        return rescue(fn () => app(BrandSettings::class)->{$key}, null, report: false);
    }
}
