<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\StaffArea;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Support\SalesReport;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/** Today, this month (vs the same days last month), orders waiting, average order — with 14-day sparklines. */
class KeyFigures extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        $daily = collect(SalesReport::daily(14));
        $today = SalesReport::between(Carbon::today(), now());

        $month = SalesReport::between(now()->startOfMonth(), now());
        $lastMonth = SalesReport::between(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow());
        $change = $lastMonth['sales'] ? (int) round(($month['sales'] - $lastMonth['sales']) / $lastMonth['sales'] * 100) : null;

        $pending = Order::where('status', OrderStatus::Pending);
        $waiting = (clone $pending)->count();
        $oldest = (clone $pending)->oldest()->value('created_at');

        $recent = SalesReport::between(Carbon::today()->subDays(29), now());
        $average = $recent['orders'] ? intdiv($recent['sales'], $recent['orders']) : 0;

        return [
            Stat::make('Sales today', 'BDT '.number_format($today['sales']))
                ->description($today['orders'].' '.str('order')->plural($today['orders']).' so far')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->chart($daily->pluck('sales')->all())
                ->color('primary'),

            Stat::make('This month', 'BDT '.number_format($month['sales']))
                ->description($change === null ? $month['orders'].' orders' : ($change >= 0 ? '+' : '').$change.'% vs last month')
                ->descriptionIcon($change === null ? 'heroicon-m-calendar' : ($change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'))
                ->chart($daily->pluck('orders')->all())
                ->color($change === null ? 'gray' : ($change >= 0 ? 'success' : 'danger')),

            Stat::make('Waiting to confirm', number_format($waiting))
                ->description($oldest ? 'Oldest '.Carbon::parse($oldest)->diffForHumans(short: true) : 'All caught up')
                ->descriptionIcon($waiting ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($waiting ? 'warning' : 'success')
                ->url(OrderResource::getUrl('index', ['tab' => 'new'])),

            Stat::make('Average order', 'BDT '.number_format($average))
                ->description($recent['orders'].' '.str('order')->plural($recent['orders']).' in the last 30 days')
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('info'),
        ];
    }

    public static function canView(): bool
    {
        return StaffArea::Reports->allowed();
    }
}
