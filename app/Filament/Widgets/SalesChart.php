<?php

namespace App\Filament\Widgets;

use App\Enums\StaffArea;
use App\Support\SalesReport;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/** Daily sales (line) and orders (bars) for the last 7 / 30 / 90 days. */
class SalesChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = null;

    public ?string $filter = '30';

    public function getHeading(): string
    {
        return 'Sales';
    }

    public function getDescription(): string
    {
        $total = collect(SalesReport::daily((int) $this->filter));

        return 'BDT '.number_format($total->sum('sales')).' from '.number_format($total->sum('orders')).' orders · cancelled & returned orders not counted';
    }

    protected function getFilters(): ?array
    {
        return ['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = collect(SalesReport::daily((int) ($this->filter ?: 30)));

        return [
            'datasets' => [
                [
                    'label' => 'Sales (BDT)',
                    'data' => $days->pluck('sales')->values()->all(),
                    'borderColor' => '#1D71B8',
                    'backgroundColor' => 'rgba(29, 113, 184, 0.10)',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 5,
                    'pointBackgroundColor' => '#1D71B8',
                    'yAxisID' => 'y',
                    'order' => 1,
                ],
                [
                    'type' => 'bar',
                    'label' => 'Orders',
                    'data' => $days->pluck('orders')->values()->all(),
                    'backgroundColor' => 'rgba(243, 146, 0, 0.35)',
                    'hoverBackgroundColor' => 'rgba(243, 146, 0, 0.6)',
                    'borderRadius' => 4,
                    'maxBarThickness' => 14,
                    'yAxisID' => 'y1',
                    'order' => 2,
                ],
            ],
            'labels' => $days->keys()->map(fn (string $day) => Carbon::parse($day)->format('j M'))->all(),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
            {
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: true, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 16 } },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => ctx.dataset.yAxisID === 'y'
                                ? ` Sales: BDT ${ctx.parsed.y.toLocaleString('en-US')}`
                                : ` Orders: ${ctx.parsed.y}`,
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 18 } },
                    y: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                        ticks: {
                            maxTicksLimit: 5,
                            callback: (value) => value >= 100000 ? (value / 100000).toFixed(1).replace('.0', '') + 'L'
                                : value >= 1000 ? (value / 1000).toFixed(0) + 'K' : value,
                        },
                    },
                    y1: { beginAtZero: true, position: 'right', display: false, grid: { display: false }, suggestedMax: 4 },
                },
            }
        JS);
    }

    public static function canView(): bool
    {
        return StaffArea::Reports->allowed();
    }
}
