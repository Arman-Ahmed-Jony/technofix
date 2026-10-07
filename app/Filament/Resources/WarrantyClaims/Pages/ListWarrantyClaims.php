<?php

namespace App\Filament\Resources\WarrantyClaims\Pages;

use App\Enums\WarrantyStatus;
use App\Filament\Resources\WarrantyClaims\Tables\WarrantyClaimsTable;
use App\Filament\Resources\WarrantyClaims\WarrantyClaimResource;
use App\Models\WarrantyClaim;
use App\Support\WarrantySheet\Exporter;
use App\Support\WarrantySheet\Importer;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ListWarrantyClaims extends ListRecords
{
    protected static string $resource = WarrantyClaimResource::class;

    public function getSubheading(): ?string
    {
        return 'Customers check their claims on the website with their mobile number (Warranty status page). Your own stock never shows there.';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('export')
                    ->label('Download Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->modalHeading('Download the warranty list')
                    ->modalDescription('The same layout as the shop’s Warranty List sheet, with the TechnoFix banner. Edit it in Excel and import it back if you like — rows are matched by SYSTEM ID.')
                    ->modalSubmitActionLabel('Download')
                    ->schema([
                        Select::make('statuses')->label('Status')->multiple()->options(WarrantyStatus::class)->placeholder('All statuses'),
                        Toggle::make('own_stock')->label('Include our own stock')->default(true),
                    ])
                    ->action(function (array $data) {
                        $query = WarrantyClaim::query()
                            ->when($data['statuses'] ?? [], fn (Builder $q, array $statuses) => $q->whereIn('status', $statuses))
                            ->when(! ($data['own_stock'] ?? true), fn (Builder $q) => $q->where('is_internal', false));
                        $path = tempnam(sys_get_temp_dir(), 'tfw').'.xlsx';
                        (new Exporter($query))->writeTo($path);

                        return response()->download($path, Exporter::fileName())->deleteFileAfterSend();
                    }),
                Action::make('import')
                    ->label('Import from Excel')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->modalHeading('Import a warranty sheet')
                    ->modalDescription('The shop’s original Warranty List or a downloaded copy. New rows are added; rows already here are skipped; downloaded rows (with SYSTEM ID) update their claim. No emails are sent.')
                    ->modalSubmitActionLabel('Import')
                    ->schema([
                        FileUpload::make('file')->label('Excel file (.xlsx)')->required()
                            ->disk('local')->directory('imports/warranty')->visibility('private')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                            ->maxSize(20 * 1024)
                            ->storeFileNamesIn('file_name'),
                    ])
                    ->action(function (array $data) {
                        $path = Storage::disk('local')->path($data['file']);

                        try {
                            $report = (new Importer)->import($path, $data['file_name'] ?? 'warranty sheet');
                        } catch (Throwable $e) {
                            Notification::make()->danger()->title('Import failed')->body(Str::limit($e->getMessage(), 300))->persistent()->send();

                            return;
                        } finally {
                            Storage::disk('local')->delete($data['file']);
                        }

                        Notification::make()
                            ->title('Warranty sheet imported')
                            ->body(collect([
                                $report['created'].' added',
                                $report['updated'].' updated',
                                $report['skipped'].' already here',
                                $report['notes'] ? $report['notes'].' extra lines kept as staff notes' : null,
                                $report['problems'] ? count($report['problems']).' rows with problems: '.implode('; ', array_slice($report['problems'], 0, 5)) : null,
                            ])->filter()->join(' · '))
                            ->status($report['problems'] ? 'warning' : 'success')
                            ->persistent()
                            ->send();
                    }),
            ])->label('Excel')->icon('heroicon-o-table-cells')->button()->color('gray'),
            CreateAction::make()->label('New claim'),
        ];
    }

    public function getTabs(): array
    {
        $count = fn (?callable $scope = null) => (clone WarrantyClaim::query())->when($scope, $scope)->count();

        return [
            'open' => Tab::make('Open')
                ->modifyQueryUsing(fn (Builder $query) => WarrantyClaimsTable::openScope($query))
                ->badge($count(fn (Builder $q) => WarrantyClaimsTable::openScope($q))),
            'ready' => Tab::make('Ready to collect')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', WarrantyStatus::Ready)->where('is_internal', false))
                ->badge($count(fn (Builder $q) => $q->where('status', WarrantyStatus::Ready)->where('is_internal', false)))
                ->badgeColor('success'),
            'customers' => Tab::make('Customers’ products')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_internal', false)),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
