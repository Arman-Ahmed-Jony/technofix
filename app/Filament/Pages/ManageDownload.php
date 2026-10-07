<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\DownloadSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use UnitEnum;

/** The glowing "Download software" button: text, link and where it shows. */
class ManageDownload extends SettingsPage
{
    protected static string $settings = DownloadSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Software download';

    protected static ?string $title = 'Software download button';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'software-download';

    public static function canAccess(): bool
    {
        return StaffArea::Content->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'A glowing button that takes customers to the drivers and software they need (for example a Google Drive folder). It opens in a new tab.';
    }

    public function form(Schema $schema): Schema
    {
        $on = fn (Get $get) => (bool) $get('enabled');

        return $schema->components([
            Section::make('Button')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')->label('Show the button')->live()->columnSpanFull(),
                    TextInput::make('label')->label('Button text')->required()->maxLength(40)->placeholder('Download software'),
                    TextInput::make('url')->label('Link address')->url()->maxLength(500)->required($on)
                        ->placeholder('https://drive.google.com/…')
                        ->helperText('Google Drive: Share → "Anyone with the link" → Copy link, then paste it here.'),
                ]),

            Section::make('Where it shows')
                ->columns(3)
                ->columnSpanFull()
                ->visible($on)
                ->schema([
                    Toggle::make('show_services')->label('Repair service page')->helperText('Big button in the top banner.'),
                    Toggle::make('show_top_bar')->label('Top bar & mobile menu')->helperText('Small glowing link on every page.'),
                    Toggle::make('show_footer')->label('Footer')->helperText('Under your contact details.'),
                ]),

            Section::make('Text next to the button (Repair page)')
                ->columns(2)
                ->columnSpanFull()
                ->visible($on)
                ->schema([
                    TextInput::make('title')->label('Heading')->maxLength(60)->placeholder('Need drivers or software?'),
                    Textarea::make('text')->label('Short note')->rows(2)->maxLength(160),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test')
                ->label('Test the link')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->visible(fn () => filled(app(DownloadSettings::class)->url))
                ->url(fn () => app(DownloadSettings::class)->url, shouldOpenInNewTab: true),
        ];
    }
}
