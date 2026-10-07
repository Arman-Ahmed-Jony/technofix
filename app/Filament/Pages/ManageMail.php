<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\MailSettings;
use App\Support\MailSetup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use UnitEnum;

/** How the shop sends email (order emails, password resets, backup alerts). */
class ManageMail extends SettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email sending';

    protected static ?string $title = 'Email sending';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'email';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'Order confirmations, status updates, “new order” alerts, password resets and backup warnings are sent with these details. Your hosting’s email account (cPanel → Email Accounts) works well.';
    }

    public function form(Schema $schema): Schema
    {
        $on = fn (Get $get) => $get('enabled');

        return $schema->components([
            Section::make('Mail server (SMTP)')
                ->columns(['default' => 1, 'md' => 3])
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')->label('Send email through this server')->live()->columnSpanFull()
                        ->helperText('Off = the server’s own settings (.env file).'),
                    TextInput::make('host')->label('Server')->placeholder('mail.technofixbd.com')->required($on)->visible($on),
                    TextInput::make('port')->integer()->minValue(1)->maxValue(65535)->required()->visible($on),
                    Select::make('encryption')->label('Security')->options(['tls' => 'TLS / STARTTLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'])->selectablePlaceholder(false)->visible($on),
                    TextInput::make('username')->placeholder('info@technofixbd.com')->visible($on),
                    TextInput::make('password')->password()->revealable()->visible($on)->helperText('Stored encrypted.'),
                ]),
            Section::make('Sender')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('from_address')->label('From address')->email()->placeholder('info@technofixbd.com')
                        ->helperText('Use an address on your own domain, so emails don’t land in spam.'),
                    TextInput::make('from_name')->label('From name')->maxLength(80),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Send a test email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->schema([TextInput::make('to')->label('Send to')->email()->required()->default(fn () => auth('web')->user()?->email)])
                ->modalDescription('Uses the saved settings — save your changes first.')
                ->action(function (array $data) {
                    app()->forgetInstance('mail.manager');
                    Mail::clearResolvedInstances();
                    MailSetup::apply();

                    try {
                        Mail::raw('This is a test email from your TechnoFix shop. If you can read it, email sending works.', fn ($message) => $message->to($data['to'])->subject('TechnoFix test email'));
                        Notification::make()->success()->title('Test email sent')->body('Check '.$data['to'].' (and the spam folder).')->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('The email couldn’t be sent')->body($e->getMessage())->persistent()->send();
                    }
                }),
        ];
    }
}
