<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The "Download software" button (drivers / tools for customers) — Admin → Content → Software download.
 * Shown as a glowing button: <x-download-button /> (Repair page, footer) and a link in the top bar / mobile menu.
 */
class DownloadSettings extends Settings
{
    public bool $enabled;

    /** Button text. */
    public string $label;

    /** Where the button goes (e.g. a Google Drive folder). Opens in a new tab. */
    public ?string $url;

    /** Line next to the button on the Repair page. */
    public ?string $title;

    public ?string $text;

    /** Top bar on computers + the mobile menu. */
    public bool $show_top_bar;

    public bool $show_services;

    public bool $show_footer;

    public static function group(): string
    {
        return 'download';
    }

    /** Whether the button shows in a place: top_bar | services | footer. */
    public function shows(string $place): bool
    {
        return $this->enabled && filled($this->url) && (bool) ($this->{'show_'.$place} ?? false);
    }
}
