<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One change to a product's quantity — an order, a cancellation, a stock-in, or a staff adjustment (with a reason).
 */
#[Fillable(['product_id', 'change', 'balance', 'type', 'reason', 'order_id', 'stock_receipt_id', 'user_id', 'note'])]
class StockMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'order' => 'Ordered',
        'cancelled' => 'Order cancelled / returned',
        'order_edit' => 'Order items changed',
        'adjustment' => 'Changed by staff',
        'purchase' => 'Stock-in',
        'purchase_cancelled' => 'Stock-in cancelled',
    ];

    /** Why staff adjusted stock (Adjust stock action). */
    public const REASONS = [
        'count' => 'Stock count',
        'damaged' => 'Damaged',
        'lost' => 'Lost / stolen',
        'supplier_return' => 'Returned to supplier',
        'own_use' => 'Used in the shop / repairs',
        'found' => 'Found',
        'other' => 'Other',
    ];

    protected function casts(): array
    {
        return ['change' => 'integer', 'balance' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
