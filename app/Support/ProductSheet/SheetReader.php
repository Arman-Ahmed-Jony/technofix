<?php

namespace App\Support\ProductSheet;

use DateTimeInterface;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

/**
 * Reads the first sheet of an .xlsx or .csv file into a header row and data rows (keyed by spreadsheet row number).
 */
class SheetReader
{
    /**
     * @return array{headers: list<string>, rows: array<int, list<?string>>}
     */
    public static function read(string $path, int $maxRows = Importer::MAX_ROWS): array
    {
        $reader = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xlsx' => new XlsxReader,
            'csv', 'txt' => new CsvReader(self::csvOptions($path)),
            default => throw new RuntimeException('Please upload an Excel (.xlsx) or CSV file.'),
        };

        $headers = null;
        $rows = [];
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $number = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $number++;
                    $cells = array_map(self::cellValue(...), $row->toArray());

                    if (! array_filter($cells, fn ($value) => $value !== null)) {
                        continue; // blank line
                    }

                    if ($headers === null) {
                        $headers = array_map(fn ($value) => trim((string) $value), $cells);

                        continue;
                    }

                    if (count($rows) >= $maxRows) {
                        throw new RuntimeException("The file has more than {$maxRows} products. Please split it into smaller files.");
                    }

                    $rows[$number] = array_pad(array_slice($cells, 0, count($headers)), count($headers), null);
                }

                break; // first sheet only (exports put the instructions on the second sheet)
            }
        } finally {
            $reader->close();
        }

        if (! $headers) {
            throw new RuntimeException('The file is empty.');
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    protected static function cellValue(mixed $value): ?string
    {
        $value = match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_float($value) && floor($value) === $value && abs($value) < PHP_INT_MAX => (string) (int) $value,
            is_bool($value) => $value ? 'Yes' : 'No',
            default => $value,
        };

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** Excel saves CSV with ";" or tabs in some regions and often not as UTF-8. */
    protected static function csvOptions(string $path): CsvOptions
    {
        $content = (string) file_get_contents($path); // uploads are capped at 10 MB
        $firstLine = strtok($content, "\n") ?: '';

        $options = new CsvOptions;
        $options->FIELD_DELIMITER = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($firstLine, $d))->first();
        $options->ENCODING = mb_check_encoding($content, 'UTF-8') ? 'UTF-8' : 'Windows-1252';

        return $options;
    }
}
