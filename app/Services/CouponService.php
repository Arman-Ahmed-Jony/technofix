<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Exceptions\InvalidCoupon;
use App\Models\Coupon;
use Illuminate\Support\Str;

/**
 * Checks coupon codes and works out their discount. The checkout only previews the result; the order is priced
 * here again when it is placed.
 */
class CouponService
{
    /**
     * The usable coupon for this code and order, or InvalidCoupon with the reason to show the customer.
     *
     * @param  int  $subtotal  products only, priced from the catalog
     * @param  string|null  $phone  normalised mobile number, for the per-customer limit (checked when it's known)
     * @param  bool  $lock  lock the coupon row while an order is being saved, so the last use can't be taken twice
     */
    public function check(string $code, int $subtotal, ?string $phone = null, bool $lock = false): Coupon
    {
        $code = Str::upper(trim($code));
        $coupon = $code === '' ? null : Coupon::where('code', $code)->when($lock, fn ($q) => $q->lockForUpdate())->first();

        if (! $coupon || ! $coupon->is_active) {
            throw new InvalidCoupon('This coupon code isn’t valid.');
        }

        if ($coupon->starts_at?->isFuture()) {
            throw new InvalidCoupon('This coupon starts on '.$coupon->starts_at->format('j M Y').'.');
        }

        if ($coupon->ends_at?->isPast()) {
            throw new InvalidCoupon('This coupon has expired.');
        }

        if ($coupon->usage_limit !== null && $coupon->uses()->count() >= $coupon->usage_limit) {
            throw new InvalidCoupon('This coupon has already been fully used.');
        }

        if ($coupon->min_subtotal && $subtotal < $coupon->min_subtotal) {
            throw new InvalidCoupon('This coupon needs an order of ৳'.number_format($coupon->min_subtotal).' or more (before delivery).');
        }

        if ($phone && $coupon->per_customer_limit !== null && $coupon->uses()->where('phone', $phone)->count() >= $coupon->per_customer_limit) {
            throw new InvalidCoupon('You’ve already used this coupon.');
        }

        return $coupon;
    }

    /** Taka off the order: never more than it costs. */
    public static function discount(Coupon $coupon, int $subtotal, int $deliveryFee): int
    {
        $discount = match ($coupon->type) {
            CouponType::Percent => min(
                intdiv($subtotal * min(100, $coupon->value), 100),
                $coupon->max_discount ?: PHP_INT_MAX,
            ),
            CouponType::Fixed => min($coupon->value, $subtotal), // off the products, not the delivery
            CouponType::FreeDelivery => $deliveryFee,
        };

        return max(0, min($discount, $subtotal + $deliveryFee));
    }
}
