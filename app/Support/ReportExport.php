<?php

namespace App\Support;

use App\Filament\Widgets\OrderSources;
use App\Models\Order;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The sales report as an Excel file: one sheet per report, plus every order of the period (for the accounts).
 */
class ReportExport
{
    public function __construct(private Reports $report) {}

    public function fileName(): string
    {
        return 'technofix-sales-'.$this->report->from->format('Y-m-d').'-to-'.$this->report->to->format('Y-m-d').'.xlsx';
    }

    /** Writes the file and returns its path. */
    public function write(string $path): string
    {
        $options = new Options;
        $options->setColumnWidthForRange(34, 1, 1);
        $options->setColumnWidthForRange(16, 2, 16);

        $writer = new Writer($options);
        $writer->openToFile($path);
        $r = $this->report;
        $s = $r->summary();

        $this->sheet($writer, 'Summary', ['Figure', 'Value'], [
            ['Report', $r->label()],
            ['Orders', $s['orders']],
            ['Sales (BDT)', $s['sales']],
            ['Products (BDT)', $s['subtotal']],
            ['Delivery charges (BDT)', $s['delivery']],
            ['Discounts (BDT)', $s['discount']],
            ['Average order (BDT)', $s['average']],
            ['Items sold', $s['items']],
            ['Customers (by mobile)', $s['buyers']],
            ['Collected (BDT)', $s['collected']],
            ['Still to collect (BDT)', $s['to_collect']],
            ['Estimated profit (BDT)', $s['profit']],
            ['Sales with a cost price (%)', $s['profit_coverage']],
            ['Cancelled orders', $s['cancelled']],
            ['Cancelled value (BDT)', $s['cancelled_value']],
            ['Returned orders', $s['returned']],
            ['Returned value (BDT)', $s['returned_value']],
        ], first: true);

        $this->sheet($writer, 'By day', ['Date', 'Orders', 'Sales (BDT)'], $r->daily()->map(fn ($d) => [$d['day']->format('Y-m-d'), $d['orders'], $d['sales']]));
        $this->sheet($writer, 'Products', ['Product', 'Code', 'Category', 'Brand', 'Qty', 'Sales (BDT)', 'Cost (BDT)', 'Profit (BDT)'],
            $r->products()->map(fn ($p) => [$p['name'], $p['sku'], $p['category'], $p['brand'], $p['qty'], $p['revenue'], $p['cost'], $p['profit']]));
        $this->sheet($writer, 'Categories', ['Category', 'Qty', 'Sales (BDT)', 'Share %'], $r->byCategory()->map(fn ($c) => [$c['name'], $c['qty'], $c['revenue'], $c['share']]));
        $this->sheet($writer, 'Brands', ['Brand', 'Qty', 'Sales (BDT)', 'Share %'], $r->byBrand()->map(fn ($c) => [$c['name'], $c['qty'], $c['revenue'], $c['share']]));
        $this->sheet($writer, 'Came from', ['Channel', 'Orders', 'Sales (BDT)', 'Share %'], $r->byChannel()->map(fn ($c) => [$c['name'], $c['orders'], $c['revenue'], $c['share']]));
        $this->sheet($writer, 'Districts', ['District', 'Orders', 'Sales (BDT)', 'Share %'], $r->byDistrict()->map(fn ($c) => [$c['name'], $c['orders'], $c['revenue'], $c['share']]));
        $this->sheet($writer, 'Staff', ['Staff member', 'Orders taken', 'Taken value (BDT)', 'Confirmed', 'Marked delivered'],
            $r->byStaff()->map(fn ($u) => [$u['name'], $u['taken'], $u['taken_value'], $u['confirmed'], $u['delivered']]));
        $this->sheet($writer, 'Coupons', ['Coupon', 'Orders', 'Discount (BDT)', 'Sales (BDT)'], $r->byCoupon()->map(fn ($c) => [$c['name'], $c['orders'], $c['discount'], $c['revenue']]));

        // Every order of the period.
        $writer->addNewSheetAndMakeItCurrent()->setName('Orders')->setSheetView((new SheetView)->setFreezeRow(2));
        $writer->addRow($this->header(['Order', 'Placed', 'Status', 'Customer', 'Mobile', 'District', 'Came from', 'Items', 'Products (BDT)', 'Delivery (BDT)', 'Discount (BDT)', 'Total (BDT)', 'Paid (BDT)', 'Payment', 'Coupon']));
        $r->orders()->withCount('items')->orderBy('id')->chunkById(500, function (Collection $orders) use ($writer) {
            foreach ($orders as $order) {
                /** @var Order $order */
                $writer->addRow(Row::fromValues([
                    $order->number, $order->created_at->format('Y-m-d H:i'), $order->status->getLabel(), $order->name, $order->phone,
                    $order->is_pickup ? 'Store pickup' : $order->district, OrderSources::channel($order), $order->items_count,
                    $order->subtotal, $order->delivery_fee, $order->discount, $order->total, $order->paid_amount,
                    $order->payment_status->getLabel(), $order->coupon_code,
                ]));
            }
        });

        $writer->close();

        return $path;
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<array<int, mixed>>  $rows
     */
    private function sheet(Writer $writer, string $name, array $headers, iterable $rows, bool $first = false): void
    {
        $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName($name)->setSheetView((new SheetView)->setFreezeRow(2));
        $writer->addRow($this->header($headers));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_values($row)));
        }
    }

    /**
     * @param  list<string>  $headers
     */
    private function header(array $headers): Row
    {
        return Row::fromValues($headers, (new Style)->setFontBold()->setBackgroundColor('DCEBF7'));
    }
}
