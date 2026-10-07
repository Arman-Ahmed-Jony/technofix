<?php

namespace App\Support\WarrantySheet;

use App\Enums\WarrantyStatus;
use App\Models\WarrantyClaim;
use App\Support\ActivityLog;
use App\Support\Phone;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Throwable;

/**
 * Reads a warranty sheet — the shop's original "Warranty List" or a download from the admin — into warranty claims.
 *
 * Original sheet quirks: rows without a NO/DESCRIPTION continue the entry above (their text becomes a staff note; a
 * REMARKS there sets the status); Excel turned some typed "dd-mm-yy" dates into real dates with day and month swapped;
 * contacts lost their leading 0; "TECHNO FIX" rows are the shop's own stock (hidden from customers).
 * A download has a SYSTEM ID column: those rows update the same claims. Rows already imported are skipped.
 */
class Importer
{
    public const MAX_ROWS = 20000;

    private const EMPTY = ['', '-', '--', 'N/A', 'NA', 'NONE', 'NULL'];

    /** @var array<int, string> column index => field */
    private array $map = [];

    /** @var array<int, string> column index => header text */
    private array $headers = [];

    private bool $ownFormat = false;

    /** @var array{created: int, updated: int, skipped: int, notes: int, problems: list<string>} */
    private array $report = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'notes' => 0, 'problems' => []];

    public function __construct(private string $shopName = 'TECHNO FIX') {}

    /** @return array{created: int, updated: int, skipped: int, notes: int, problems: list<string>} */
    public function import(string $path, string $label = 'warranty sheet'): array
    {
        $entries = $this->entries($path);

        ActivityLog::quietly(fn () => DB::transaction(function () use ($entries) {
            foreach ($entries as $entry) {
                $this->save($entry);
            }
        }));

        ActivityLog::record('warranty_imported', label: $label, type: WarrantyClaim::class, changes: [
            ['field' => 'Claims added', 'old' => null, 'new' => number_format($this->report['created'])],
            ['field' => 'Claims updated', 'old' => null, 'new' => number_format($this->report['updated'])],
            ['field' => 'Rows skipped (already there)', 'old' => null, 'new' => number_format($this->report['skipped'])],
        ]);

        return $this->report;
    }

    /**
     * The sheet's entries (continuation rows already merged), with the row they start on.
     *
     * @return list<array{row: int, values: array<string, mixed>, notes: list<string>, remarks: ?string}>
     */
    public function entries(string $path): array
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw new RuntimeException('Please upload the warranty sheet as an Excel (.xlsx) file.');
        }

        $reader = new Reader;
        $reader->open($path);
        $entries = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $number = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $number++;
                    $cells = array_map(fn ($cell) => $cell->getValue(), $row->getCells());

                    if (! $this->map) {
                        $this->findHeaders($cells);

                        continue;
                    }

                    if (count($entries) >= self::MAX_ROWS) {
                        throw new RuntimeException('The file has more than '.self::MAX_ROWS.' rows. Please split it.');
                    }

                    $values = [];
                    foreach ($this->map as $index => $field) {
                        $value = $cells[$index] ?? null;
                        $values[$field] = is_string($value) ? trim($value) : $value;
                    }

                    $filled = array_filter($values, fn ($value) => ! $this->isEmpty($value));
                    if (! $filled) {
                        continue;
                    }

                    // A line without NO and DATE (or without NO and DESCRIPTION) adds to the entry above ("FULL BOX",
                    // "(KHALIL VAI)", charges…). A complete new line typed without a NO still counts as an entry.
                    if ($this->isEmpty($values['number'] ?? null)
                        && ($this->isEmpty($values['received_on'] ?? null) || $this->isEmpty($values['product'] ?? null))) {
                        if ($entries) {
                            $this->continueEntry($entries[array_key_last($entries)], $filled);
                        }

                        continue;
                    }

                    // Pre-numbered empty lines (or stray text) at the end of the sheet.
                    if ($this->isEmpty($values['product'] ?? null)) {
                        continue;
                    }

                    $entries[] = ['row' => $number, 'values' => $values, 'notes' => [], 'remarks' => null];
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
        }

        if (! $this->map) {
            throw new RuntimeException('This doesn’t look like the warranty sheet: no header row with DESCRIPTION and REMARKS (or PERSON / CONTACT) was found.');
        }

        return $entries;
    }

    /** @param list<mixed> $cells */
    protected function findHeaders(array $cells): void
    {
        $map = [];
        foreach ($cells as $index => $value) {
            if (is_string($value) && ($field = Columns::fieldFor($value))) {
                $map[$index] = $field;
                $this->headers[$index] = Str::of($value)->trim()->lower()->ucfirst()->toString();
            }
        }

        if (count($map) >= 4 && in_array('product', $map, true)) {
            $this->map = $map;
            $this->ownFormat = in_array('id', $map, true) || in_array('is_internal', $map, true);
        }
    }

    /**
     * @param  array{row: int, values: array<string, mixed>, notes: list<string>, remarks: ?string}  $entry
     * @param  array<string, mixed>  $filled
     */
    protected function continueEntry(array &$entry, array $filled): void
    {
        foreach ($filled as $field => $value) {
            if ($field === 'status') {
                $parent = $this->text($entry['values']['status'] ?? null) ?? $entry['remarks'];
                if ($parent === null) {
                    $entry['remarks'] = $this->text($value);

                    continue;
                }
                if (strcasecmp($parent, (string) $this->text($value)) === 0) {
                    continue; // repeats the status above
                }
            }

            $label = $this->headers[array_search($field, $this->map, true)] ?? $field;
            $entry['notes'][] = $label.': '.$this->text($value);
        }
    }

    /** @param array{row: int, values: array<string, mixed>, notes: list<string>, remarks: ?string} $entry */
    protected function save(array $entry): void
    {
        $v = $entry['values'];
        $notes = $entry['notes'];

        try {
            $phone = $this->text($v['customer_phone'] ?? null);
            $normalized = Phone::normalize($phone);
            if ($phone !== null && ! Phone::isValid($normalized)) {
                $notes[] = 'Contact: '.$phone;
                $normalized = null;
            }

            $email = $this->text($v['customer_email'] ?? null);
            if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $notes[] = 'Email: '.$email;
                $email = null;
            }

            $name = $this->text($v['customer_name'] ?? null) ?? 'Unknown';
            $claimedOn = $this->date($v['claimed_on'] ?? null);
            $internal = $this->ownFormat && array_key_exists('is_internal', $v)
                ? in_array(strtoupper((string) $this->text($v['is_internal'])), ['YES', 'Y', '1', 'TRUE'], true)
                : str_contains(strtoupper($name), strtoupper($this->shopName));

            $attributes = [
                'number' => is_numeric($v['number'] ?? null) ? (int) $v['number'] : null,
                'received_on' => $this->date($v['received_on'] ?? null),
                'customer_name' => Str::limit($name, 120, ''),
                'customer_phone' => $normalized ?: null,
                'customer_email' => $email,
                'is_internal' => $internal,
                'product' => Str::limit((string) $this->text($v['product']), 255, ''),
                'qty' => max(1, min(9999, (int) (is_numeric($v['qty'] ?? null) ? $v['qty'] : 1))),
                'serial' => $this->serial($v['serial'] ?? null),
                'problem' => $this->lines($v['problem'] ?? null),
                'vendor' => $this->text($v['vendor'] ?? null),
                'claimed_on' => $claimedOn,
                'returned_on' => $this->date($v['returned_on'] ?? null),
                'new_serial' => $this->serial($v['new_serial'] ?? null),
                'status' => WarrantyStatus::fromSheet($this->text($v['status'] ?? null) ?? $entry['remarks'], $claimedOn !== null),
            ];

            if (array_key_exists('customer_note', $v)) {
                $attributes['customer_note'] = $this->lines($v['customer_note']);
            }
            $staffNote = collect([$this->lines($v['staff_note'] ?? null), ...$notes])->filter()->unique()->join("\n");
            if ($staffNote !== '' || array_key_exists('staff_note', $v)) {
                $attributes['staff_note'] = $staffNote ?: null;
            }
            $this->report['notes'] += count($entry['notes']);

            $claim = is_numeric($v['id'] ?? null) ? WarrantyClaim::find((int) $v['id']) : null;

            if (! $claim && $this->alreadyImported($attributes)) {
                $this->report['skipped']++;

                return;
            }

            $claim ??= new WarrantyClaim;
            $claim->fill($attributes);

            if (! $claim->exists || $claim->isDirty('status')) {
                $claim->forceFill(['status_changed_at' => ($attributes['returned_on'] ?? $claimedOn ?? $attributes['received_on'] ?? now())]);
            }

            if ($claim->exists && ! $claim->isDirty()) {
                $this->report['skipped']++;

                return;
            }

            $this->report[$claim->exists ? 'updated' : 'created']++;
            $claim->save();
        } catch (Throwable $e) {
            $this->report['problems'][] = "Row {$entry['row']}: ".Str::limit($e->getMessage(), 160);
        }
    }

    /** @param array<string, mixed> $attributes */
    protected function alreadyImported(array $attributes): bool
    {
        return WarrantyClaim::query()
            ->where('product', $attributes['product'])
            ->where('customer_name', $attributes['customer_name'])
            ->when($attributes['number'], fn ($q, $number) => $q->where('number', $number))
            ->when($attributes['received_on'], fn ($q, $date) => $q->whereDate('received_on', $date), fn ($q) => $q->whereNull('received_on'))
            ->when($attributes['serial'], fn ($q, $serial) => $q->where('serial', $serial), fn ($q) => $q->whereNull('serial'))
            ->exists();
    }

    protected function date(mixed $value): ?CarbonImmutable
    {
        // A date cell without a date format reads as Excel's day number (44599 = 7 Feb 2022).
        if ((is_int($value) || is_float($value)) && $value > 20000 && $value < 80000) {
            $value = CarbonImmutable::create(1899, 12, 30)->addDays((int) $value);
        }

        if ($value instanceof DateTimeInterface) {
            $date = CarbonImmutable::instance($value)->startOfDay();

            // The original sheet: typed "04-06-22" became 6 April — swap back when that's possible.
            return ! $this->ownFormat && $date->day <= 12 ? $date->setDate($date->year, $date->day, $date->month) : $date;
        }

        $text = $this->text($value);
        if ($text === null) {
            return null;
        }

        if (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})$/', $text, $m)) {
            $year = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];

            return checkdate((int) $m[2], (int) $m[1], $year) ? CarbonImmutable::create($year, (int) $m[2], (int) $m[1]) : null;
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $text, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3]) : null;
        }

        return null;
    }

    protected function serial(mixed $value): ?string
    {
        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        $text = $this->text($value);

        return $text === null || in_array(strtoupper($text), ['UNKNOWN'], true) ? $text : Str::limit($text, 120, '');
    }

    protected function text(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d-m-y');
        }

        if (is_float($value) && floor($value) === $value) {
            $value = number_format($value, 0, '', '');
        }

        $text = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $this->isEmpty($text) ? null : $text;
    }

    /** Like text(), but keeps line breaks (notes, problem). */
    protected function lines(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $this->text($value);
        }

        $text = collect(preg_split('/\R/u', $value))
            ->map(fn (string $line) => trim(preg_replace('/[ \t]+/u', ' ', $line)))
            ->filter(fn (string $line) => $line !== '')
            ->join("\n");

        return $this->isEmpty($text) ? null : $text;
    }

    protected function isEmpty(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if ($value instanceof DateTimeInterface || is_int($value) || is_float($value) || is_bool($value)) {
            return false;
        }

        return in_array(strtoupper(trim((string) $value)), self::EMPTY, true);
    }
}
