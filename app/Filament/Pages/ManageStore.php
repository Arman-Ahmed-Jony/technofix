<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\StoreSettings;
use BackedEnum;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageStore extends SettingsPage
{
    protected static string $settings = StoreSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Store & contact';

    protected static ?string $title = 'Store & contact';

    protected static ?int $navigationSort = 1;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact details')
                ->description('Shown in the header, footer, product pages, checkout and on every "Call" / "WhatsApp" button.')
                ->columns(2)
                ->schema([
                    TextInput::make('hotline')
                        ->label('Hotline')
                        ->required()
                        ->maxLength(30)
                        ->prefixIcon('heroicon-o-phone')
                        ->placeholder('09613-123456'),
                    TextInput::make('whatsapp')
                        ->label('WhatsApp number')
                        ->tel()
                        ->prefixIcon('heroicon-o-chat-bubble-left-right')
                        ->placeholder('01712345678')
                        ->helperText('Leave empty to hide WhatsApp buttons.')
                        ->formatStateUsing(fn (?string $state) => $state ? '0'.substr($state, 3) : null)
                        ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                            if (filled($value) && ! StoreSettings::normalizeWhatsapp($value)) {
                                $fail('Enter a Bangladeshi mobile number, e.g. 01712345678.');
                            }
                        })
                        ->dehydrateStateUsing(fn (?string $state) => StoreSettings::normalizeWhatsapp($state)),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->prefixIcon('heroicon-o-envelope'),
                    TextInput::make('hours')
                        ->label('Opening hours')
                        ->required()
                        ->maxLength(60)
                        ->prefixIcon('heroicon-o-clock')
                        ->placeholder('Sat – Thu, 10:00 AM – 8:00 PM'),
                    Textarea::make('address')
                        ->label('Store address')
                        ->required()
                        ->rows(2)
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Repeater::make('extra_phones')
                        ->label('More phone numbers')
                        ->helperText('Shown with the hotline in the footer, on the contact page, on invoices and on the maintenance page. Call buttons use the hotline.')
                        ->schema([
                            TextInput::make('label')->required()->maxLength(30)->placeholder('Service centre'),
                            TextInput::make('number')->required()->maxLength(30)->placeholder('01712-345678'),
                        ])
                        ->columns(2)
                        ->maxItems(5)
                        ->defaultItems(0)
                        ->reorderable()
                        ->addActionLabel('Add a phone number')
                        ->columnSpanFull(),
                ]),

            Section::make('Social media')
                ->description('Only filled-in links are shown in the footer.')
                ->columns(2)
                ->schema([
                    TextInput::make('facebook')->url()->prefixIcon('heroicon-o-link')->placeholder('https://facebook.com/…'),
                    TextInput::make('youtube')->url()->prefixIcon('heroicon-o-link')->placeholder('https://youtube.com/@…'),
                    TextInput::make('instagram')->url()->prefixIcon('heroicon-o-link')->placeholder('https://instagram.com/…'),
                    TextInput::make('tiktok')->label('TikTok')->url()->prefixIcon('heroicon-o-link')->placeholder('https://tiktok.com/@…'),
                ]),

            Section::make('On invoices')
                ->description('Printed on invoices (Orders → Print). Trade licence and BIN only show when filled in.')
                ->columns(3)
                ->schema([
                    TextInput::make('legal_name')->label('Registered business name')->required()->maxLength(120),
                    TextInput::make('trade_licence')->label('Trade licence no.')->maxLength(60),
                    TextInput::make('bin')->label('VAT BIN')->maxLength(30),
                    Textarea::make('invoice_footer')->label('Closing text')->rows(2)->maxLength(400)->columnSpanFull()
                        ->helperText('E.g. warranty terms or return policy in one or two sentences.'),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }
}
