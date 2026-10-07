<?php

namespace App\Support\WarrantySheet;

use App\Models\WarrantyClaim;
use App\Settings\WarrantySettings;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Options\PageOrientation;
use OpenSpout\Writer\XLSX\Options\PageSetup;
use OpenSpout\Writer\XLSX\Options\PaperSize;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes the warranty list in the layout of the shop's "Warranty List" sheet: banner across row 1, headers in row 2,
 * small Calibri text with thin borders, coloured REMARKS, a filter on the headers, landscape and fit to page width.
 */
class Exporter
{
    /** REMARKS colours: status => [fill, text]. */
    public const STATUS_COLORS = [
        'received' => ['EDEDED', '3A3A3A'],
        'claimed' => ['FFEB9C', '9C5700'],
        'ready' => ['C6EFCE', '006100'],
        'delivered' => ['DDEBF7', '1F4E78'],
        'closed' => ['FFC7CE', '9C0006'],
    ];

    /** Texts shown in red, like the original sheet ("TECHNO FIX" = the shop's own stock, "N/A" = unknown). */
    private const RED = '9C0006';

    private const LONG_TEXT = ['product', 'problem', 'customer_note', 'staff_note', 'vendor', 'customer_name'];

    /** @var array<string, Style> */
    private array $styles = [];

    /** @param Builder<WarrantyClaim>|null $query */
    public function __construct(private ?Builder $query = null) {}

    /** @return int rows written */
    public function writeTo(string $path): int
    {
        $fields = array_keys(Columns::FIELDS);
        $last = count($fields) - 1;

        $options = new Options;
        foreach (array_values(Columns::FIELDS) as $i => [, $width]) {
            $options->setColumnWidth($width, $i + 1);
        }
        $options->mergeCells(0, 1, 12, 1); // banner row across the original A–M
        $options->setPageSetup(new PageSetup(PageOrientation::LANDSCAPE, PaperSize::A4, fitToHeight: 0, fitToWidth: 1));

        $writer = new Writer($options);
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Sheet1');
        $sheet->setSheetView((new SheetView)->setFreezeRow(3)->setZoomScale(140));

        // Banner row (71pt). A space, not '': openspout drops empty rows, and the picture would then cover the headers.
        $writer->addRow((new Row([Cell::fromValue(' ')]))->setHeight(71));
        $writer->addRow(new Row(array_map(fn (array $column) => Cell::fromValue($column[0], $this->style('header')), array_values(Columns::FIELDS))));

        $count = 0;
        // Entry order (= the original sheet's row order, new claims at the end) — the old sheet reuses some NOs.
        ($this->query ?? WarrantyClaim::query())->orderBy('id')
            ->chunk(300, function ($claims) use ($writer, &$count) {
                foreach ($claims as $claim) {
                    $writer->addRow(new Row($this->cellsFor($claim)));
                    $count++;
                }
            });

        $sheet->setAutoFilter(new AutoFilter(0, 2, $last, max(3, $count + 2)));
        $writer->close();

        if ($banner = app(WarrantySettings::class)->bannerPath()) {
            XlsxImage::insert($path, $banner, column: 4, heightPx: 90, offsetPx: 30); // ≈ centred over A–M, like the original
        }

        return $count;
    }

    /** @return list<Cell> */
    protected function cellsFor(WarrantyClaim $claim): array
    {
        $cells = [];

        foreach (array_keys(Columns::FIELDS) as $field) {
            [$value, $style] = match ($field) {
                'number', 'qty', 'id' => [(int) $claim->{$field}, 'center'],
                'received_on', 'claimed_on', 'returned_on' => $claim->{$field}
                    ? [DateTimeImmutable::createFromInterface($claim->{$field}), 'date']
                    : ['-', 'center'],
                'customer_name' => [$claim->customer_name, $claim->is_internal ? 'red' : 'text'],
                'customer_phone' => $claim->customer_phone ? [$claim->customer_phone, 'center'] : ['N/A', 'red-center'],
                'serial' => filled($claim->serial) ? [$claim->serial, 'center'] : ['N/A', 'red-center'],
                'vendor', 'new_serial' => [filled($claim->{$field}) ? $claim->{$field} : '-', $field === 'vendor' ? 'text' : 'center'],
                'status' => [$claim->status->sheetValue(), 'status-'.$claim->status->value],
                'is_internal' => [$claim->is_internal ? 'YES' : '', 'center'],
                default => [(string) $claim->{$field}, 'text'],
            };

            $cells[] = Cell::fromValue($value, $this->style($style, in_array($field, self::LONG_TEXT, true)));
        }

        return $cells;
    }

    protected function style(string $name, bool $wrap = false): Style
    {
        return $this->styles[$name.($wrap ? ':wrap' : '')] ??= tap((new Style)
            ->setFontName('Calibri')
            ->setFontSize(7)
            ->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
            ->setBorder(new Border(...array_map(fn (string $side) => new BorderPart($side, '000000', Border::WIDTH_THIN, Border::STYLE_SOLID),
                [Border::LEFT, Border::RIGHT, Border::TOP, Border::BOTTOM]))), function (Style $style) use ($name, $wrap) {
                    if ($wrap) {
                        $style->setShouldWrapText();
                    }

                    match (true) {
                        $name === 'header' => $style->setFontBold()->setBackgroundColor('D9E1F2')->setCellAlignment(CellAlignment::CENTER)->setShouldWrapText(),
                        $name === 'date' => $style->setFormat(Columns::DATE_FORMAT)->setCellAlignment(CellAlignment::CENTER),
                        $name === 'center' => $style->setCellAlignment(CellAlignment::CENTER),
                        $name === 'red' => $style->setFontColor(self::RED),
                        $name === 'red-center' => $style->setFontColor(self::RED)->setCellAlignment(CellAlignment::CENTER),
                        str_starts_with($name, 'status-') => $style->setFontBold()->setCellAlignment(CellAlignment::CENTER)
                            ->setBackgroundColor(self::STATUS_COLORS[substr($name, 7)][0] ?? 'FFFFFF')
                            ->setFontColor(self::STATUS_COLORS[substr($name, 7)][1] ?? '000000'),
                        default => null,
                    };
                });
    }

    /** "warranty-list-2026-10-07.xlsx" */
    public static function fileName(): string
    {
        return 'warranty-list-'.now()->format('Y-m-d').'.xlsx';
    }
}
