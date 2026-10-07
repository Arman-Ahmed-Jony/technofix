<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New order')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $tab = fn (string $label, array $statuses, ?string $color = null) => Tab::make($label)
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $statuses)))
            ->badge(($n = collect($statuses)->sum(fn (OrderStatus $s) => $counts[$s->value] ?? 0)) ?: null)
            ->badgeColor($color ?? 'gray');

        return [
            'open' => $tab('To do', [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], 'warning'),
            'new' => $tab('New', [OrderStatus::Pending], 'warning'),
            'shipped' => $tab('Shipped', [OrderStatus::Shipped], 'primary'),
            'delivered' => $tab('Delivered', [OrderStatus::Delivered]),
            'cancelled' => $tab('Cancelled / returned', [OrderStatus::Cancelled, OrderStatus::Returned]),
            'all' => Tab::make('All'),
        ];
    }
}
