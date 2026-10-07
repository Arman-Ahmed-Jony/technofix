<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Search engine and sharing texts — edited in Admin → Settings → SEO. Read through App\Support\Seo.
 */
class SeoSettings extends Settings
{
    /** Shown after every page title ("… | TechnoFix"), on link previews and in Google's business details. */
    public string $site_name;

    public string $home_title;

    public string $home_description;

    /**
     * Templates for pages without their own SEO title / description. Placeholders: see Seo::TOKENS.
     */
    public string $product_title;

    public string $product_description;

    public string $category_title;

    public string $category_description;

    public string $brand_title;

    public string $brand_description;

    public string $category_brand_title;

    public string $category_brand_description;

    /** Fixed pages (Seo::PAGES): key => ['title' => ?, 'description' => ?]; empty = the built-in text. */
    public array $pages;

    /** Picture for link previews (Facebook, WhatsApp) on pages without their own — path on the public disk. */
    public ?string $share_image;

    /** schema.org type for Google's business details, e.g. ElectronicsStore (Seo::BUSINESS_TYPES). */
    public string $business_type;

    /** Let AI assistants' crawlers (ChatGPT, Claude, Perplexity, Gemini…) read the site — else blocked in robots.txt. */
    public bool $allow_ai_crawlers;

    /** Extra lines added to robots.txt (advanced). */
    public ?string $robots_extra;

    /** Serve /llms.txt (a plain-text summary of the shop for AI assistants, see App\Support\LlmsTxt). */
    public bool $llms_enabled;

    /** The summary at the top of llms.txt. */
    public string $llms_intro;

    public static function group(): string
    {
        return 'seo';
    }
}
