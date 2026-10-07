<?php

namespace App\Settings;

use Illuminate\Support\Facades\Storage;
use Spatie\LaravelSettings\Settings;

/** Warranty claims — Admin → Settings → Warranty. Used by App\Services\WarrantyDesk and App\Support\WarrantySheet. */
class WarrantySettings extends Settings
{
    /** The "Warranty status" page (/warranty) and its links in the footer and mobile menu. */
    public bool $lookup_enabled;

    /** Email customers (who gave an email) when their claim's status or note changes. */
    public bool $emails_enabled;

    /** Text under the heading of the warranty status page. */
    public string $page_intro;

    /** Banner across the top of the Excel sheet (public disk path); empty = the built-in TechnoFix banner. */
    public ?string $sheet_banner;

    public static function group(): string
    {
        return 'warranty';
    }

    /** Local file of the Excel banner (PNG/JPG). */
    public function bannerPath(): ?string
    {
        if ($this->sheet_banner && Storage::disk('public')->exists($this->sheet_banner)) {
            return Storage::disk('public')->path($this->sheet_banner);
        }

        $default = resource_path('images/warranty-sheet-banner.png');

        return is_file($default) ? $default : null;
    }
}
