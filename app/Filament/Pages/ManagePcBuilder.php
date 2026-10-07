<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Filament\Resources\PcBuilderSlots\PcBuilderSlotResource;
use App\Settings\PcBuilderSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ManagePcBuilder extends SettingsPage
{
    protected static string $settings = PcBuilderSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'PC Builder rules';

    protected static ?string $title = 'PC Builder rules';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'pc-builder-rules';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('slots')->label('Builder slots')->icon('heroicon-o-cpu-chip')->color('gray')->url(PcBuilderSlotResource::getUrl()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')
                ->columnSpanFull()
                ->schema([
                    TextInput::make('title')->label('Heading')->required()->maxLength(60),
                    Textarea::make('intro')->label('Introduction')->rows(2)->maxLength(300),
                    Textarea::make('quote_note')->label('Note on printed quotations')->rows(2)->maxLength(400)
                        ->helperText('E.g. how long prices are valid, assembly, warranty.'),
                ]),
            Section::make('Compatibility checks')
                ->description('Each check compares two parts using their spec fields (the ones marked “PC Builder” in Catalog → Spec fields). If a value is missing on a product, that check is skipped for it — fill them in for the checks to work. Parts that don’t fit can still be picked; the customer sees a warning.')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('check_socket')->label('Socket: processor ↔ motherboard ↔ CPU cooler')->helperText('Uses “Socket”, e.g. LGA1700, AM5. A cooler can list several (LGA1700, AM5, LGA115X).'),
                    Toggle::make('check_memory')->label('Memory type: RAM ↔ motherboard')->helperText('Uses “Memory Type” (DDR4 / DDR5).'),
                    Toggle::make('check_form_factor')->label('Size: motherboard ↔ casing')->helperText('Uses “Form Factor”: Mini-ITX < Micro-ATX < ATX < E-ATX — a casing fits boards of its size and smaller.'),
                    Toggle::make('check_gpu_length')->label('Length: graphics card ↔ casing')->helperText('Uses “Card Length” and “Max GPU Length” (mm).'),
                    Toggle::make('check_power')->label('Power: estimated draw ↔ power supply')->helperText('Uses “TDP” of the processor and graphics card and the power supply’s “Wattage”.'),
                ]),
            Section::make('Power estimate')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('base_watts')
                        ->label('Allowance for the rest of the PC')
                        ->numeric()->integer()->minValue(0)->maxValue(500)->required()
                        ->suffix('W')
                        ->helperText('Added to processor + graphics card TDP for the motherboard, RAM, drives and fans.'),
                    TextInput::make('psu_headroom')
                        ->label('Recommended headroom')
                        ->numeric()->integer()->minValue(0)->maxValue(100)->required()
                        ->suffix('%')
                        ->helperText('A power supply below the estimate is a conflict; below estimate + headroom is a warning.'),
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return StaffArea::Catalog->allowed();
    }
}
