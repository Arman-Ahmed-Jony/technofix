<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer waiting for a product to be orderable again (App\Services\StockAlerts sends the email). */
#[Fillable(['product_id', 'lead_id', 'name', 'phone', 'email', 'notified_at'])]
class StockAlert extends Model
{
    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
