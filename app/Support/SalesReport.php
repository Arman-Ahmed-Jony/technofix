<?php

namespace App\Support;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sales figures for the admin dashboard. "Sales" = order totals of every order except cancelled / returned, by the
 * day the order was placed (store time zone).
 */
class SalesReport
{
    /**
     * @return array{sales: int, orders: int}
     */
    public static function between(CarbonInterface $from, CarbonInterface $to): array
    {
        $row = Order::query()->counted()->whereBetween('created_at', [$from, $to])
            ->selectRaw('COALESCE(SUM(total), 0) as sales, COUNT(*) as orders')->first();

        return ['sales' => (int) $row->sales, 'orders' => (int) $row->orders];
    }

    /**
     * Day by day for the last $days days (today included), zero-filled.
     *
     * @return array<string, array{sales: int, orders: int}> 'Y-m-d' => figures
     */
    public static function daily(int $days): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = Order::query()->counted()->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total) as sales, COUNT(*) as orders')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->day)->toDateString());

        $series = [];

        for ($day = $from->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $row = $rows->get($day->toDateString());
            $series[$day->toDateString()] = ['sales' => (int) ($row->sales ?? 0), 'orders' => (int) ($row->orders ?? 0)];
        }

        return $series;
    }

    /** "BDT 1.2L" style short amounts for chart axes and tight spaces (lakh / crore, as used in Bangladesh). */
    public static function short(int $amount): string
    {
        return match (true) {
            $amount >= 10_000_000 => round($amount / 10_000_000, 1).' Cr',
            $amount >= 100_000 => round($amount / 100_000, 1).' L',
            $amount >= 1_000 => round($amount / 1_000, 1).'K',
            default => (string) $amount,
        };
    }
}
