<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use App\Models\Concerns\LogsActivity;
use App\Services\ProductFeedback;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A customer's star rating + comment on a product. Shown once staff approve it; order_id set = verified purchase.
 * Written and moderated through App\Services\ProductFeedback, which keeps products.rating_avg/rating_count in step.
 */
#[Fillable(['product_id', 'customer_id', 'order_id', 'rating', 'title', 'body', 'status', 'reply', 'replied_at', 'approved_at'])]
class ProductReview extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        // Approving, hiding, re-rating or deleting a review changes the product's average.
        static::saved(function (self $review) {
            if ($review->wasRecentlyCreated || $review->wasChanged(['status', 'rating', 'product_id'])) {
                ProductFeedback::refreshRating($review->product_id);
            }
        });
        static::deleted(fn (self $review) => ProductFeedback::refreshRating($review->product_id));
    }

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'rating' => 'integer',
            'replied_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved);
    }

    public function isVerified(): bool
    {
        return $this->order_id !== null;
    }

    /** "Rahim A." — first name + initial, so full names aren't published. */
    public function authorName(): string
    {
        return self::publicName($this->customer?->name);
    }

    public static function publicName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        if (! $parts) {
            return 'Customer';
        }

        return count($parts) > 1 ? $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $parts[0];
    }
}
