<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'label', 'name', 'phone', 'district', 'area', 'address', 'is_default'])]
class CustomerAddress extends Model
{
    public const MAX_PER_CUSTOMER = 10;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        // One default per customer; the first address becomes the default.
        static::saving(function (CustomerAddress $address) {
            if (! $address->customer->addresses()->whereKeyNot($address->id ?? 0)->exists()) {
                $address->is_default = true;
            }
        });

        static::saved(function (CustomerAddress $address) {
            if ($address->is_default) {
                $address->customer->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }
        });

        static::deleted(function (CustomerAddress $address) {
            if ($address->is_default) {
                $address->customer->addresses()->first()?->update(['is_default' => true]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** "Mirpur 10, Dhaka" */
    public function summary(): string
    {
        return collect([$this->address, $this->area, $this->district])->filter()->join(', ');
    }

    /** @return array<string, mixed> for the checkout's "use this address" buttons */
    public function forCheckout(): array
    {
        return $this->only(['id', 'label', 'name', 'phone', 'district', 'area', 'address']);
    }
}
