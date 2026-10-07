<?php

namespace App\Models;

use App\Support\PcBuilder\Build;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A PC build a customer saved to their account (opened with today's prices). */
#[Fillable(['customer_id', 'name', 'code'])]
class SavedBuild extends Model
{
    public const MAX_PER_CUSTOMER = 20;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function build(): Build
    {
        return Build::fromCode($this->code);
    }

    public function url(): string
    {
        return Build::link($this->code);
    }
}
