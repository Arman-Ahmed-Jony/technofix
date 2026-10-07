<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\StaffArea;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Widgets\Widget;

/** Open orders by stage (New → Confirmed → Packing → Shipped) and this month's outcomes. */
class OrderPipeline extends Widget
{
    protected string $view = 'filament.widgets.order-pipeline';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $counts = Order::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');
        $month = Order::query()->where('created_at', '>=', now()->startOfMonth())->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        $stages = collect([
            [OrderStatus::Pending, '#f59e0b', 'new'],
            [OrderStatus::Confirmed, '#0ea5e9', 'open'],
            [OrderStatus::Processing, '#6366f1', 'open'],
            [OrderStatus::Shipped, '#1d71b8', 'shipped'],
        ])->map(fn (array $stage) => [
            'label' => $stage[0]->getLabel(),
            'count' => (int) ($counts[$stage[0]->value] ?? 0),
            'color' => $stage[1],
            'url' => OrderResource::getUrl('index', ['tab' => $stage[2]]),
        ]);

        return [
            'stages' => $stages,
            'open' => $stages->sum('count'),
            'delivered' => (int) ($month[OrderStatus::Delivered->value] ?? 0),
            'lost' => (int) ($month[OrderStatus::Cancelled->value] ?? 0) + (int) ($month[OrderStatus::Returned->value] ?? 0),
            'allUrl' => OrderResource::getUrl('index', ['tab' => 'open']),
        ];
    }

    public static function canView(): bool
    {
        return StaffArea::Orders->allowed();
    }
}
