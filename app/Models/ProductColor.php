<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One color version of a product (name + swatch, can be marked sold out). See Product::pickColor(). */
#[Fillable(['product_id', 'name', 'hex', 'is_available', 'sort'])]
class ProductColor extends Model
{
    protected function casts(): array
    {
        return ['is_available' => 'boolean', 'sort' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
