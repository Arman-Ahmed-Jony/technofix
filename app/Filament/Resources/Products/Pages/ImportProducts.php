<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Attribute;
use App\Models\Product;
use App\Support\ActivityLog;
use App\Support\ProductSheet\Columns;
use App\Support\ProductSheet\Exporter;
use App\Support\ProductSheet\Importer;
use App\Support\ProductSheet\SheetReader;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Upload → match columns → preview (dry run) → import. Files and reports live in storage/app/private/imports
 * and are removed after a day.
 */
class ImportProducts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Import products';

    protected static ?string $breadcrumb = 'Import';

    /** 1 = upload, 2 = match columns, 3 = preview, 4 = done */
    public int $step = 1;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?string $path = null;

    public ?string $originalName = null;

    /** @var list<string> */
    public array $headers = [];

    /** @var array<int, string> column index => a few example values */
    public array $samples = [];

    /** @var array<int, ?string> */
    public array $mapping = [];

    /** @var array<string, mixed> */
    public array $options = [];

    public ?string $reportId = null;

    public function mount(): void
    {
        $this->cleanUpOldFiles();
        $this->form->fill();
    }

    public function getSubheading(): ?string
    {
        return match ($this->step) {
            1 => 'Step 1 of 3 — Upload an Excel (.xlsx) or CSV file. Tip: export your products first and edit that file.',
            2 => "Step 2 of 3 — Tell us what each column of “{$this->originalName}” contains.",
            3 => 'Step 3 of 3 — Check the preview. Nothing has been saved yet.',
            default => 'Import finished.',
        };
    }

    // ─── Schema ─────────────────────────────────────────────────────

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components(fn () => match ($this->step) {
                1 => $this->uploadFields(),
                2 => $this->mappingFields(),
                default => [],
            });
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler(fn () => $this->step === 1 ? 'readFile' : 'showPreview')
                ->footer([
                    Actions::make([
                        Action::make('upload')->label('Next: match columns')->submit('form')->visible(fn () => $this->step === 1),
                        $this->templateAction()->visible(fn () => $this->step === 1),
                        Action::make('preview')->label('Next: preview')->submit('form')->visible(fn () => $this->step === 2),
                        $this->startOverAction()->label('Choose another file')->visible(fn () => $this->step === 2),
                    ]),
                ])
                ->visible(fn () => $this->step <= 2),

            Callout::make(fn () => $this->summaryHeading())
                ->description(fn () => $this->summaryText())
                ->status(fn () => $this->step === 4 ? 'success' : (($this->report()['summary']['error'] ?? 0) ? 'warning' : 'info'))
                ->actions([
                    $this->importAction()->button()->visible(fn () => $this->step === 3),
                    Action::make('products')->label('View products')->button()->url(ProductResource::getUrl())->visible(fn () => $this->step === 4),
                    $this->errorReportAction()->button(),
                    Action::make('back')->label('Back')->button()->color('gray')->action(fn () => $this->back())->visible(fn () => $this->step === 3),
                    $this->startOverAction()->name('importAnother')->label('Import another file')->button()->visible(fn () => $this->step === 4),
                ])
                ->visible(fn () => $this->step >= 3),

            EmbeddedTable::make()->visible(fn () => $this->step >= 3),
        ]);
    }

    /** @return array<int, mixed> */
    protected function uploadFields(): array
    {
        return [
            FileUpload::make('file')
                ->label('Product file')
                ->disk('local')
                ->directory('imports')
                ->visibility('private')
                ->required()
                ->maxSize(10240)
                ->rules(['extensions:xlsx,csv,txt'])
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => Str::uuid().'.'.Str::lower($file->getClientOriginalExtension()))
                ->helperText('Excel (.xlsx) or CSV, up to 10 MB and '.number_format(Importer::MAX_ROWS).' products. The first row must be the column names.'),
        ];
    }

    /** @return array<int, mixed> */
    protected function mappingFields(): array
    {
        $targets = [
            'Product fields' => collect(Columns::FIELDS)->map(fn (array $field) => $field[0])->all(),
            'Specifications' => Attribute::orderBy('name')->get()->mapWithKeys(fn (Attribute $a) => ["spec:{$a->id}" => $a->name.($a->unit ? " ({$a->unit})" : '')])->all(),
        ];

        return [
            Section::make('Columns')
                ->description('We matched the columns we recognised. Anything set to “Ignore” is skipped. Name or Product code is required.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema(
                        collect($this->headers)->map(fn (string $header, int $index) => Select::make("map.{$index}")
                            ->label($header !== '' ? $header : 'Column '.($index + 1))
                            ->options($targets)
                            ->placeholder('Ignore this column')
                            ->searchable()
                            ->helperText(filled($this->samples[$index] ?? null) ? 'e.g. '.$this->samples[$index] : 'Empty in the first rows'))
                            ->all()
                    ),
                ]),
            Section::make('Options')
                ->columns(['default' => 1, 'md' => 3])
                ->schema([
                    Toggle::make('update_existing')
                        ->label('Update existing products')
                        ->helperText('Matched by Product code, or by exact name when the code is empty.')
                        ->default(true),
                    Toggle::make('create_brands')
                        ->label('Create missing brands')
                        ->helperText('Off = rows with an unknown brand are rejected.')
                        ->default(true),
                    Select::make('status')
                        ->label('New products are')
                        ->options(ProductStatus::class)
                        ->default(ProductStatus::Published->value)
                        ->selectablePlaceholder(false)
                        ->helperText('Used when the file has no Status column.'),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (?array $filters, ?string $search, int|string $page, int|string $recordsPerPage) {
                $rows = collect($this->report()['rows'] ?? [])
                    ->when($filters['action']['value'] ?? null, fn ($rows, $action) => $rows->where('action', $action))
                    ->when(filled($search), fn ($rows) => $rows->filter(fn (array $row) => Str::contains(
                        Str::lower($row['name'].' '.$row['sku'].' '.$row['category']), Str::lower($search)
                    )))
                    ->map(fn (array $row) => [...$row, '__key' => (string) $row['row'], 'messages' => [...$row['errors'], ...$row['warnings']]])
                    ->values();

                $perPage = $recordsPerPage === 'all' ? max(1, $rows->count()) : (int) $recordsPerPage;

                return new LengthAwarePaginator($rows->forPage((int) $page, $perPage)->values(), $rows->count(), $perPage, (int) $page);
            })
            ->columns([
                TextColumn::make('row')->label('Row')->width('70px'),
                TextColumn::make('action')
                    ->label('Result')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Importer::ACTIONS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'create' => 'success',
                        'update' => 'info',
                        'error' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->wrap()
                    ->description(fn (array $record) => collect([$record['sku'], $record['category']])->filter()->join(' · ')),
                TextColumn::make('changes')
                    ->label('Changes')
                    ->listWithLineBreaks()
                    ->limitList(6)
                    ->expandableLimitedList()
                    ->wrap()
                    ->color('gray'),
                TextColumn::make('messages')
                    ->label('Notes')
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->wrap()
                    ->color(fn (array $record) => $record['errors'] ? 'danger' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('action')->label('Result')->options(Importer::ACTIONS),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('No rows match');
    }

    // ─── Steps ──────────────────────────────────────────────────────

    public function readFile(): void
    {
        $upload = collect($this->form->getRawState()['file'] ?? [])->first(fn ($file) => $file instanceof TemporaryUploadedFile);
        $state = $this->form->getState();
        $this->path = $state['file'];
        $this->originalName = $upload?->getClientOriginalName() ?? basename($this->path);

        try {
            $sheet = SheetReader::read($this->fullPath());
        } catch (RuntimeException $e) {
            $this->fail($e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->fail('This file could not be read. Save it as .xlsx (Excel Workbook) or .csv and try again.');

            return;
        }

        if (! $sheet['rows']) {
            $this->fail('The file has column names but no product rows.');

            return;
        }

        $attributes = Attribute::query()->get(['id', 'name', 'code'])->keyBy('id')->map(fn (Attribute $a) => ['name' => $a->name, 'code' => $a->code])->all();
        $this->headers = $sheet['headers'];
        $this->samples = collect($this->headers)->keys()->mapWithKeys(fn (int $i) => [
            $i => collect($sheet['rows'])->pluck($i)->filter()->take(3)->map(fn ($v) => Str::limit(str_replace("\n", ' ', $v), 30))->join(', '),
        ])->all();

        // Guess the mapping; a target can only be used once (first column wins).
        $used = [];
        $this->mapping = collect($this->headers)->map(function (string $header) use ($attributes, &$used) {
            $target = Columns::guess($header, $attributes);

            return $target && ! in_array($target, $used, true) ? ($used[] = $target) : null;
        })->all();

        $this->goToStep(2);
        $this->form->fill(['map' => $this->mapping, 'update_existing' => true, 'create_brands' => true, 'status' => ProductStatus::Published->value]);
    }

    public function showPreview(): void
    {
        $state = $this->form->getState();
        $mapping = collect($state['map'] ?? [])->map(fn ($target) => filled($target) ? $target : null)->all();
        $targets = array_filter($mapping);

        if (! array_intersect(['name', 'sku'], $targets)) {
            Notification::make()->danger()->title('Choose which column has the product Name or Product code.')->send();

            return;
        }

        if ($duplicates = collect($targets)->duplicates()->unique()->all()) {
            $labels = collect($duplicates)->map(fn ($t) => str_starts_with($t, 'spec:') ? Attribute::find((int) substr($t, 5))?->name : Columns::header($t));
            Notification::make()->danger()->title('Each field can only be used by one column')->body('Used twice: '.$labels->join(', '))->send();

            return;
        }

        $this->mapping = $mapping;
        $this->options = [
            'update_existing' => (bool) ($state['update_existing'] ?? true),
            'create_brands' => (bool) ($state['create_brands'] ?? true),
            'status' => ($state['status'] ?? null) instanceof ProductStatus ? $state['status']->value : ($state['status'] ?? ProductStatus::Published->value),
        ];

        $this->runImport(apply: false);
        $this->goToStep(3);
    }

    public function back(): void
    {
        $this->goToStep(2);
        $this->form->fill(['map' => $this->mapping, ...$this->options]);
    }

    protected function runImport(bool $apply): array
    {
        @set_time_limit(300);

        $sheet = SheetReader::read($this->fullPath());
        $importer = new Importer($sheet['headers'], $this->mapping, $this->options);
        $report = $apply ? ActivityLog::quietly(fn () => $importer->run($sheet['rows'], true)) : $importer->run($sheet['rows'], false);

        if ($apply) {
            ActivityLog::record('imported', label: $this->originalName, type: Product::class, changes: [
                ['field' => 'Products added', 'old' => null, 'new' => number_format($report['summary']['create'])],
                ['field' => 'Products updated', 'old' => null, 'new' => number_format($report['summary']['update'])],
                ['field' => 'Rows with errors', 'old' => null, 'new' => number_format($report['summary']['error'])],
            ]);
        }
        $report['headers'] = $sheet['headers'];

        $this->reportId ??= (string) Str::uuid();
        Storage::disk('local')->put("imports/{$this->reportId}.json", json_encode($report, JSON_UNESCAPED_UNICODE));
        $this->cachedReport = null;

        return $report;
    }

    // ─── Actions ────────────────────────────────────────────────────

    protected function importAction(): Action
    {
        $summary = fn () => $this->report()['summary'] ?? [];
        $count = fn () => ($summary()['create'] ?? 0) + ($summary()['update'] ?? 0);

        return Action::make('import')
            ->label(fn () => $count() ? 'Import '.number_format($count()).' '.Str::plural('product', $count()) : 'Nothing to import')
            ->icon('heroicon-o-arrow-down-tray')
            ->disabled(fn () => $count() === 0)
            ->requiresConfirmation()
            ->modalHeading('Import now?')
            ->modalDescription(fn () => collect([
                ($summary()['create'] ?? 0) ? number_format($summary()['create']).' new' : null,
                ($summary()['update'] ?? 0) ? number_format($summary()['update']).' updated' : null,
                ($summary()['error'] ?? 0) ? number_format($summary()['error']).' rows with errors will be skipped' : null,
            ])->filter()->join(' · ').'.')
            ->modalSubmitActionLabel('Import')
            ->action(function () {
                $report = $this->runImport(apply: true);
                $this->goToStep(4);

                Notification::make()
                    ->success()
                    ->title('Import finished')
                    ->body(number_format($report['summary']['create']).' created, '.number_format($report['summary']['update']).' updated'.($report['summary']['error'] ? ', '.number_format($report['summary']['error']).' with errors' : '').'.')
                    ->send();
            });
    }

    protected function errorReportAction(): Action
    {
        return Action::make('errorReport')
            ->label('Download rows with errors')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->visible(fn () => ($this->report()['summary']['error'] ?? 0) > 0)
            ->action(fn () => $this->downloadErrors());
    }

    protected function templateAction(): Action
    {
        return Action::make('template')
            ->label('Download an empty template')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function () {
                $path = tempnam(sys_get_temp_dir(), 'tf').'.xlsx';
                (new Exporter(withProducts: false))->writeTo($path);

                return response()->download($path, 'technofix-product-template.xlsx')->deleteFileAfterSend();
            });
    }

    protected function startOverAction(): Action
    {
        return Action::make('startOver')
            ->color('gray')
            ->action(function () {
                $this->deleteFiles();
                $this->reset(['path', 'originalName', 'headers', 'samples', 'mapping', 'options', 'reportId']);
                $this->cachedReport = null;
                $this->goToStep(1);
                $this->form->fill();
            });
    }

    public function downloadErrors(): BinaryFileResponse
    {
        $report = $this->report();
        $path = tempnam(sys_get_temp_dir(), 'tf').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues([...$report['headers'], 'Errors'], (new Style)->setFontBold()->setBackgroundColor('FDE2E2')));

        foreach ($report['rows'] as $row) {
            if ($row['action'] === 'error') {
                $writer->addRow(Row::fromValues([...array_map(fn ($v) => $v ?? '', $row['cells'] ?? []), implode(' ', $row['errors'])]));
            }
        }

        $writer->close();

        return response()->download($path, 'import-errors-'.now()->format('Y-m-d-His').'.xlsx')->deleteFileAfterSend();
    }

    // ─── Helpers ────────────────────────────────────────────────────

    /** @var array<string, mixed>|null */
    protected ?array $cachedReport = null;

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        if ($this->cachedReport !== null || ! $this->reportId) {
            return $this->cachedReport ?? [];
        }

        $json = Storage::disk('local')->get("imports/{$this->reportId}.json");

        return $this->cachedReport = $json ? json_decode($json, true) : [];
    }

    protected function summaryHeading(): string
    {
        $s = $this->report()['summary'] ?? [];

        return $this->step === 4
            ? 'Imported: '.number_format($s['create'] ?? 0).' new, '.number_format($s['update'] ?? 0).' updated'
            : 'Preview: '.number_format($s['create'] ?? 0).' new, '.number_format($s['update'] ?? 0).' to update';
    }

    protected function summaryText(): string
    {
        $s = $this->report()['summary'] ?? [];
        $brands = $this->report()['new_brands'] ?? [];

        return collect([
            number_format($s['total'] ?? 0).' rows read from “'.$this->originalName.'”.',
            ($s['unchanged'] ?? 0) ? number_format($s['unchanged']).' already up to date.' : null,
            ($s['error'] ?? 0) ? number_format($s['error']).' '.Str::plural('row', $s['error']).' with errors '.($this->step === 4 ? 'were' : 'will be').' skipped — download them, fix and import again.' : null,
            ($s['warnings'] ?? 0) ? number_format($s['warnings']).' with warnings (see Notes).' : null,
            $brands ? 'New brands: '.implode(', ', $brands).'.' : null,
        ])->filter()->join(' ');
    }

    /** Changing step changes which fields and actions exist, so drop the schemas built for the old step. */
    protected function goToStep(int $step): void
    {
        $this->step = $step;
        $this->cachedSchemas = [];
        $this->resetTable();
    }

    protected function fullPath(): string
    {
        return Storage::disk('local')->path($this->path);
    }

    protected function fail(string $message): void
    {
        $this->deleteFiles();
        $this->path = null;
        $this->form->fill();

        Notification::make()->danger()->title('Can’t use this file')->body($message)->persistent()->send();
    }

    protected function deleteFiles(): void
    {
        Storage::disk('local')->delete(array_filter([$this->path, $this->reportId ? "imports/{$this->reportId}.json" : null]));
    }

    protected function cleanUpOldFiles(): void
    {
        $disk = Storage::disk('local');

        foreach ($disk->files('imports') as $file) {
            if ($disk->lastModified($file) < now()->subDay()->getTimestamp()) {
                $disk->delete($file);
            }
        }
    }
}
