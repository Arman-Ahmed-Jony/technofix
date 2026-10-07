<?php

namespace App\Filament\Resources\PcBuilderSlots;

use App\Filament\Resources\PcBuilderSlots\Pages\CreatePcBuilderSlot;
use App\Filament\Resources\PcBuilderSlots\Pages\EditPcBuilderSlot;
use App\Filament\Resources\PcBuilderSlots\Pages\ListPcBuilderSlots;
use App\Filament\Resources\PcBuilderSlots\Schemas\PcBuilderSlotForm;
use App\Filament\Resources\PcBuilderSlots\Tables\PcBuilderSlotsTable;
use App\Models\PcBuilderSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PcBuilderSlotResource extends Resource
{
    protected static ?string $model = PcBuilderSlot::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'PC Builder';

    protected static ?string $modelLabel = 'builder slot';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'pc-builder-slots';

    public static function form(Schema $schema): Schema
    {
        return PcBuilderSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PcBuilderSlotsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPcBuilderSlots::route('/'),
            'create' => CreatePcBuilderSlot::route('/create'),
            'edit' => EditPcBuilderSlot::route('/{record}/edit'),
        ];
    }
}
