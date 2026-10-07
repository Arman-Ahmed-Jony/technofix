<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\InvalidCoupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Settings\DeliverySettings;
use App\Support\Tracking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Placing orders and moving them through their statuses. Stock is taken when an order is placed and given back
 * when it is cancelled or returned (and taken again if it is re-opened).
 */
class OrderService
{
    /** A second identical checkout within this many minutes returns the first order instead of a duplicate. */
    public const DUPLICATE_WINDOW_MINUTES = 10;

    public function __construct(protected DeliverySettings $delivery, protected OrderNotifier $notifier, protected CouponService $coupons, protected PaymentService $payments, protected IncompleteCheckouts $incomplete) {}

    /**
     * @param  array<string, mixed>  $data  validated checkout data (PlaceOrderRequest); prices are always taken from the catalog
     */
    public function placeFromCheckout(array $data, Request $request): Order
    {
        $products = Product::query()->published()->with('colors')->findMany(collect($data['items'])->pluck('id'))->keyBy('id');

        // Merge duplicate lines (same product and color); price everything from the catalog — never from the browser.
        $lines = collect($data['items'])
            ->map(fn (array $item) => [...$item, 'color' => $products[(int) $item['id']]->pickColor($item['color'] ?? null)])
            ->groupBy(fn (array $item) => (int) $item['id'].'|'.$item['color'])
            ->map(function ($rows) use ($products) {
                $product = $products[(int) $rows->first()['id']];
                $qty = min(99, (int) $rows->sum('qty'));

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'color' => $rows->first()['color'],
                    'sku' => $product->sku,
                    'warranty' => $product->warranty,
                    'price' => $product->sale_price, // campaign deal price while a sale runs
                    'qty' => $qty,
                    'total' => $product->sale_price * $qty,
                ];
            })
            ->sortBy(fn (array $line) => $line['product_id'].'|'.$line['color'])
            ->values();

        $subtotal = $lines->sum('total');
        $option = $this->delivery->find($data['delivery']);
        $isPickup = $option['type'] === 'pickup';
        $deliveryFee = $this->delivery->feeFor($option, $subtotal);

        $fingerprint = hash('sha256', json_encode([$data['phone'], $lines->map(fn (array $l) => [$l['product_id'], $l['color'], $l['qty']])->all(), $option['code'], $data['address'] ?? null]));
        $duplicate = Order::where('fingerprint', $fingerprint)->where('created_at', '>=', now()->subMinutes(self::DUPLICATE_WINDOW_MINUTES))->first();

        if ($duplicate) {
            return $duplicate;
        }

        $order = DB::transaction(function () use ($data, $request, $lines, $subtotal, $option, $isPickup, $deliveryFee, $fingerprint) {
            // Checked again here (row locked), whatever the checkout showed: the last use can't be taken twice.
            $coupon = null;

            if (filled($data['coupon'] ?? null)) {
                try {
                    $coupon = $this->coupons->check($data['coupon'], $subtotal, $data['phone'], lock: true);
                } catch (InvalidCoupon $e) {
                    throw ValidationException::withMessages(['coupon' => $e->getMessage()]);
                }
            }

            $discount = $coupon ? CouponService::discount($coupon, $subtotal, $deliveryFee) : 0;

            $order = Order::createWithNumber([
                'status' => OrderStatus::Pending,
                'payment_method' => PaymentMethod::from($data['payment']),
                'payment_status' => PaymentStatus::Unpaid,
                'source' => OrderSource::Web,
                'customer_id' => $request->user('customer')?->id,
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'delivery_code' => $option['code'],
                'delivery_label' => $option['label'],
                'delivery_eta' => $option['eta'] ?: null,
                'is_pickup' => $isPickup,
                'district' => $isPickup ? null : $data['district'],
                'area' => $isPickup ? null : $data['area'],
                'address' => $isPickup ? null : $data['address'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'total' => $subtotal + $deliveryFee - $discount,
                'customer_note' => $data['note'] ?? null,
                'fingerprint' => $fingerprint,
                'attribution' => Tracking::attribution($request),
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
            ]);

            $order->items()->createMany($lines->all());
            $order->history()->create(['to_status' => OrderStatus::Pending, 'note' => 'Placed on the website'.($coupon ? " with coupon {$coupon->code}" : '').'.']);
            $this->takeStock($order);

            if (($customer = $request->user('customer')) && ! $isPickup && ! empty($data['save_address'])) {
                $this->saveAddress($customer, $data);
            }

            return $order;
        });

        $this->notifier->placed($order);
        $this->incomplete->ordered($order, $request->session()->pull(IncompleteCheckouts::SESSION_KEY));

        return $order;
    }

