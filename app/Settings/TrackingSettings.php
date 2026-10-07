<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Analytics and ad pixels — Admin → Settings → Tracking & pixels. Used through App\Support\Tracking.
 */
class TrackingSettings extends Settings
{
    /** Google Tag Manager container, "GTM-XXXXXXX". */
    public ?string $gtm_id;

    /** Google Analytics 4 measurement ID, "G-XXXXXXXXXX" (only when GA4 isn't set up inside GTM). */
    public ?string $ga4_id;

    /** Meta (Facebook / Instagram) pixel ID. */
    public ?string $meta_pixel_id;

    /** Meta Conversions API access token (stored encrypted). */
    public ?string $meta_capi_token;

    /** Events Manager → Test events code; remove after testing. */
    public ?string $meta_test_event_code;

    public ?string $tiktok_pixel_id;

    /** When third-party scripts load: immediately | after_load | on_interaction (Tracking::LOAD_OPTIONS). */
    public string $load_scripts;

    /** Extra HTML at the end of <head> / <body> (verification tags, chat widgets…). */
    public ?string $head_code;

    public ?string $body_code;

    public static function group(): string
    {
        return 'tracking';
    }

    public static function encrypted(): array
    {
        return ['meta_capi_token'];
    }
}
