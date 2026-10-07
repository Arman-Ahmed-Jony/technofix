<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Phone;
use App\Support\Turnstile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Customers look up an order with its number and their mobile number. A found order is remembered in the session,
 * so its page (/track-order/{number}) only opens for someone who proved both.
 */
class TrackOrderController extends Controller
{
    /** Customer-facing steps: status => [title, text]. */
    public const STEPS = [
        'pending' => ['Order received', 'We’ll call you shortly to confirm it.'],
        'confirmed' => ['Confirmed', 'Your order is confirmed and waiting to be packed.'],
        'processing' => ['Packing', 'Your items are being checked, tested and packed.'],
        'shipped' => ['On the way', 'Handed to the courier — they’ll call before delivery.'],
        'delivered' => ['Delivered', 'Enjoy your purchase!'],
    ];

    public function form(): View
    {
        return view('storefront.track-order');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')), 'number' => Str::upper(trim((string) $request->input('number')))]);
        $request->validate(
            ['number' => ['required', 'string', 'max:20'], 'phone' => ['required', 'regex:'.Phone::PATTERN], ...Turnstile::rules('track_order')],
            [...Turnstile::messages(), 'number.required' => 'Enter your order number.', 'phone.required' => 'Enter the mobile number used for the order.', 'phone.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.'],
        );

        $order = Order::where('number', $request->input('number'))->where('phone', $request->input('phone'))->first();

        if (! $order) {
            return back()->withInput()->withErrors(['number' => 'We couldn’t find an order with that number and mobile number. Please check both, or call us.']);
        }

        $request->session()->push('tracked_orders', $order->id);

        return redirect()->route('orders.track.show', $order->number);
    }

    public function show(Request $request, string $number): View|RedirectResponse
    {
        $order = Order::with('items.product', 'history')->where('number', $number)->first();
        $allowed = $order && (in_array($order->id, (array) $request->session()->get('tracked_orders', []), true) || $request->session()->get('last_order_id') === $order->id);

        if (! $allowed) {
            return redirect()->route('orders.track');
        }

        return view('storefront.track-order-show', self::viewData($order, [
            ['label' => 'Track order', 'url' => route('orders.track')],
            ['label' => $order->number, 'url' => null],
        ]));
    }

    /**
     * Timeline data for an order page (also used by the customer account).
     *
     * @param  list<array{label: string, url: ?string}>  $breadcrumbs
     * @return array<string, mixed>
     */
    public static function viewData(Order $order, array $breadcrumbs): array
    {
        // When each step was reached (latest time the order entered that status). Notes that didn't change the status
        // (payments, item edits) don't count.
        $reached = $order->history
            ->filter(fn ($h) => $h->from_status !== $h->to_status)
            ->groupBy(fn ($h) => $h->to_status->value)
            ->map(fn ($entries) => $entries->max('created_at'));
        $current = array_search($order->status->value, array_keys(self::STEPS), true);

        return [
            'order' => $order,
            'breadcrumbs' => $breadcrumbs,
            'steps' => collect(self::STEPS)->map(fn (array $step, string $status) => [
                'title' => $step[0],
                'text' => $step[1],
                'at' => $reached[$status] ?? null,
                'done' => $current !== false && array_search($status, array_keys(self::STEPS), true) <= $current,
                'current' => $status === $order->status->value,
            ])->values(),
            'stopped' => in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Returned], true),
        ];
    }
}
