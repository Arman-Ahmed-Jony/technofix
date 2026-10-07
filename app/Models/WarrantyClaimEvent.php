<?php

namespace App\Models;

use App\Enums\WarrantyStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A status / note the customer was given for a warranty claim (website timeline; `emailed_at` = email sent). */
#[Fillable(['warranty_claim_id', 'status', 'note', 'emailed_at', 'user_id'])]
class WarrantyClaimEvent extends Model
{
    protected function casts(): array
    {
        return ['status' => WarrantyStatus::class, 'emailed_at' => 'datetime'];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
