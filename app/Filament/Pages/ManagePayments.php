<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\PaymentSettings;
use BackedEnum;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManagePayments extends SettingsPage
{
    protected static string $settings = PaymentSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $title = 'Payment settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'payment-settings';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cash on delivery')
                ->columnSpanFull()
                ->columns(2)
                ->description('How customers pay for now. Online payments (bKash, Nagad, cards) are added later. Payments you receive another way — e.g. a bKash advance — can be recorded on each order with “Record payment”.')
                ->schema([
                    TextInput::make('cod_label')->label('Name at checkout')->required()->maxLength(40),
                    TextInput::make('cod_description')->label('Short explanation')->maxLength(120),
                ]),
            Section::make('Coming soon')
                ->columnSpanFull()
                ->schema([
                    TagsInput::make('coming_soon')
                        ->label('Show these as “Coming soon” at checkout')
                        ->placeholder('Add a name and press Enter')
                        ->nestedRecursiveRules(['max:20'])
                        ->reorderable()
                        ->helperText('E.g. bKash, Nagad, Card. Leave empty to show none.'),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }
}
