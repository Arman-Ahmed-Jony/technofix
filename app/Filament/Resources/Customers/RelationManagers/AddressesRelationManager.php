<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Models\CustomerAddress;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** The customer's saved addresses (they manage them in their account). */
class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->placeholder('Address')->weight('medium'),
                TextColumn::make('name')->description(fn (CustomerAddress $record) => $record->phone),
                TextColumn::make('address')->state(fn (CustomerAddress $record) => $record->summary())->wrap(),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->paginated(false)
            ->emptyStateHeading('No saved addresses');
    }
}
