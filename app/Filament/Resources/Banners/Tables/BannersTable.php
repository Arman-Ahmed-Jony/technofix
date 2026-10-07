<?php

namespace App\Filament\Resources\Banners\Tables;

use App\Models\Banner;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->description('Drag to change the order of hero slides and announcement messages.')
            ->columns([
                TextColumn::make('title')
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (Banner $record) => collect([$record->eyebrow, $record->style === 'image' && $record->placement === 'hero' ? 'Full image' : null])->filter()->join(' · ') ?: null),
                TextColumn::make('placement')
                    ->label('Where')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'hero' ? 'Hero slide' : 'Announcement')
                    ->color(fn (string $state) => $state === 'hero' ? 'primary' : 'warning'),
                TextColumn::make('starts_at')
                    ->label('Dates')
                    ->state(fn (Banner $record) => $record->starts_at || $record->ends_at
                        ? ($record->starts_at?->format('d M, h:i A') ?? 'Now').' → '.($record->ends_at?->format('d M, h:i A') ?? 'no end')
                        : 'Always')
                    ->color('gray'),
                TextColumn::make('state')
                    ->label('Status')
                    ->state(fn (Banner $record) => $record->state())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')->label('On'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
