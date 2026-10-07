<?php

namespace App\Models;

use App\Enums\QuestionStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A customer's question about a product. Shown on the product page once staff answer it (App\Services\ProductFeedback). */
#[Fillable(['product_id', 'customer_id', 'question', 'answer', 'answered_by', 'answered_at', 'status'])]
class ProductQuestion extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['status' => QuestionStatus::class, 'answered_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', QuestionStatus::Published);
    }

    public function authorName(): string
    {
        return ProductReview::publicName($this->customer?->name);
    }
}
