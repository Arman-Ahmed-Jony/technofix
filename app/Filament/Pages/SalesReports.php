<?php

namespace App\Filament\Pages;

use App\Enums\StaffArea;
use App\Support\ActivityLog;
use App\Support\ReportExport;
use App\Support\Reports;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

/**
 * Sales reports: totals, day by day, products (with estimated profit), categories, brands, where orders came from,
 * districts, staff and coupons — for any period, counting orders placed or delivered; all of it as one Excel file.
 */
class SalesReports extends Page
{
    protected string $view = 'filament.pages.sales-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Sales reports';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'reports';

    #[Url]
    public string $preset = '30d';

    #[Url]
    public string $from = '';

    #[Url]
    public string $until = '';

    #[Url]
    public string $basis = 'placed';

    public static function canAccess(): bool
    {
        return StaffArea::Reports->allowed();
    }

    public function mount(): void
    {
        $this->preset = array_key_exists($this->preset, Reports::PRESETS) ? $this->preset : '30d';
        $this->basis = array_key_exists($this->basis, Reports::BASES) ? $this->basis : 'placed';

        if ($this->preset !== 'custom' || ! $this->from || ! $this->until) {
            $this->applyPreset();
        }
    }

    public function updatedPreset(): void
    {
        if ($this->preset !== 'custom') {
            $this->applyPreset();
        }
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedUntil(): void
    {
        $this->preset = 'custom';
    }

    public function report(): Reports
    {
        $from = rescue(fn () => Carbon::parse($this->from), Carbon::today()->subDays(29), false);
        $until = rescue(fn () => Carbon::parse($this->until), Carbon::today(), false);

        if ($from->gt($until)) {
            [$from, $until] = [$until, $from];
        }

        // At most two years at a time (keeps the page quick on shared hosting).
        return new Reports(max($from, $until->copy()->subYears(2)), $until, $this->basis);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Download Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->export()),
        ];
    }

    public function export(): BinaryFileResponse
    {
        $export = new ReportExport($report = $this->report());
        $path = $export->write(tempnam(sys_get_temp_dir(), 'report').'.xlsx');
        ActivityLog::record('exported', label: 'Sales report — '.$report->label(), type: 'settings');

        return response()->download($path, $export->fileName())->deleteFileAfterSend();
    }

    protected function getViewData(): array
    {
        return ['report' => $this->report()];
    }

    private function applyPreset(): void
    {
        [$from, $until] = Reports::presetRange($this->preset);
        $this->from = $from->toDateString();
        $this->until = $until->toDateString();
    }
}
