<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order: a copy of the product's name, code and price when it was ordered.
 */
#[Fillable(['order_id', 'product_id', 'name', 'color', 'sku', 'warranty', 'price', 'qty', 'total'])]
class OrderItem extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['price' => 'integer', 'qty' => 'integer', 'total' => 'integer'];
    }

    /** "Logitech G102 — White": the name with the chosen color, for invoices, emails and order pages. */
    public function label(): string
    {
        return $this->color ? $this->name.' — '.$this->color : $this->name;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
