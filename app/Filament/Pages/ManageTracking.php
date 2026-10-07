<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\TrackingSettings;
use App\Support\Tracking;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class ManageTracking extends SettingsPage
{
    protected static string $settings = TrackingSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Tracking & pixels';

    protected static ?string $title = 'Tracking & pixels';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'tracking';

    public function getSubheading(): ?string
    {
        return 'Fill in only what you use. The shop sends product views, add to cart, checkout, purchases, enquiries, sign-ups, searches and call / WhatsApp clicks to each of them.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Google')
                ->columns(2)
                ->columnSpanFull()
                ->description('Use Tag Manager if you have a container (and set up GA4 inside it). Enter the GA4 ID here only if you don’t use Tag Manager for GA4 — otherwise visits are counted twice.')
                ->schema([
                    self::idField('gtm_id', 'Google Tag Manager container ID', 'GTM-XXXXXXX', '/^GTM-[A-Z0-9]{4,12}$/', 'Looks like GTM-5K2ABCD.'),
                    self::idField('ga4_id', 'Google Analytics 4 measurement ID', 'G-XXXXXXXXXX', '/^G-[A-Z0-9]{6,14}$/', 'Looks like G-1AB2CD3EF4.'),
                ]),
            Section::make('Meta (Facebook & Instagram)')
                ->columns(2)
                ->columnSpanFull()
                ->description(new HtmlString('Events Manager → your data set (pixel). With the Conversions API token, purchases, enquiries and sign-ups are also sent from the server — they still count when a browser blocks the pixel, and Meta removes the duplicates.'))
                ->schema([
                    self::idField('meta_pixel_id', 'Pixel (data set) ID', '123456789012345', '/^\d{10,20}$/', 'Only digits, e.g. 123456789012345.'),
                    TextInput::make('meta_capi_token')
                        ->label('Conversions API access token')
                        ->password()
                        ->revealable()
                        ->maxLength(500)
                        ->helperText('Events Manager → Settings → Conversions API → Generate access token. Stored encrypted.'),
                    TextInput::make('meta_test_event_code')
                        ->label('Test event code (only while testing)')
                        ->placeholder('TEST12345')
                        ->maxLength(40)
                        ->helperText('From Events Manager → Test events. Server events then show up there. Remove it when you’re done.'),
                ]),
            Section::make('TikTok')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    self::idField('tiktok_pixel_id', 'TikTok pixel ID', 'C1A2B3C4D5E6F7G8H9I0', '/^[A-Z0-9]{15,30}$/', 'TikTok Ads Manager → Assets → Events → Web events.'),
                ]),
            Section::make('Page speed')
                ->columnSpanFull()
                ->description('These scripts are the heaviest thing on a page. Loading them after the page keeps it fast; events are never lost, only sent a moment later.')
                ->schema([
                    Radio::make('load_scripts')->hiddenLabel()->options(Tracking::LOAD_OPTIONS)->required(),
                ]),
            Section::make('Other code (advanced)')
                ->columnSpanFull()
                ->collapsed()
                ->description('Verification tags or other snippets someone asks you to add. Code here runs on every page — only paste code from a source you trust.')
                ->schema([
                    Textarea::make('head_code')->label('Inside <head>')->rows(4)->extraInputAttributes(['class' => 'font-mono text-xs']),
                    Textarea::make('body_code')->label('End of <body>')->rows(4)->extraInputAttributes(['class' => 'font-mono text-xs']),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMeta')
                ->label('Send a test event to Meta')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->visible(fn () => app(Tracking::class)->capiEnabled())
                ->requiresConfirmation()
                ->modalDescription('Sends one "PageView" test event through the Conversions API right now, using the saved pixel ID and token. Add a test event code first to see it in Events Manager → Test events.')
                ->action(fn () => $this->sendTestEvent()),
        ];
    }

    protected function sendTestEvent(): void
    {
        $settings = app(TrackingSettings::class);

        try {
            $response = Http::asJson()->timeout(15)->post(
                'https://graph.facebook.com/'.Tracking::GRAPH_VERSION.'/'.$settings->meta_pixel_id.'/events',
                array_filter([
                    'data' => [[
                        'event_name' => 'PageView',
                        'event_time' => now()->timestamp,
                        'event_id' => 'test-'.Str::random(10),
                        'action_source' => 'website',
                        'event_source_url' => url('/'),
                        'user_data' => ['client_ip_address' => request()->ip(), 'client_user_agent' => (string) request()->userAgent()],
                    ]],
                    'access_token' => $settings->meta_capi_token,
                    'test_event_code' => $settings->meta_test_event_code ?: null,
                ]),
            );
        } catch (\Throwable $e) {
            Notification::make()->danger()->title('Couldn’t reach Meta')->body($e->getMessage())->send();

            return;
        }

        $response->successful()
            ? Notification::make()->success()->title('Meta received the event')->body($settings->meta_test_event_code ? 'Look for it in Events Manager → Test events.' : 'Add a test event code to see test events in Events Manager.')->send()
            : Notification::make()->danger()->title('Meta refused the event')->body($response->json('error.message') ?? $response->body())->persistent()->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['gtm_id', 'ga4_id', 'tiktok_pixel_id'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? Str::upper(trim($data[$key])) : null;
        }

        foreach (['meta_pixel_id', 'meta_capi_token', 'meta_test_event_code'] as $key) {
            $data[$key] = filled($data[$key] ?? null) ? trim($data[$key]) : null;
        }

        return $data;
    }

    protected static function idField(string $name, string $label, string $placeholder, string $pattern, string $hint): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->placeholder($placeholder)
            ->maxLength(40)
            ->extraInputAttributes(['class' => 'font-mono'])
            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Str::upper(trim($state)) : null)
            ->rule(fn () => function (string $attribute, $value, \Closure $fail) use ($pattern, $hint) {
                if (filled($value) && ! preg_match($pattern, Str::upper(trim($value)))) {
                    $fail('That doesn’t look right. '.$hint);
                }
            })
            ->helperText($hint);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Seo->allowed();
    }
}
