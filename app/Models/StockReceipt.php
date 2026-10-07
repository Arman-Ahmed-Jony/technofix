<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Goods arriving from a supplier (Admin → Inventory → Stock-in). Saved through App\Services\InventoryService, which adds
 * the stock and updates cost prices; a receipt is never edited — a mistake is cancelled (stock taken back out).
 */
#[Fillable(['number', 'supplier_id', 'reference', 'received_on', 'total_cost', 'note', 'user_id', 'cancelled_at'])]
class StockReceipt extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    /** "SI-261003-001": the day's next number. */
    public static function nextNumber(): string
    {
        $prefix = 'SI-'.now()->format('ymd').'-';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');

        return $prefix.str_pad((string) ((int) substr((string) $last, -3) + 1), 3, '0', STR_PAD_LEFT);
    }
}