    /**
     * An order taken by staff (phone, Facebook, WhatsApp, walk-in). Staff may set the unit prices (e.g. an agreed
     * price) and the delivery charge; subtotal and total are always calculated here from the lines.
     *
     * @param  array<string, mixed>  $data  items[product_id, price, qty], delivery_code, delivery_fee, discount, status, source, customer + address fields
     */
    public function createManual(array $data, ?User $by = null): Order
    {
        $lines = $this->linesFrom($data['items']);
        $option = $this->delivery->find($data['delivery_code']) ?? throw new \InvalidArgumentException('Unknown delivery option.');
        $isPickup = $option['type'] === 'pickup';
        $status = $data['status'] instanceof OrderStatus ? $data['status'] : OrderStatus::from($data['status'] ?? OrderStatus::Confirmed->value);
        $source = $data['source'] instanceof OrderSource ? $data['source'] : OrderSource::from($data['source'] ?? OrderSource::Phone->value);
        [$subtotal, $fee, $discount, $total] = $this->totals($lines, $data['delivery_fee'] ?? $option['fee'], $data['discount'] ?? 0);

        $order = DB::transaction(function () use ($data, $by, $lines, $option, $isPickup, $status, $source, $subtotal, $fee, $discount, $total) {
            $order = Order::createWithNumber([
                'status' => $status,
                'payment_method' => PaymentMethod::CashOnDelivery,
                'payment_status' => PaymentStatus::Unpaid,
                'source' => $source,
                // Staff spoke to this customer, so a registered account with the same mobile gets the order.
                'customer_id' => Customer::where('phone', $data['phone'])->value('id'),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'delivery_code' => $option['code'],
                'delivery_label' => $option['label'],
                'delivery_eta' => $option['eta'] ?: null,
                'is_pickup' => $isPickup,
                'district' => $isPickup ? null : ($data['district'] ?? null),
                'area' => $isPickup ? null : ($data['area'] ?? null),
                'address' => $isPickup ? null : ($data['address'] ?? null),
                'subtotal' => $subtotal,
                'delivery_fee' => $fee,
                'discount' => $discount,
                'total' => $total,
                'customer_note' => $data['customer_note'] ?? null,
                'staff_note' => $data['staff_note'] ?? null,
            ]);

            $order->items()->createMany($lines->all());
            $order->history()->create([
                'to_status' => $status,
                'note' => 'Order taken by staff ('.$source->getLabel().').',
                'user_id' => $by?->id,
            ]);

            if ($status->holdsStock()) {
                $this->takeStock($order);
            }

            return $order;
        });

        $this->notifier->placed($order);
        $this->incomplete->ordered($order); // e.g. staff called back an incomplete checkout

        return $order;
    }

    /**
     * Replace an open order's items, delivery option/charge and discount. Stock moves by the difference only.
     *
     * @param  array<string, mixed>  $data  items[product_id, price, qty], delivery_code, delivery_fee, discount
     */
    public function updateItems(Order $order, array $data, ?User $by = null): void
    {
        abort_unless(self::itemsEditable($order), 422, 'Items can only be changed before the order is shipped.');

        $lines = $this->linesFrom($data['items']);
        $option = $this->delivery->find($data['delivery_code'] ?? $order->delivery_code);
        [$subtotal, $fee, $discount, $total] = $this->totals($lines, $data['delivery_fee'] ?? $order->delivery_fee, $data['discount'] ?? $order->discount);

        DB::transaction(function () use ($order, $by, $lines, $option, $subtotal, $fee, $discount, $total) {
            $before = $order->items()->get();
            $oldTotal = $order->total;

            if ($order->stock_deducted) {
                $old = $before->groupBy('product_id')->map(fn ($items) => $items->sum('qty'));
                $new = $lines->groupBy('product_id')->map(fn ($items) => $items->sum('qty'));

                foreach ($old->keys()->merge($new->keys())->unique()->filter() as $productId) {
                    $more = ($new[$productId] ?? 0) - ($old[$productId] ?? 0);
                    StockService::change((int) $productId, -$more, 'order_edit', $order, 'Order items changed');
                }
            }

            $order->items()->delete();
            $order->items()->createMany($lines->all());

            if ($option && $option['code'] !== $order->delivery_code) {
                $order->fill([
                    'delivery_code' => $option['code'],
                    'delivery_label' => $option['label'],
                    'delivery_eta' => $option['eta'] ?: null,
                    'is_pickup' => $option['type'] === 'pickup',
                ]);
            }

            $order->fill(['subtotal' => $subtotal, 'delivery_fee' => $fee, 'discount' => $discount, 'total' => $total])->save();
            $this->payments->refresh($order); // a new total can turn "Paid" into "Part paid" (or back)

            $order->history()->create([
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => 'Items changed ('.$before->sum('qty').' → '.$lines->sum('qty').' pcs), total BDT '.number_format($oldTotal).' → BDT '.number_format($total).'.',
                'user_id' => $by?->id,
            ]);
        });

        $order->unsetRelation('items')->unsetRelation('history');
    }

