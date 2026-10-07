<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A payment or refund on an order. Record them through App\Services\PaymentService, which keeps the order's
 * paid amount and payment status in step.
 */
#[Fillable(['order_id', 'method', 'is_refund', 'status', 'amount', 'trx_id', 'note', 'payload', 'user_id', 'paid_at'])]
class Payment extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => TransactionStatus::class,
            'is_refund' => 'boolean',
            'amount' => 'integer',
            'payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** + for payments, − for refunds. */
    public function signedAmount(): int
    {
        return $this->is_refund ? -$this->amount : $this->amount;
    }
}
