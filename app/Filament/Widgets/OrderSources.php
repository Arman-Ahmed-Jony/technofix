<?php

namespace App\Filament\Widgets;

use App\Enums\OrderSource;
use App\Enums\StaffArea;
use App\Models\Order;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

/**
 * Where the last 30 days' orders came from: website orders by the ad / site that brought the visitor
 * (orders.attribution, see CaptureAttribution), staff orders by the channel they were taken on.
 */
class OrderSources extends ChartWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '230px';

    protected ?string $pollingInterval = null;

    protected const COLORS = ['#1d71b8', '#f39200', '#10b981', '#6366f1', '#0ea5e9', '#e94e1b', '#94a3b8', '#ec4899'];

    public function getHeading(): string
    {
        return 'Where orders come from';
    }

    public function getDescription(): string
    {
        return 'Last 30 days · ad tags, referring sites and order channels';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = Order::query()->counted()->where('created_at', '>=', now()->subDays(30))
            ->get(['source', 'attribution'])
            ->countBy(fn (Order $order) => self::channel($order))
            ->sortDesc();

        if ($counts->isEmpty()) {
            $counts = collect(['No orders yet' => 0]);
        }

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => array_slice(self::COLORS, 0, $counts->count()),
                'borderWidth' => 0,
                'hoverOffset' => 6,
            ]],
            'labels' => $counts->keys()->all(),
        ];
    }

    public static function channel(Order $order): string
    {
        if ($order->source !== OrderSource::Web) {
            return $order->source->getLabel();
        }

        return self::fromAttribution($order->attribution);
    }

    /** Where a website visitor came from (see CaptureAttribution), e.g. "Facebook / Instagram". */
    public static function fromAttribution(?array $a): string
    {
        $a ??= [];
        $host = Str::lower((string) parse_url((string) ($a['referrer'] ?? ''), PHP_URL_HOST));

        return match (true) {
            filled($a['utm_source'] ?? null) => Str::headline(Str::lower($a['utm_source'])),
            filled($a['fbclid'] ?? null) || Str::contains($host, ['facebook.', 'fb.', 'instagram.', 'messenger.']) => 'Facebook / Instagram',
            filled($a['gclid'] ?? null) => 'Google Ads',
            Str::contains($host, 'google.') => 'Google search',
            filled($host) => Str::after($host, 'www.'),
            default => 'Direct / typed in',
        };
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                cutout: '68%',
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 7, boxHeight: 7, padding: 12 } },
                    tooltip: { padding: 10, cornerRadius: 10 },
                },
                scales: { x: { display: false }, y: { display: false } },
            }
        JS);
    }

    public static function canView(): bool
    {
        return StaffArea::Reports->allowed();
    }
}
