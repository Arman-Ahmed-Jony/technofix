<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Default "Buying guide" box for category and category + brand pages — edited in Admin → Content → Buying guide.
 * "{name}" is replaced with the page's name ("Laptop", "Dell Laptop"). Categories and category + brand pages can
 * have their own text and questions, which replace these.
 */
class BuyingGuideSettings extends Settings
{
    public string $text;

    // list of ['question' => string, 'answer' => string]
    public array $faqs;

    public static function group(): string
    {
        return 'buying_guide';
    }

    /** Swap in the page name. */
    public static function withName(?string $template, string $name): string
    {
        return str_replace('{name}', $name, (string) $template);
    }
}
