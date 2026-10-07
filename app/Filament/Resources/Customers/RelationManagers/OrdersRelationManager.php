<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('number')->label('Order')->fontFamily('mono')->weight('medium'),
                TextColumn::make('created_at')->label('Placed')->dateTime('d M Y, h:i A'),
                TextColumn::make('total')->formatStateUsing(fn (int $state) => 'BDT '.number_format($state)),
                TextColumn::make('status')->badge(),
                TextColumn::make('source')->badge()->color('gray'),
            ])
            ->emptyStateHeading('No orders yet');
    }
}
