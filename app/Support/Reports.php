<?php

namespace App\Support;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Widgets\OrderSources;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales reports (Admin → Sales → Reports, Excel export). Like the dashboard, "sales" are order totals of every order
 * except cancelled / returned, by the day the order was placed — or, with basis "delivered", only delivered orders
 * by the day they were delivered (what was really sold with cash on delivery).
 */
class Reports
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'custom' => 'Custom dates',
    ];

    public const BASES = ['placed' => 'Orders placed', 'delivered' => 'Orders delivered'];

    public readonly CarbonInterface $from;

    public readonly CarbonInterface $to;

    /** @var array<string, mixed> */
    private array $memo = [];

    public function __construct(CarbonInterface $from, CarbonInterface $to, public readonly string $basis = 'placed')
    {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function presetRange(string $preset): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'today' => [$today, $today],
            'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
            '7d' => [$today->copy()->subDays(6), $today],
            'this_month' => [$today->copy()->startOfMonth(), $today],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today],
            default => [$today->copy()->subDays(29), $today],
        };
    }

    public function label(): string
    {
        $range = $this->from->isSameDay($this->to) ? $this->from->format('j M Y') : $this->from->format('j M Y').' – '.$this->to->format('j M Y');

        return self::BASES[$this->basis].', '.$range;
    }

    /** The orders this report counts. */
    public function orders(): Builder
    {
        if ($this->basis === 'delivered') {
            return Order::query()->where('orders.status', OrderStatus::Delivered)->whereIn('orders.id', OrderStatusHistory::query()
                ->where('to_status', OrderStatus::Delivered->value)->whereBetween('created_at', [$this->from, $this->to])->select('order_id'));
        }

        return Order::query()->counted()->whereBetween('created_at', [$this->from, $this->to]);
    }

    /**
     * @return array<string, int|float>
     */
    public function summary(): array
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $row = $this->orders()->toBase()->selectRaw('COUNT(*) as orders, COALESCE(SUM(total), 0) as sales, COALESCE(SUM(subtotal), 0) as subtotal,
                COALESCE(SUM(delivery_fee), 0) as delivery, COALESCE(SUM(discount), 0) as discount, COALESCE(SUM(paid_amount), 0) as paid,
                COUNT(DISTINCT phone) as buyers')->first();
            $items = (int) DB::table('order_items')->whereIn('order_id', $this->orders()->select('id'))->sum('qty');
            $lost = Order::query()->whereIn('status', [OrderStatus::Cancelled, OrderStatus::Returned])->whereBetween('created_at', [$this->from, $this->to])
                ->toBase()->selectRaw('status, COUNT(*) as orders, COALESCE(SUM(total), 0) as value')->groupBy('status')->get()->keyBy('status');
            $products = $this->products();

            return [
                'orders' => (int) $row->orders,
                'sales' => (int) $row->sales,
                'subtotal' => (int) $row->subtotal,
                'delivery' => (int) $row->delivery,
                'discount' => (int) $row->discount,
                'average' => $row->orders ? (int) round($row->sales / $row->orders) : 0,
                'items' => $items,
                'buyers' => (int) $row->buyers,
                'collected' => min((int) $row->paid, (int) $row->sales),
                'to_collect' => max(0, (int) $row->sales - (int) $row->paid),
                'cancelled' => (int) ($lost[OrderStatus::Cancelled->value]->orders ?? 0),
                'cancelled_value' => (int) ($lost[OrderStatus::Cancelled->value]->value ?? 0),
                'returned' => (int) ($lost[OrderStatus::Returned->value]->orders ?? 0),
                'returned_value' => (int) ($lost[OrderStatus::Returned->value]->value ?? 0),
                'profit' => $products->sum('profit'),
                'profit_coverage' => $products->sum('revenue') ? (int) round($products->whereNotNull('profit')->sum('revenue') / $products->sum('revenue') * 100) : 0,
            ];
        })();
    }

    /**
     * Day by day (zero-filled).
     *
     * @return Collection<int, array{day: Carbon, orders: int, sales: int}>
     */
    public function daily(): Collection
    {
        return $this->memo[__FUNCTION__] ??= (function () {
            $dateColumn = $this->basis === 'delivered' ? 'h.created_at' : 'orders.created_at';
            $query = $this->orders()->toBase();

            if ($this->basis === 'delivered') {
                $query->join('order_status_histories as h', fn ($join) => $join->on('h.order_id', '=', 'orders.id')->where('h.to_status', OrderStatus::Delivered->value)
                    ->where(fn ($q) => $q->whereNull('h.from_status')->orWhere('h.from_status', '!=', OrderStatus::Delivered->value)));
            }

            $rows = $query->selectRaw("DATE({$dateColumn}) as day, COUNT(DISTINCT orders.id) as orders, SUM(orders.total) as sales")
                ->groupBy(DB::raw("DATE({$dateColumn})"))
                ->get()
                ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

            $days = collect();
            for ($day = $this->from->copy()->startOfDay(); $day->lte($this->to) && $days->count() < 400; $day->addDay()) {
                $row = $rows->get($day->toDateString());
                $days->push(['day' => $day->copy(), 'orders' => (int) ($row->orders ?? 0), 'sales' => (int) ($row->sales ?? 0)]);
            }

            return $days;
        })();
    }

    /**
     * The daily figures in at most ~$bars bars (a day, a week or a month each), for the chart.
     *
     * @return Collection<int, array{label: string, orders: int, sales: int}>
     */
    public function chart(int $bars = 62): Collection
    {
        $days = $this->daily();
        $unit = match (true) {
            $days->count() <= $bars => 'day',
            $days->count() <= $bars * 7 => 'week',
            default => 'month',
        };

        return $days
            ->groupBy(fn (array $d) => match ($unit) {
                'day' => $d['day']->toDateString(),
                'week' => $d['day']->copy()->startOfWeek()->toDateString(),
                'month' => $d['day']->format('Y-m'),
            })
            ->map(fn (Collection $group) => [
                'label' => match ($unit) {
                    'day' => $group->first()['day']->format('D j M'),
                    'week' => 'Week of '.$group->first()['day']->format('j M'),
                    'month' => $group->first()['day']->format('F Y'),
                },
                'orders' => $group->sum('orders'),
                'sales' => $group->sum('sales'),
            ])
            ->values();
    }

    /**
     * Products sold: quantity, sales and an estimated profit (from the product's cost price today, when it has one).
     *
     * @return Collection<int, array{name: string, sku: ?string, qty: int, revenue: int, cost: ?int, profit: ?int, category: string, brand: string}>
     */
    public function products(): Collection
    {
        return $this->memo[__FUNCTION__] ??= DB::table('order_items')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('order_items.order_id', $this->orders()->select('id'))
            ->groupBy('order_items.product_id', 'order_items.name', 'order_items.sku', 'products.cost_price', 'products.category_id', 'products.brand_id')
            ->selectRaw('order_items.product_id, order_items.name, order_items.sku, products.cost_price, products.category_id, products.brand_id,
                SUM(order_items.qty) as qty, SUM(order_items.total) as revenue')
            ->get()
            ->groupBy(fn ($row) => $row->product_id ?? 'x'.$row->name) // a renamed product sold under two names = one line
            ->map(function (Collection $rows) {
                $first = $rows->first();
                $qty = (int) $rows->sum('qty');
                $revenue = (int) $rows->sum('revenue');
                $cost = $first->cost_price ? (int) $first->cost_price * $qty : null;

                return [
                    'name' => $rows->last()->name,
                    'sku' => $first->sku,
                    'qty' => $qty,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $cost !== null ? $revenue - $cost : null,
                    'category' => CatalogCache::data()['categories'][$first->category_id]['name'] ?? '—',
                    'brand' => CatalogCache::data()['brands'][$first->brand_id]['name'] ?? '—',
                ];
            })
            ->sortByDesc('revenue')
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, qty: int, revenue: int, share: float}>
     */
    public function byCategory(): Collection
    {
        return $this->group($this->products(), 'category');
    }

    /**
     * @return Collection<int, array{name: string, qty: int, revenue: int, share: float}>
     */
    public function byBrand(): Collection
    {
        return $this->group($this->products(), 'brand');
    }

    /**
     * Where orders came from: website orders by the ad / site that brought the visitor, staff orders by channel.
     *
     * @return Collection<int, array{name: string, orders: int, revenue: int, share: float}>
     */
    public function byChannel(): Collection
    {
        $rows = $this->orders()->get(['id', 'source', 'attribution', 'total'])
            ->groupBy(fn (Order $order) => OrderSources::channel($order))
            ->map(fn (Collection $orders, string $name) => ['name' => $name, 'orders' => $orders->count(), 'revenue' => (int) $orders->sum('total')]);

        return $this->withShare($rows);
    }

    /**
     * @return Collection<int, array{name: string, orders: int, revenue: int, share: float}>
     */
    public function byDistrict(): Collection
    {
        $place = "CASE WHEN is_pickup = 1 THEN 'Store pickup' ELSE COALESCE(district, '—') END"; // (not "name": orders has a name column)
        $rows = $this->orders()->toBase()
            ->selectRaw("{$place} as place, COUNT(*) as orders, SUM(total) as revenue")
            ->groupByRaw($place)
            ->get()
            ->map(fn ($row) => ['name' => $row->place, 'orders' => (int) $row->orders, 'revenue' => (int) $row->revenue]);

        return $this->withShare($rows);
    }

    /**
     * Per staff member in the period: orders they took (phone, Facebook…), confirmed and marked delivered.
     *
     * @return Collection<int, array{name: string, taken: int, taken_value: int, confirmed: int, delivered: int}>
     */
    public function byStaff(): Collection
    {
        // Real status changes only (payments add notes that keep the status, e.g. "delivered → delivered").
        $history = OrderStatusHistory::query()->whereNotNull('user_id')->whereBetween('order_status_histories.created_at', [$this->from, $this->to])
            ->where(fn ($q) => $q->whereNull('from_status')->orWhereColumn('from_status', '!=', 'to_status'));

        $taken = (clone $history)->whereNull('from_status')->join('orders', 'orders.id', '=', 'order_status_histories.order_id')
            ->where('orders.source', '!=', OrderSource::Web->value)
            ->selectRaw('user_id, COUNT(*) as n, SUM(orders.total) as value')->groupBy('user_id')->get()->keyBy('user_id');
        $confirmed = (clone $history)->where('to_status', OrderStatus::Confirmed->value)->whereNotNull('from_status')
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->pluck('n', 'user_id');
        $delivered = (clone $history)->where('to_status', OrderStatus::Delivered->value)
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->pluck('n', 'user_id');

        $ids = $taken->keys()->merge($confirmed->keys())->merge($delivered->keys())->unique();

        return User::query()->whereIn('id', $ids)->get(['id', 'name'])
            ->map(fn (User $user) => [
                'name' => $user->name,
                'taken' => (int) ($taken[$user->id]->n ?? 0),
                'taken_value' => (int) ($taken[$user->id]->value ?? 0),
                'confirmed' => (int) ($confirmed[$user->id] ?? 0),
                'delivered' => (int) ($delivered[$user->id] ?? 0),
            ])
            ->sortByDesc(fn (array $row) => $row['taken'] + $row['confirmed'] + $row['delivered'])
            ->values();
    }

    /**
     * @return Collection<int, array{name: string, orders: int, discount: int, revenue: int}>
     */
    public function byCoupon(): Collection
    {
        return $this->orders()->toBase()->whereNotNull('coupon_code')
            ->selectRaw('coupon_code as name, COUNT(*) as orders, SUM(discount) as discount, SUM(total) as revenue')
            ->groupBy('coupon_code')
            ->orderByDesc('orders')
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'orders' => (int) $row->orders, 'discount' => (int) $row->discount, 'revenue' => (int) $row->revenue]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return Collection<int, array{name: string, qty: int, revenue: int, share: float}>
     */
    private function group(Collection $products, string $key): Collection
    {
        return $this->withShare($products->groupBy($key)->map(fn (Collection $rows, string $name) => [
            'name' => $name, 'qty' => (int) $rows->sum('qty'), 'revenue' => (int) $rows->sum('revenue'),
        ]));
    }

    /**
     * @param  Collection<array-key, array<string, mixed>>  $rows
     */
    private function withShare(Collection $rows): Collection
    {
        $total = max(1, $rows->sum('revenue'));

        return $rows->map(fn (array $row) => [...$row, 'share' => round($row['revenue'] / $total * 100, 1)])->sortByDesc('revenue')->values();
    }
}
