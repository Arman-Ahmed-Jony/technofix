<?php

namespace App\Filament\Resources\PcBuilderSlots\Tables;

use App\Models\PcBuilderSlot;
use App\Support\Catalog;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PcBuilderSlotsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->paginated(false)
            ->description('Drag to change the order on the builder page.')
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->description(function (PcBuilderSlot $record) {
                        $categories = collect($record->category_ids)->map(fn ($id) => app(Catalog::class)->categoryById((int) $id)?->name)->filter()->join(', ');

                        return $categories === $record->name ? null : $categories;
                    }),
                TextColumn::make('section')->formatStateUsing(fn (string $state) => PcBuilderSlot::SECTIONS[$state] ?? $state)->badge()->color('gray'),
                TextColumn::make('role')->label('Checked as')->placeholder('—'),
                IconColumn::make('is_required')->label('Required')->boolean(),
                TextColumn::make('max_qty')->label('Max qty'),
                TextColumn::make('products')
                    ->label('Products')
                    ->state(fn (PcBuilderSlot $record) => $record->products()->count())
                    ->color(fn (int $state) => $state === 0 ? 'danger' : null),
                ToggleColumn::make('is_active')->label('On builder'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
