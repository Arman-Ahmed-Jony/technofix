<?php

namespace App\Support;

/**
 * Colour themes for brand "Exclusive Zone" cards and brand-store headers. The owner picks one per brand
 * in the admin; the classes live here so Tailwind sees them.
 */
class ZoneThemes
{
    public const THEMES = [
        'emerald' => [
            'label' => 'Green',
            'bg' => 'bg-[linear-gradient(125deg,#032a1d_0%,#07583b_48%,#13a466_100%)]',
            'glow' => 'bg-emerald-300/40',
            'accent' => 'text-emerald-700',
            'icons' => ['plug-connected', 'usb'],
        ],
        'violet' => [
            'label' => 'Violet',
            'bg' => 'bg-[linear-gradient(125deg,#140f3d_0%,#332891_50%,#6c5cf2_100%)]',
            'glow' => 'bg-violet-300/40',
            'accent' => 'text-violet-700',
            'icons' => ['arrows-exchange', 'device-desktop'],
        ],
        'orange' => [
            'label' => 'Orange',
            'bg' => 'bg-[linear-gradient(125deg,#2b0f04_0%,#8a2d0b_50%,#f3741d_100%)]',
            'glow' => 'bg-amber-300/40',
            'accent' => 'text-orange-700',
            'icons' => ['arrows-split', 'device-tv'],
        ],
        'blue' => [
            'label' => 'Blue',
            'bg' => 'bg-[linear-gradient(125deg,#031a33_0%,#0b4f8a_50%,#1d9be0_100%)]',
            'glow' => 'bg-sky-300/40',
            'accent' => 'text-sky-700',
            'icons' => ['router', 'wifi'],
        ],
        'rose' => [
            'label' => 'Red',
            'bg' => 'bg-[linear-gradient(125deg,#2d0610_0%,#8c1230_50%,#ef3f5f_100%)]',
            'glow' => 'bg-rose-300/40',
            'accent' => 'text-rose-700',
            'icons' => ['headphones', 'device-gamepad-2'],
        ],
        // Light card: white with a soft shadow and border (dark text), for brands whose own colours are busy.
        'white' => [
            'label' => 'White (light card)',
            'bg' => 'bg-white dark:bg-card',
            'glow' => 'bg-brand-400/15',
            'accent' => 'text-white',
            'icons' => ['sparkles', 'shield-check'],
            'light' => true,
        ],
        'navy' => [
            'label' => 'Navy',
            'bg' => 'bg-[linear-gradient(125deg,#020d1a_0%,#062c47_50%,#0c5f8f_100%)]',
            'glow' => 'bg-brand-400/30',
            'accent' => 'text-navy-900',
            'icons' => ['cpu', 'device-laptop'],
        ],
    ];

    /**
     * @return array{label: string, bg: string, glow: string, accent: string, icons: array{string, string}, light: bool}
     */
    public static function get(?string $key): array
    {
        return ['light' => false, ...(self::THEMES[$key] ?? self::THEMES['navy'])];
    }

    /**
     * @return array<string, string> key => label, for admin selects
     */
    public static function options(): array
    {
        return array_map(fn (array $theme) => $theme['label'], self::THEMES);
    }
}
