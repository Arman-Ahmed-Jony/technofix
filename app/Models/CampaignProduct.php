<?php

namespace App\Models;

use App\Support\Deals;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A product in a campaign with its deal price. */
#[Fillable(['campaign_id', 'product_id', 'deal_price', 'sort'])]
class CampaignProduct extends Model
{
    protected function casts(): array
    {
        return ['deal_price' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Deals::flush());
        static::deleted(fn () => Deals::flush());
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