    /**
     * Keeps a checkout address on the customer's account (unless it's already there or the list is full).
     *
     * @param  array<string, mixed>  $data
     */
    protected function saveAddress(Customer $customer, array $data): void
    {
        $exists = $customer->addresses()
            ->where('district', $data['district'])->where('area', $data['area'])->where('address', $data['address'])
            ->exists();

        if (! $exists && $customer->addresses()->count() < CustomerAddress::MAX_PER_CUSTOMER) {
            $customer->addresses()->create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'district' => $data['district'],
                'area' => $data['area'],
                'address' => $data['address'],
            ]);
        }
    }

    public static function itemsEditable(Order $order): bool
    {
        return in_array($order->status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true);
    }

    /**
     * Order lines from staff input: current product name/code/warranty, the entered price, same product + price merged.
     *
     * @param  array<int, array{product_id: mixed, price: mixed, qty: mixed}>  $items
     * @return Collection<int, array<string, mixed>>
     */
    protected function linesFrom(array $items): Collection
    {
        $products = Product::withTrashed()->with('colors')->findMany(collect($items)->pluck('product_id'))->keyBy('id');

        return collect($items)
            ->filter(fn ($item) => isset($products[(int) ($item['product_id'] ?? 0)]) && (int) ($item['qty'] ?? 0) > 0)
            ->map(fn ($item) => [...$item, 'color' => rescue(fn () => $products[(int) $item['product_id']]->pickColor($item['color'] ?? null, allowSoldOut: true), null, false)])
            ->groupBy(fn ($item) => (int) $item['product_id'].'@'.(int) $item['price'].'@'.$item['color'])
            ->map(function ($rows) use ($products) {
                $product = $products[(int) $rows->first()['product_id']];
                $price = max(0, (int) $rows->first()['price']);
                $qty = min(999, (int) $rows->sum('qty'));

                return [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'color' => $rows->first()['color'],
                    'sku' => $product->sku,
                    'warranty' => $product->warranty,
                    'price' => $price,
                    'qty' => $qty,
                    'total' => $price * $qty,
                ];
            })
            ->values()
            ->whenEmpty(fn () => throw new \InvalidArgumentException('An order needs at least one product.'));
    }

    /**
     * @return array{int, int, int, int} subtotal, delivery fee, discount (capped), total
     */
    protected function totals(Collection $lines, mixed $fee, mixed $discount): array
    {
        $subtotal = (int) $lines->sum('total');
        $fee = max(0, (int) $fee);
        $discount = min(max(0, (int) $discount), $subtotal + $fee);

        return [$subtotal, $fee, $discount, $subtotal + $fee - $discount];
    }

    public function changeStatus(Order $order, OrderStatus $status, ?string $note = null, ?User $by = null): void
    {
        if ($order->status === $status) {
            return;
        }

        DB::transaction(function () use ($order, $status, $note, $by) {
            $from = $order->status;

            if ($status->holdsStock() && ! $order->stock_deducted) {
                $this->takeStock($order);
            } elseif (! $status->holdsStock() && $order->stock_deducted) {
                $this->returnStock($order, $status);
            }

            $order->status = $status;
            $order->save();
            $order->history()->create(['from_status' => $from, 'to_status' => $status, 'note' => $note, 'user_id' => $by?->id]);

            // Cash on delivery: the courier collected whatever was still due.
            if ($status === OrderStatus::Delivered && $order->payment_method === PaymentMethod::CashOnDelivery && $order->amountDue() > 0) {
                $this->payments->record($order, PaymentMethod::CashOnDelivery, $order->amountDue(), ['note' => 'Collected on delivery'], $by);
            }
        });

        $this->notifier->statusChanged($order, $status);
    }

    protected function takeStock(Order $order): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            StockService::change($item->product_id, -$item->qty, 'order', $order);
        }

        $order->forceFill(['stock_deducted' => true])->save();
    }

    protected function returnStock(Order $order, OrderStatus $status): void
    {
        foreach ($order->items()->whereNotNull('product_id')->get() as $item) {
            StockService::change($item->product_id, $item->qty, 'cancelled', $order, 'Order '.Str::lower($status->getLabel()));
        }

        $order->forceFill(['stock_deducted' => false])->save();
    }
}
