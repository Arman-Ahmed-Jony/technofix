<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Money is in whole taka. Items are a snapshot taken when the order was placed; change the status through
 * App\Services\OrderService so stock and history stay right.
 */
#[Fillable([
    'number', 'status', 'payment_method', 'payment_status', 'source', 'customer_id', 'name', 'phone', 'email',
    'delivery_code', 'delivery_label', 'delivery_eta', 'is_pickup', 'district', 'area', 'address', 'courier', 'tracking_code',
    'subtotal', 'delivery_fee', 'discount', 'coupon_id', 'coupon_code', 'total', 'customer_note', 'staff_note', 'stock_deducted', 'fingerprint',
    'attribution', 'ip', 'user_agent',
])]
class Order extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'source' => OrderSource::class,
            'is_pickup' => 'boolean',
            'stock_deducted' => 'boolean',
            'attribution' => 'array',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    /** Orders that count as sales (everything except cancelled and returned). */
    #[Scope]
    protected function counted(Builder $query): void
    {
        $query->whereNotIn('status', [OrderStatus::Cancelled->value, OrderStatus::Returned->value]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at')->latest('id');
    }

    /** What is still to be paid (what the courier collects on a cash-on-delivery order). */
    public function amountDue(): int
    {
        return max(0, $this->total - $this->paid_amount);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /** "House 12, Road 5, Mirpur 10, Dhaka" — or the pickup note. */
    public function fullAddress(): string
    {
        return $this->is_pickup ? 'Store pickup' : collect([$this->address, $this->area, $this->district])->filter()->join(', ');
    }

    public function whatsappUrl(): string
    {
        return 'https://wa.me/88'.$this->phone.'?text='.rawurlencode("Hello {$this->name}, this is ".config('app.name')." about your order {$this->number}.");
    }

    public function telHref(): string
    {
        return 'tel:'.$this->phone;
    }

    /**
     * Creates the order with the next number of the day: TF-260929-001, TF-260929-002…
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createWithNumber(array $attributes): self
    {
        $prefix = 'TF-'.now()->format('ymd').'-';

        for ($attempt = 0; ; $attempt++) {
            // Longest first so …-1000 sorts after …-999.
            $last = static::where('number', 'like', $prefix.'%')->orderByRaw('length(number) desc')->orderByDesc('number')->value('number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 + $attempt : 1;

            try {
                return DB::transaction(fn () => static::create([...$attributes, 'number' => $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT)]));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 5) {
                    throw $e;
                }
            }
        }
    }
}
