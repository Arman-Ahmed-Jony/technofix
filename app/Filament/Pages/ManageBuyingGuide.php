<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Filament\Schemas\BuyingGuideFields;
use App\Settings\BuyingGuideSettings;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageBuyingGuide extends SettingsPage
{
    protected static string $settings = BuyingGuideSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Buying guide';

    protected static ?string $title = 'Buying guide defaults';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'buying-guide';

    protected ?string $subheading = 'The “Buying guide” box at the bottom of every category page (e.g. /laptop) and category + brand page (e.g. /laptop/dell). '
        .'A category or category + brand page with its own text or questions uses those instead.';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Default text')
                ->columnSpanFull()
                ->description(BuyingGuideFields::NAME_HINT)
                ->schema([
                    Textarea::make('text')->hiddenLabel()->required()->rows(4)->maxLength(1500),
                ]),
            Section::make('Default questions')
                ->columnSpanFull()
                ->description(BuyingGuideFields::NAME_HINT.' Pages can also have their own questions: Catalog → Categories (category pages) or Catalog → Brands → Category pages.')
                ->schema([
                    BuyingGuideFields::faqs()->hiddenLabel(),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Content->allowed();
    }
}
