<?php

namespace App\Filament\Resources\Campaigns\Tables;

use App\Models\Campaign;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('items'))
            ->columns([
                TextColumn::make('name')
                    ->weight('medium')
                    ->searchable()
                    ->description(fn (Campaign $record) => $record->headline),
                TextColumn::make('badge')->badge()->color('warning'),
                TextColumn::make('items_count')->label('Products')->alignCenter(),
                TextColumn::make('starts_at')
                    ->label('Runs')
                    ->state(fn (Campaign $record) => $record->starts_at->format('d M, h:i A').' → '.$record->ends_at->format('d M, h:i A'))
                    ->description(fn (Campaign $record) => match ($record->state()) {
                        'Live' => 'Ends '.$record->ends_at->diffForHumans(),
                        'Scheduled' => 'Starts '.$record->starts_at->diffForHumans(),
                        default => null,
                    })
                    ->sortable(),
                TextColumn::make('state')
                    ->label('Status')
                    ->state(fn (Campaign $record) => $record->state())
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
