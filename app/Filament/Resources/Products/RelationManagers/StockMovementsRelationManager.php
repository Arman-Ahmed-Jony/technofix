<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Read-only stock ledger: every change to this product's quantity. */
class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Stock history';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order:id,number', 'user:id,name']))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('d M Y, h:i A'),
                TextColumn::make('type')->formatStateUsing(fn (string $state) => StockMovement::TYPES[$state] ?? $state)->badge()->color('gray'),
                TextColumn::make('change')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '').$state)
                    ->color(fn (int $state) => $state > 0 ? 'success' : 'danger')
                    ->weight('medium'),
                TextColumn::make('balance')->label('Quantity after'),
                TextColumn::make('order.number')
                    ->label('Order')
                    ->placeholder('—')
                    ->url(fn (StockMovement $record) => $record->order ? OrderResource::getUrl('view', ['record' => $record->order]) : null),
                TextColumn::make('user.name')->label('By')->placeholder('Customer / system'),
                TextColumn::make('note')->placeholder('—')->toggleable(),
            ])
            ->emptyStateHeading('No stock changes yet');
    }
}
