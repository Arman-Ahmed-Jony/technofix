<?php

namespace App\Filament\Widgets;

use App\Enums\StaffArea;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest orders')
            ->query(Order::query()->withCount('items')->latest('id')->limit(5))
            ->paginated(false)
            ->recordUrl(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('all')->label('View all')->link()->url(OrderResource::getUrl('index')),
            ])
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->fontFamily('mono')
                    ->weight('semibold')
                    ->description(fn (Order $record) => $record->created_at->diffForHumans(short: true)),
                TextColumn::make('name')
                    ->label('Customer')
                    ->description(fn (Order $record) => $record->phone)
                    ->limit(24)
                    ->visibleFrom('md'),
                TextColumn::make('items_count')->label('Items')->alignCenter()->visibleFrom('lg'),
                TextColumn::make('total')
                    ->formatStateUsing(fn ($state) => 'BDT '.number_format((int) $state))
                    ->weight('semibold')
                    ->alignEnd(),
                TextColumn::make('status')->badge()->alignEnd(),
            ])
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('New website and phone orders will show up here.');
    }

    public static function canView(): bool
    {
        return StaffArea::Orders->allowed();
    }
}
