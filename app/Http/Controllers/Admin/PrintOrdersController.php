<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffArea;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Settings\StoreSettings;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Print-ready invoices and packing slips (A4, one order per page) for staff. The browser's print dialog can also
 * save them as PDF.
 */
class PrintOrdersController extends Controller
{
    public const TYPES = ['invoice' => 'Invoice', 'slip' => 'Packing slip'];

    public function __invoke(Request $request, StoreSettings $store): View
    {
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')) && StaffArea::Orders->allowed(), 403);

        $type = array_key_exists($request->query('type'), self::TYPES) ? $request->query('type') : 'invoice';
        $ids = collect(explode(',', (string) $request->query('ids')))->map(fn ($id) => (int) $id)->filter()->unique()->take(200);
        $orders = Order::with('items')->whereKey($ids->all())->get()->sortBy(fn (Order $o) => $ids->search($o->id))->values();

        abort_if($orders->isEmpty(), 404);

        return view('print.orders', [
            'orders' => $orders,
            'type' => $type,
            'store' => $store,
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    /**
     * @param  iterable<Order>|Order  $orders
     */
    public static function url(iterable|Order $orders, string $type = 'invoice'): string
    {
        $ids = collect($orders instanceof Order ? [$orders] : $orders)->pluck('id')->join(',');

        return route('orders.print', ['type' => $type, 'ids' => $ids, 'autoprint' => 1]);
    }
}
