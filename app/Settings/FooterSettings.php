<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

/**
 * The storefront footer — Admin → Content → Footer. Links come from the menu, pages and services; contact details from
 * Store & contact.
 */
class FooterSettings extends Settings
{
    public ?string $about;

    /** Promise badges above the footer: [{icon, title, text}]. */
    public array $badges;

    /** "Talk to an expert" banner above the footer. */
    public bool $cta_enabled;

    public ?string $cta_eyebrow;

    public ?string $cta_title;

    public ?string $cta_highlight;

    public ?string $cta_text;

    /** {year} = the current year. */
    public string $copyright;

    /** Facebook card in the footer (cover photo background, logo, name; links to the page). */
    public bool $facebook_box;

    /** The page's real address (not a /share/ link); empty = the Facebook link in Store & contact. */
    public ?string $facebook_page_url;

    /** Uploaded cover photo used as the card's background (public disk path). */
    public ?string $facebook_cover;

    public function facebookCoverUrl(): ?string
    {
        return $this->facebook_cover && Storage::disk('public')->exists($this->facebook_cover)
            ? Storage::disk('public')->url($this->facebook_cover)
            : null;
    }

    public static function group(): string
    {
        return 'footer';
    }

    /** The page address for the Facebook box, or null when it's switched off or there is no usable address. */
    public function facebookBoxUrl(): ?string
    {
        $url = trim((string) ($this->facebook_page_url ?: app(StoreSettings::class)->facebook));

        return $this->facebook_box && self::isFacebookPage($url) ? $url : null;
    }

    /** A facebook.com page address the box can show (share links and other sites can't be used). */
    public static function isFacebookPage(?string $url): bool
    {
        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));

        return in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com'], true)
            && ! str_contains((string) $url, '/share/');
    }

    public function copyrightLine(): string
    {
        return str_replace('{year}', date('Y'), $this->copyright);
    }
}
