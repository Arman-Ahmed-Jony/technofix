<?php

namespace App\Models;

use App\Enums\CouponType;
use App\Enums\OrderStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A whole-order discount code. Check and price it with App\Services\CouponService, never in the browser.
 */
#[Fillable(['code', 'description', 'type', 'value', 'max_discount', 'min_subtotal', 'starts_at', 'ends_at', 'usage_limit', 'per_customer_limit', 'is_active'])]
class Coupon extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'max_discount' => 'integer',
            'min_subtotal' => 'integer',
            'usage_limit' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->code = Str::upper(trim($coupon->code));

            if ($coupon->type === CouponType::FreeDelivery) {
                $coupon->value = 0;
            }

            if ($coupon->type !== CouponType::Percent) {
                $coupon->max_discount = null;
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** Orders that used it (cancelled / returned orders give the use back). */
    public function uses(): HasMany
    {
        return $this->orders()->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value]);
    }

    /** "10% off (max ৳1,000)", "৳500 off", "Free delivery" */
    public function summary(string $currency = '৳'): string
    {
        return match ($this->type) {
            CouponType::Percent => $this->value.'% off'.($this->max_discount ? ' (max '.$currency.number_format($this->max_discount).')' : ''),
            CouponType::Fixed => $currency.number_format($this->value).' off',
            CouponType::FreeDelivery => 'Free delivery',
        };
    }

    /** Active, Scheduled, Expired, Used up or Off — for the admin list. */
    public function state(?int $uses = null): string
    {
        return match (true) {
            ! $this->is_active => 'Off',
            $this->starts_at?->isFuture() ?? false => 'Scheduled',
            $this->ends_at?->isPast() ?? false => 'Expired',
            $this->usage_limit !== null && ($uses ?? $this->uses()->count()) >= $this->usage_limit => 'Used up',
            default => 'Active',
        };
    }

    /**
     * The rule the checkout needs to show the discount live (the server re-checks when the order is placed).
     *
     * @return array<string, mixed>
     */
    public function forCheckout(): array
    {
        return [
            'code' => $this->code,
            'type' => $this->type->value,
            'value' => $this->value,
            'max_discount' => $this->max_discount,
            'min_subtotal' => $this->min_subtotal,
            'summary' => $this->summary(),
        ];
    }
}
