<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The dark bar above the header on computers — Admin → Content → Top bar. The hotline and opening hours themselves
 * come from StoreSettings, the "Download software" link from DownloadSettings.
 */
class TopBarSettings extends Settings
{
    public bool $show_hotline;

    public bool $show_hours;

    /** Short message on the left (empty = none); shown on wide screens only. */
    public ?string $message;

    /** Tabler icon in front of the message. */
    public ?string $message_icon;

    /** Rows of label + url: "/page" on this site or a full https:// address. */
    public array $links;

    public static function group(): string
    {
        return 'top_bar';
    }

    /**
     * Links ready for the view.
     *
     * @return array<int, array{label: string, url: string, external: bool}>
     */
    public function navLinks(): array
    {
        return collect($this->links)
            ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->map(function (array $link) {
                $url = trim($link['url']);
                $external = (bool) preg_match('#^https?://#i', $url) && parse_url($url, PHP_URL_HOST) !== request()->getHost();

                return ['label' => $link['label'], 'url' => str_starts_with($url, '/') ? url($url) : $url, 'external' => $external];
            })
            ->values()
            ->all();
    }
}
