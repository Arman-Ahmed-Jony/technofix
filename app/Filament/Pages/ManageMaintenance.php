<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Settings\MaintenanceSettings;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Close the shop to customers for a while; staff and the admin keep working. */
class ManageMaintenance extends SettingsPage
{
    protected static string $settings = MaintenanceSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Maintenance mode';

    protected static ?string $title = 'Maintenance mode';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'maintenance';

    public static function canAccess(): bool
    {
        return StaffArea::Settings->allowed();
    }

    public static function getNavigationBadge(): ?string
    {
        return rescue(fn () => app(MaintenanceSettings::class)->enabled, false, false) ? 'On' : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')
                        ->label('Close the shop to customers')
                        ->helperText(new HtmlString('Customers see a “back soon” page with your hotline and WhatsApp. You (signed in as staff) still see the shop and can check it — <a href="'.url('/').'" target="_blank" class="underline">open the shop</a>.')),
                    TextInput::make('headline')->required()->maxLength(80),
                    Textarea::make('message')->rows(2)->maxLength(300),
                    DateTimePicker::make('back_at')->label('Expected back (optional)')->seconds(false)->format('Y-m-d H:i'),
                ]),
        ]);
    }
}
