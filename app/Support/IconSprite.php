<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Collects the Tabler icons used while rendering a page and prints each one
 * once as an SVG <symbol>. Icons on the page reference them with <use>, which
 * keeps the HTML small no matter how many times an icon is repeated.
 */
class IconSprite
{
    /** @var array<string, array{attributes: string, body: string}> */
    protected array $used = [];

    /** @var array<string, array{attributes: string, body: string}> */
    protected static array $parsed = [];

    /**
     * Register an icon and return the presentation attributes for its <svg> wrapper.
     */
    public function use(string $name): string
    {
        return ($this->used[$name] ??= static::parse($name))['attributes'];
    }

    public function render(): string
    {
        if ($this->used === []) {
            return '';
        }

        $symbols = collect($this->used)
            ->map(fn (array $icon, string $name) => '<symbol id="i-'.$name.'" viewBox="0 0 24 24">'.$icon['body'].'</symbol>')
            ->join('');

        // Not display:none — browsers drop gradients defined inside hidden SVGs.
        // #tf-grad / #tf-grad-accent can be used as `stroke: url(#tf-grad)` on any icon.
        $defs = '<defs>'
            .'<linearGradient id="tf-grad" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#26abe6"/><stop offset="1" stop-color="#1d71b8"/></linearGradient>'
            .'<linearGradient id="tf-grad-accent" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#f7a01f"/><stop offset="1" stop-color="#e94e1b"/></linearGradient>'
            .'</defs>';

        return '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" style="position:absolute;width:0;height:0;overflow:hidden">'
            .$defs.$symbols.'</svg>';
    }

    /** Whether a Tabler icon with this name exists (used to validate icon fields in the admin). */
    public static function exists(string $name): bool
    {
        return (bool) preg_match('/^[a-z0-9-]+$/', $name) && is_file(static::path($name));
    }

    protected static function path(string $name): string
    {
        return base_path("vendor/secondnetwork/blade-tabler-icons/resources/svg/{$name}.svg");
    }

    /**
     * @return array{attributes: string, body: string}
     */
    protected static function parse(string $name): array
    {
        if (isset(static::$parsed[$name])) {
            return static::$parsed[$name];
        }

        if (! static::exists($name)) {
            // A mistyped icon must not take the shop down: fall back in production, fail loudly elsewhere.
            if (app()->isProduction() && $name !== 'package') {
                report(new RuntimeException("Unknown icon [{$name}]."));

                return static::$parsed[$name] = static::parse('package');
            }

            throw new RuntimeException("Unknown icon [{$name}].");
        }

        $svg = file_get_contents(static::path($name));
        preg_match('/<svg([^>]*)>(.*)<\/svg>/s', $svg, $m);

        // Stroke/fill presentation attributes move to the outer <svg> so CSS
        // (e.g. .tf-icon stroke-width) can override them through inheritance.
        preg_match_all('/\s(fill|stroke|stroke-width|stroke-linecap|stroke-linejoin)="([^"]*)"/', $m[1], $attrs, PREG_SET_ORDER);

        $body = preg_replace('/<path stroke="none" d="M0 0h24v24H0z" fill="none"\s*\/>/', '', $m[2]);
        $body = trim(preg_replace('/\s+/', ' ', Str::replace(["\n", "\r"], ' ', $body)));
        $body = preg_replace('/>\s+</', '><', $body);

        return static::$parsed[$name] = [
            'attributes' => collect($attrs)->map(fn (array $a) => $a[1].'="'.$a[2].'"')->join(' '),
            'body' => $body,
        ];
    }
}
