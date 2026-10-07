<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\SecuritySettings;
use App\Support\Phone;
use App\Support\Turnstile;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\IpUtils;
use Throwable;
use UnitEnum;

/** Spam and fake-order protection: Cloudflare Turnstile on public forms and a blocklist of numbers / addresses. */
class ManageSecurity extends SettingsPage
{
    protected static string $settings = SecuritySettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Security & spam';

    protected static ?string $title = 'Security & spam';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'security';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'Stop bots and fake orders. Every form already has a hidden bot trap and a limit on how often it can be sent; these add more.';
    }

    public function form(Schema $schema): Schema
    {
        $on = fn (Get $get) => (bool) $get('turnstile_enabled');

        return $schema->components([
            Section::make('Robot check on forms (Cloudflare Turnstile)')
                ->columns(2)
                ->columnSpanFull()
                ->description(new HtmlString('Free from Cloudflare and usually invisible — real customers don’t have to solve puzzles. Get the keys at '
                    .'<a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank" rel="noopener" class="text-primary-600 underline">Cloudflare → Turnstile</a> → '
                    .'Add widget: domain <b>'.e(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'technofixbd.com').'</b>, mode <b>Managed</b>.'))
                ->schema([
                    Toggle::make('turnstile_enabled')->label('Check forms for robots')->live()->columnSpanFull(),
                    TextInput::make('turnstile_site_key')->label('Site key')->placeholder('0x4AAAAAAA…')->maxLength(100)
                        ->required($on)->visible($on),
                    TextInput::make('turnstile_secret_key')->label('Secret key')->password()->revealable()->maxLength(100)
                        ->required($on)->visible($on)->helperText('Stored encrypted.'),
                    CheckboxList::make('turnstile_forms')->label('Forms to protect')->options(Turnstile::FORMS)
                        ->columns(2)->columnSpanFull()->visible($on)->required($on)->bulkToggleable(),
                ]),

            Section::make('Blocked mobile numbers')
                ->columnSpanFull()
                ->description('Numbers behind fake or refused orders. They can’t place orders, send enquiries or create an account; they see the message below. Staff can still take their orders by phone. Tip: use “Block this number” on an order or lead.')
                ->schema([
                    Repeater::make('blocked_phones')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('phone')->label('Mobile number')->required()->maxLength(20)->placeholder('01712345678')
                                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                                    if (! Phone::isValid((string) $value)) {
                                        $fail('Enter a Bangladeshi mobile number, e.g. 01712345678.');
                                    }
                                }),
                            TextInput::make('note')->label('Why (for staff)')->maxLength(120)->placeholder('3 fake orders, refused delivery'),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->collapsible(false)
                        ->addActionLabel('Block a number'),
                ]),

            Section::make('Blocked internet addresses')
                ->columnSpanFull()
                ->collapsed(fn (Get $get) => blank($get('blocked_ips')))
                ->description('For a bot or attacker you can see in the logs. Many mobile users share one address, so block an address only for clear abuse, and remove it again later. Blocked addresses can still browse.')
                ->schema([
                    Repeater::make('blocked_ips')
                        ->hiddenLabel()
                        ->schema([
                            TextInput::make('ip')->label('Address or range')->required()->maxLength(50)->placeholder('203.0.113.7 or 203.0.113.0/24')
                                ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                                    if (! self::validAddress(trim((string) $value))) {
                                        $fail('Enter an address like 203.0.113.7 or a range like 203.0.113.0/24.');
                                    }
                                }),
                            TextInput::make('note')->label('Why (for staff)')->maxLength(120),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Block an address'),
                ]),

            Section::make('Message for blocked visitors')
                ->columnSpanFull()
                ->schema([
                    Textarea::make('blocked_message')->hiddenLabel()->required()->rows(2)->maxLength(300)
                        ->helperText('{hotline} becomes your hotline number. Keep it polite — a real customer may share a number or an address.'),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['blocked_phones'] = collect($data['blocked_phones'] ?? [])
            ->map(fn (array $row) => ['phone' => Phone::normalize($row['phone'] ?? ''), 'note' => $row['note'] ?? null])
            ->unique('phone')->values()->all();
        $data['blocked_ips'] = collect($data['blocked_ips'] ?? [])
            ->map(fn (array $row) => ['ip' => trim((string) ($row['ip'] ?? '')), 'note' => $row['note'] ?? null])
            ->unique('ip')->values()->all();

        return $data;
    }

    public static function validAddress(string $value): bool
    {
        [$ip, $bits] = array_pad(explode('/', $value, 2), 2, null);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $max = str_contains($ip, ':') ? 128 : 32;

        return $bits === null || (ctype_digit($bits) && (int) $bits >= ($max === 32 ? 8 : 16) && (int) $bits <= $max && IpUtils::checkIp($ip, $value));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('checkKeys')
                ->label('Check the secret key')
                ->icon('heroicon-o-key')
                ->color('gray')
                ->visible(fn () => filled(app(SecuritySettings::class)->turnstile_secret_key))
                ->modalDescription('Asks Cloudflare whether the saved secret key is valid. Save your changes first.')
                ->action(function () {
                    try {
                        // A made-up token: Cloudflare then says either "bad secret" or just "bad token".
                        $codes = (array) Http::asForm()->timeout(8)->post(Turnstile::VERIFY_URL, [
                            'secret' => app(SecuritySettings::class)->turnstile_secret_key,
                            'response' => 'technofix-key-check',
                        ])->json('error-codes', []);
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Couldn’t reach Cloudflare')->body($e->getMessage())->send();

                        return;
                    }

                    in_array('invalid-input-secret', $codes, true)
                        ? Notification::make()->danger()->title('The secret key is wrong')->body('Copy it again from Cloudflare → Turnstile → your widget → Settings.')->persistent()->send()
                        : Notification::make()->success()->title('The secret key works')->send();
                }),
        ];
    }
}
