<?php

namespace App\Models;

use App\Enums\WarrantyStatus;
use App\Models\Concerns\LogsActivity;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One line of the shop's warranty list (Sales → Warranty claims). Status changes and customer notes are recorded
 * (and emailed) by App\Services\WarrantyDesk; the Excel sheet comes from App\Support\WarrantySheet.
 */
#[Fillable([
    'number', 'received_on', 'customer_name', 'customer_phone', 'customer_email', 'is_internal', 'product', 'qty', 'serial',
    'problem', 'vendor', 'claimed_on', 'returned_on', 'new_serial', 'status', 'customer_note', 'staff_note',
])]
class WarrantyClaim extends Model
{
    use LogsActivity;

    /** Set by the system. */
    public array $activityIgnore = ['status_changed_at'];

    protected $attributes = ['status' => 'received', 'qty' => 1, 'is_internal' => false];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'qty' => 'integer',
            'is_internal' => 'boolean',
            'received_on' => 'date',
            'claimed_on' => 'date',
            'returned_on' => 'date',
            'status' => WarrantyStatus::class,
            'status_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (WarrantyClaim $claim) {
            $claim->number = $claim->number ?: self::nextNumber();
            $claim->customer_phone = filled($claim->customer_phone) ? (Phone::normalize($claim->customer_phone) ?: null) : null;
            $claim->customer_email = filled($claim->customer_email) ? strtolower(trim($claim->customer_email)) : null;

            if (($claim->isDirty('status') || ! $claim->exists) && ! $claim->isDirty('status_changed_at')) {
                $claim->status_changed_at = now();
            }
        });
    }

    public static function nextNumber(): int
    {
        return (int) self::query()->max('number') + 1;
    }

    public function events(): HasMany
    {
        return $this->hasMany(WarrantyClaimEvent::class)->latest('id');
    }

    /** What a customer may see: their own claims (not the shop's own stock). */
    #[Scope]
    protected function forCustomer(Builder $query, string $phone): void
    {
        $query->where('is_internal', false)->where('customer_phone', Phone::normalize($phone));
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', collect(WarrantyStatus::cases())->filter->isOpen()->map->value->all());
    }

    /** Serial numbers on the website show only their end ("••••0001"). */
    public static function maskSerial(?string $serial): ?string
    {
        $serial = trim((string) $serial);

        if ($serial === '' || in_array(strtoupper($serial), ['N/A', '-', 'NEW', 'SAME', 'UNKNOWN'], true)) {
            return null;
        }

        return mb_strlen($serial) <= 4 ? $serial : '••••'.mb_substr($serial, -4);
    }

    /** Whether the customer gets emails about this claim. */
    public function canEmail(): bool
    {
        return ! $this->is_internal && filter_var($this->customer_email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
