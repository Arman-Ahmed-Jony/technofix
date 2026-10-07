<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\SeoSettings;
use App\Support\LlmsTxt;
use App\Support\Robots;
use App\Support\Seo;
use App\Support\Sitemap;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class ManageSeo extends SettingsPage
{
    protected static string $settings = SeoSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'SEO & sharing';

    protected static ?string $title = 'SEO & sharing';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'seo';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Titles & descriptions')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Site')
                                ->description('What Google shows for the home page. Every other page title ends with “| site name”.')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('site_name')->required()->maxLength(40),
                                    TextInput::make('home_title')->label('Home page title')->required()->maxLength(70)->live(onBlur: true)
                                        ->helperText(fn (?string $state) => new HtmlString(self::length($state, 60, html: true))),
                                    Textarea::make('home_description')->label('Home page description')->required()->rows(2)->maxLength(200)->live(onBlur: true)
                                        ->helperText(fn (?string $state) => new HtmlString(self::length($state, 160, html: true)))
                                        ->columnSpanFull(),
                                ]),
                            Section::make('Templates')
                                ->description(new HtmlString('Used for every product, category and brand page that has no SEO title / description of its own (the SEO tab when editing it). Placeholders in <b>{braces}</b> are filled in for each page; <b>{site}</b> and <b>{year}</b> work everywhere.'))
                                ->schema([
                                    self::template('product', 'Products'),
                                    self::template('category', 'Categories'),
                                    self::template('brand', 'Brands'),
                                    self::template('category_brand', 'A brand in a category (e.g. “Gigabyte Motherboard”)'),
                                ]),
                        ]),
                    Tab::make('Pages')
                        ->icon('heroicon-o-rectangle-stack')
                        ->schema([
                            Section::make('Fixed pages')
                                ->description('Leave a field empty to keep the text shown in grey. Pages, services and the PC Builder have their own SEO fields where you edit them.')
                                ->columns(['default' => 1, 'xl' => 2])
                                ->schema(collect(Seo::PAGES)->map(fn (array $page, string $key) => Fieldset::make($page['label'])
                                    ->columns(1)
                                    ->schema([
                                        TextInput::make("pages.{$key}.title")->label('Title')->placeholder($page['title'])->maxLength(70),
                                        Textarea::make("pages.{$key}.description")->label('Description')->placeholder($page['description'])->rows(2)->maxLength(200),
                                    ]))->values()->all()),
                        ]),
                    Tab::make('Sharing & Google')
                        ->icon('heroicon-o-share')
                        ->schema([
                            Section::make('Link preview picture')
                                ->description('Shown when a link is shared on Facebook, WhatsApp or Messenger. Product pages use the product photo; every other page uses this picture.')
                                ->schema([
                                    FileUpload::make('share_image')
                                        ->hiddenLabel()
                                        ->image()
                                        ->disk('public')
                                        ->directory('seo')
                                        ->visibility('public')
                                        ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                        ->maxSize(2048)
                                        ->imagePreviewHeight('180')
                                        ->helperText(new HtmlString('1200 × 630 px, JPG or PNG. While this is empty, the built-in picture is used:'
                                            .'<img src="'.asset('images/brand/share.jpg').'" alt="" class="mt-2 w-full max-w-sm rounded-lg ring-1 ring-gray-950/10">')),
                                ]),
                            Section::make('Business details for Google')
                                ->description('Name, logo, hotline, email, address and social pages come from Settings → Store & contact.')
                                ->schema([
                                    Select::make('business_type')
                                        ->label('Type of business')
                                        ->options(Seo::BUSINESS_TYPES)
                                        ->selectablePlaceholder(false)
                                        ->required(),
                                ]),
                        ]),
                    Tab::make('Crawlers & AI')
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            Section::make('Sitemap')
                                ->description('The list of pages search engines should know about. It updates itself (within an hour) as products, categories, brands, services and pages change.')
                                ->schema([
                                    Text::make(fn () => self::sitemapSummary()),
                                ]),
                            Section::make('robots.txt')
                                ->description('Tells crawlers what to skip. Cart, checkout, account, search and other private pages are always left out.')
                                ->schema([
                                    Toggle::make('allow_ai_crawlers')
                                        ->label('Let AI assistants read the site')
                                        ->helperText('ChatGPT, Claude, Perplexity, Gemini and others can then use your pages when people ask them where to buy — useful for being recommended. Turn off to block their crawlers. Google and Bing search are not affected.'),
                                    Textarea::make('robots_extra')
                                        ->label('Extra lines (advanced)')
                                        ->rows(3)
                                        ->maxLength(2000)
                                        ->helperText('Added to robots.txt as they are. Leave empty unless an SEO expert asks for something.'),
                                    Text::make(fn () => self::preview(route('robots'), app(Robots::class)->text())),
                                ]),
                            Section::make('llms.txt')
                                ->description('A short plain-text guide to the shop for AI assistants (llmstxt.org): what you sell, delivery and payment, services, policies and contact details — built from your catalog and settings. It’s a new, optional standard: some AI tools read it, Google doesn’t use it. It does no harm.')
                                ->schema([
                                    Toggle::make('llms_enabled')->label('Publish llms.txt')->live(),
                                    Textarea::make('llms_intro')
                                        ->label('Summary of the shop')
                                        ->helperText('The first thing an AI assistant reads about you. Plain sentences: what you sell, where, and what makes you different.')
                                        ->rows(3)
                                        ->maxLength(600)
                                        ->required()
                                        ->visible(fn (Get $get) => $get('llms_enabled')),
                                    Text::make(fn () => new HtmlString('<a href="'.route('llms').'" target="_blank" class="font-medium text-primary-600 underline">View llms.txt</a> — updates within an hour of catalog changes, straight away after saving here.'))
                                        ->visible(fn (Get $get) => $get('llms_enabled')),
                                ]),
                        ]),
                ]),
        ]);
    }

    protected function afterSave(): void
    {
        LlmsTxt::flush();
    }

    protected static function sitemapSummary(): HtmlString
    {
        $counts = app(Sitemap::class)->counts();

        return new HtmlString(sprintf(
            '<p><a href="%1$s" target="_blank" class="font-mono font-medium text-primary-600 underline">%1$s</a></p>'
            .'<p class="mt-2 text-gray-600 dark:text-gray-400">%2$s pages · %3$s categories &amp; brand-in-category pages · %4$s brands · %5$s products</p>'
            .'<p class="mt-2 text-gray-600 dark:text-gray-400">After the site goes live, add this address once in Google Search Console → Sitemaps (and Bing Webmaster Tools). It is also listed in robots.txt, so search engines find it by themselves.</p>',
            e(route('sitemap')),
            number_format($counts['pages']),
            number_format($counts['categories']),
            number_format($counts['brands']),
            number_format($counts['products']),
        ));
    }

    protected static function preview(string $url, string $text): HtmlString
    {
        $note = app()->isProduction() ? '' : '<p class="mb-2 text-warning-600">This isn’t the live site, so robots.txt closes it to every crawler. The live site uses your settings.</p>';

        return new HtmlString($note.'<p class="mb-2 text-sm text-gray-600 dark:text-gray-400">Current file (<a href="'.e($url).'" target="_blank" class="underline">open</a>) — changes show after saving:</p>'
            .'<pre class="overflow-x-auto rounded-lg bg-gray-950 p-4 font-mono text-xs leading-relaxed text-gray-100">'.e($text).'</pre>');
    }

    /** Title + description template for one kind of page, with a live example. */
    protected static function template(string $type, string $label): Fieldset
    {
        $tokens = collect([...Seo::TOKENS[$type], 'site', 'year'])->map(fn (string $t) => '{'.$t.'}')->join(' ');

        return Fieldset::make($label)
            ->columns(1)
            ->schema([
                TextInput::make("{$type}_title")
                    ->label('Title')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->helperText(fn (?string $state) => self::example(app(Seo::class)->title(app(Seo::class)->example($type, (string) $state)), 60, $tokens)),
                Textarea::make("{$type}_description")
                    ->label('Description')
                    ->required()
                    ->rows(2)
                    ->maxLength(300)
                    ->live(onBlur: true)
                    ->helperText(fn (?string $state) => self::example(app(Seo::class)->example($type, (string) $state), 160)),
            ]);
    }

    protected static function example(string $text, int $max, ?string $tokens = null): HtmlString
    {
        return new HtmlString(
            ($tokens ? 'Placeholders: <code>'.e($tokens).'</code><br>' : '')
            .'Example: <span class="text-gray-950 dark:text-white">'.e($text).'</span> '.self::length($text, $max, html: true)
        );
    }

    protected static function length(?string $text, int $max, bool $html = false): string|HtmlString
    {
        $length = Str::length((string) $text);
        $label = $length.' / '.$max.' characters'.($length > $max ? ' — Google may cut the end' : '');

        return $html ? '<span class="'.($length > $max ? 'text-warning-600' : 'text-gray-400').'">('.e($label).')</span>' : $label;
    }

    public static function canAccess(): bool
    {
        return StaffArea::Seo->allowed();
    }
}
