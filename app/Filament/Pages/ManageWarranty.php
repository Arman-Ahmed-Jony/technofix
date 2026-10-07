<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\WarrantySettings;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

/** Warranty claims: the customer status page, customer emails and the Excel banner. */
class ManageWarranty extends SettingsPage
{
    protected static string $settings = WarrantySettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Warranty';

    protected static ?string $title = 'Warranty';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'warranty-settings';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed() || StaffArea::Warranty->allowed();
    }

    public function getSubheading(): ?string
    {
        return 'The claims themselves are in Sales → Warranty claims.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Warranty status page')
                ->description('Customers type their mobile number and see their products’ warranty status and your notes.')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('lookup_enabled')->label('Show the warranty status page')
                        ->helperText('With its link in the footer (Help) and the mobile menu. Off = the page says to call the shop.'),
                    Textarea::make('page_intro')->label('Text under the heading')->rows(3)->maxLength(500)->required(),
                ]),
            Section::make('Emails to customers')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('emails_enabled')->label('Email customers about their claims')
                        ->helperText('When a claim is added, or its status or the note for the customer changes — only for claims with an email address. Staff can untick it for a single update.'),
                ]),
            Section::make('Excel download')
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('sheet_banner')->label('Banner across the top of the sheet')
                        ->image()->disk('public')->directory('brand')->visibility('public')
                        ->acceptedFileTypes(['image/png', 'image/jpeg'])->maxSize(2048)
                        ->helperText('PNG or JPG, wide (about 1100 × 200 px). Empty = the TechnoFix banner from the original sheet.'),
                ]),
        ]);
    }
}
