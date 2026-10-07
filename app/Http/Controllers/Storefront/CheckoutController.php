<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\InvalidCoupon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Models\CheckoutDraft;
use App\Models\Order;
use App\Models\Product;
use App\Payments\PaymentGateways;
use App\Services\CouponService;
use App\Services\IncompleteCheckouts;
use App\Services\OrderService;
use App\Settings\DeliverySettings;
use App\Settings\PaymentSettings;
use App\Support\Phone;
use App\Support\Tracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, DeliverySettings $delivery, PaymentGateways $gateways, PaymentSettings $payments, IncompleteCheckouts $incomplete): View
    {
        $customer = $request->user('customer');

        return view('storefront.checkout', [
            'districts' => require database_path('data/bd-districts.php'),
            'deliveryOptions' => array_values($delivery->active()),
            'freeOver' => $delivery->free_delivery_over,
            'paymentMethods' => $gateways->available(),
            'comingSoon' => $payments->coming_soon,
            'customer' => $customer,
            'addresses' => $customer ? $customer->addresses()->get()->map->forCheckout()->all() : [],
            'draftUrl' => $incomplete->enabled() ? route('checkout.draft') : null,
            'restoreCart' => session('restore_cart'),
        ]);
    }

    /** What the customer has typed so far, kept in case they leave without ordering (IncompleteCheckouts). */
    public function draft(Request $request, IncompleteCheckouts $incomplete): Response
    {
        $incomplete->save($request);

        return response()->noContent();
    }

    /** The "finish your order" link staff send: refills the cart and the details, then opens the checkout. */
    public function resume(Request $request, string $token): RedirectResponse
    {
        $draft = CheckoutDraft::where('token', $token)->whereNull('order_id')->first();

        if (! $draft || ! ($items = $draft->cartItems())) {
            return redirect()->route('cart');
        }

        $request->session()->put(IncompleteCheckouts::SESSION_KEY, $draft->id);

        return redirect()->route('checkout')
            ->withInput(array_filter($draft->only(['name', 'phone', 'email', 'district', 'area', 'address', 'note']) + ['delivery' => $draft->delivery_code]))
            ->with('restore_cart', $items);
    }

    /**
     * Saves the order (priced from the catalog, stock taken), then hands over to the payment method: online
     * gateways send the customer to pay, cash on delivery goes straight to the confirmation. The order id is kept
     * in the session, so only this visitor can see it.
     */
    public function store(PlaceOrderRequest $request, OrderService $orders, PaymentGateways $gateways): RedirectResponse
    {
        $order = $orders->placeFromCheckout($request->validated(), $request);

        $request->session()->put('last_order_id', $order->id);
        app(Tracking::class)->purchase($order, $request);

        $payUrl = $gateways->for($order->payment_method)?->start($order);

        return $payUrl ? redirect()->away($payUrl) : redirect()->route('checkout.success');
    }

    /**
     * Checks a coupon for the checkout's live summary. The subtotal is priced from the catalog here, and the order
     * is checked again when it is placed; the rule is returned so the summary can follow cart and delivery changes.
     */
    public function coupon(Request $request, CouponService $coupons): JsonResponse
    {
        $items = json_decode((string) $request->input('items', ''), true);
        $qty = collect(is_array($items) ? $items : [])
            ->filter(fn ($item) => is_array($item) && is_numeric($item['id'] ?? null) && is_numeric($item['qty'] ?? null))
            ->groupBy(fn (array $item) => (int) $item['id'])
            ->map(fn ($rows) => min(99, max(0, (int) $rows->sum('qty'))));
        $subtotal = Product::query()->published()->findMany($qty->keys()->take(50))
            ->sum(fn (Product $product) => (int) $product->sale_price * $qty[$product->id]);
        $phone = Phone::normalize($request->input('phone'));

        try {
            $coupon = $coupons->check((string) $request->input('code', ''), $subtotal, Phone::isValid($phone) ? $phone : null);
        } catch (InvalidCoupon $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['coupon' => $coupon->forCheckout()]);
    }

    public function success(): View|RedirectResponse
    {
        $order = Order::with('items.product')->find(session('last_order_id'));

        return $order ? view('storefront.checkout-success', ['order' => $order]) : redirect()->route('home');
    }
}
