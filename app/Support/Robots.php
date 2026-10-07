<?php

namespace App\Support;

use App\Settings\SeoSettings;

/**
 * /robots.txt: private pages stay out of search engines, AI crawlers follow the owner's choice (Admin → Settings →
 * SEO & sharing), and anything but the live site is closed to every crawler.
 */
class Robots
{
    /** Never crawled: personal, per-visitor or endless pages (they are noindex too). The admin path is left out on purpose. */
    public const PRIVATE_PATHS = ['/account', '/cart', '/checkout', '/compare', '/wishlist', '/search', '/login', '/register',
        '/forgot-password', '/reset-password', '/orders/', '/enquiry', '/pc-builder/', '/recently-viewed', '/warranty/status'];

    /** Crawlers of AI assistants and AI search / training, blocked when the owner says so. */
    public const AI_CRAWLERS = ['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai',
        'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'meta-externalagent', 'Amazonbot', 'Bytespider', 'CCBot'];

    public function __construct(private SeoSettings $settings) {}

    public function text(): string
    {
        if (! app()->isProduction()) {
            return "# Not the live site: closed to all crawlers.\nUser-agent: *\nDisallow: /\n";
        }

        $lines = ['User-agent: *', ...array_map(fn (string $path) => "Disallow: {$path}", self::PRIVATE_PATHS), ''];

        if (! $this->settings->allow_ai_crawlers) {
            $lines = [...$lines, '# AI assistants (Admin → Settings → SEO & sharing)', ...array_map(fn (string $bot) => "User-agent: {$bot}", self::AI_CRAWLERS), 'Disallow: /', ''];
        }

        if (filled($extra = trim(str_replace("\r", '', (string) $this->settings->robots_extra)))) {
            $lines = [...$lines, $extra, ''];
        }

        return implode("\n", [...$lines, 'Sitemap: '.route('sitemap')])."\n";
    }
}
