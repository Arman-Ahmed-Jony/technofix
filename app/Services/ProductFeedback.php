<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\QuestionStatus;
use App\Enums\ReviewStatus;
use App\Mail\NewFeedbackForStaff;
use App\Mail\QuestionAnswered;
use App\Mail\ReviewRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\User;
use App\Settings\OrderSettings;
use App\Settings\ReviewSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Product reviews and questions: who may write them, saving them, moderation and the rating summary.
 * Only signed-in customers write; staff approve reviews and answer questions before anything is shown.
 */
class ProductFeedback
{
    public const SHOWN = 20;

    public function __construct(protected ReviewSettings $settings) {}

    public function settings(): ReviewSettings
    {
        return $this->settings;
    }

    // ─── Reading ─────────────────────────────────────────────────────

    /** @return Collection<int, ProductReview> newest approved reviews */
    public function reviews(Product $product): Collection
    {
        return $product->reviews()->approved()->with('customer:id,name')->latest('approved_at')->latest('id')->limit(self::SHOWN)->get();
    }

    /** @return Collection<int, ProductQuestion> newest answered questions */
    public function questions(Product $product): Collection
    {
        return $product->questions()->published()->with('customer:id,name')->latest('answered_at')->latest('id')->limit(self::SHOWN)->get();
    }

    /**
     * Average, count and how many reviews gave each star (5 → 1).
     *
     * @return array{average: ?float, count: int, stars: array<int, int>}
     */
    public function summary(Product $product): array
    {
        $stars = array_fill_keys([5, 4, 3, 2, 1], 0);
        if ($product->rating_count > 0) {
            $counts = $product->reviews()->approved()->selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
            foreach ($counts as $rating => $total) {
                $stars[(int) $rating] = (int) $total;
            }
        }

        return ['average' => $product->rating_avg, 'count' => (int) $product->rating_count, 'stars' => $stars];
    }

    // ─── Who may write ───────────────────────────────────────────────

    /** The newest delivered order of this product by the customer (by account or by the phone they ordered with). */
    public function deliveredOrder(Customer $customer, Product $product): ?Order
    {
        return Order::query()
            ->where('status', OrderStatus::Delivered)
            ->where(fn ($q) => $q->where('customer_id', $customer->id)->orWhere('phone', $customer->phone))
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest('id')
            ->first();
    }

    /**
     * What the review box shows for this visitor.
     *
     * @return array{state: 'disabled'|'guest'|'reviewed'|'not_buyer'|'can_review', review: ?ProductReview}
     */
    public function reviewState(?Customer $customer, Product $product): array
    {
        if (! $this->settings->reviews_enabled) {
            return ['state' => 'disabled', 'review' => null];
        }
        if (! $customer) {
            return ['state' => 'guest', 'review' => null];
        }
        if ($own = $product->reviews()->where('customer_id', $customer->id)->first()) {
            return ['state' => 'reviewed', 'review' => $own];
        }
        if ($this->settings->buyers_only && ! $this->deliveredOrder($customer, $product)) {
            return ['state' => 'not_buyer', 'review' => null];
        }

        return ['state' => 'can_review', 'review' => null];
    }

    // ─── Writing ─────────────────────────────────────────────────────

    /** @param  array{rating: int, title?: ?string, body: string}  $data */
    public function submitReview(Customer $customer, Product $product, array $data): ProductReview
    {
        $review = ProductReview::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $this->deliveredOrder($customer, $product)?->id,
            'rating' => max(1, min(5, (int) $data['rating'])),
            'title' => filled($data['title'] ?? null) ? trim($data['title']) : null,
            'body' => trim($data['body']),
            'status' => ReviewStatus::Pending,
        ]);

        $this->tellStaff($review);

        return $review;
    }

    public function submitQuestion(Customer $customer, Product $product, string $question): ProductQuestion
    {
        $question = ProductQuestion::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'question' => trim($question),
            'status' => QuestionStatus::Pending,
        ]);

        $this->tellStaff($question);

        return $question;
    }

    // ─── Moderation (admin) ──────────────────────────────────────────

    public function approve(ProductReview $review): void
    {
        $review->update(['status' => ReviewStatus::Approved, 'approved_at' => $review->approved_at ?? now()]);
    }

    public function hide(ProductReview $review): void
    {
        $review->update(['status' => ReviewStatus::Rejected]);
    }

    /** A public reply from the shop under the review (empty = remove it). */
    public function reply(ProductReview $review, ?string $reply): void
    {
        $reply = filled($reply) ? trim($reply) : null;
        $review->update(['reply' => $reply, 'replied_at' => $reply ? now() : null]);
    }

    /** Answer and publish a question; the customer gets an email the first time it is answered. */
    public function answer(ProductQuestion $question, string $answer, ?User $by = null): void
    {
        $first = $question->answered_at === null;

        $question->update([
            'answer' => trim($answer),
            'answered_by' => $by?->id ?? $question->answered_by,
            'answered_at' => $question->answered_at ?? now(),
            'status' => QuestionStatus::Published,
        ]);

        // Look the address up: admin lists load the customer with only a few columns.
        $email = Customer::whereKey($question->customer_id)->value('email');
        if ($first && filled($email)) {
            Mail::to($email)->queue(new QuestionAnswered($question));
        }
    }

    public function hideQuestion(ProductQuestion $question): void
    {
        $question->update(['status' => QuestionStatus::Hidden]);
    }

    /** Recount a product's approved reviews into products.rating_avg / rating_count (called by ProductReview events). */
    public static function refreshRating(int $productId): void
    {
        $stats = ProductReview::query()->where('product_id', $productId)->approved()
            ->selectRaw('count(*) as total, avg(rating) as average')->first();

        // A query update: no product events, activity log entries or stock bookkeeping for a recount.
        Product::withTrashed()->whereKey($productId)->update([
            'rating_count' => (int) $stats->total,
            'rating_avg' => $stats->total ? round((float) $stats->average, 1) : null,
        ]);
    }

    /**
     * Queue "please review your order" emails for orders delivered request_after_days ago (each order once; orders
     * delivered more than a month before that are skipped, so switching this on doesn't email old customers).
     */
    public function sendReviewRequests(): int
    {
        if (! $this->settings->reviews_enabled || ! $this->settings->request_reviews || ! app(OrderSettings::class)->email_customers) {
            return 0;
        }

        $cutoff = now()->subDays(max(1, $this->settings->request_after_days));
        $orders = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereNull('review_requested_at')
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereHas('history', fn ($q) => $q->where('to_status', OrderStatus::Delivered->value)
                ->where('created_at', '<=', $cutoff)
                ->where('created_at', '>=', $cutoff->copy()->subDays(30)))
            ->limit(200)
            ->get();

        foreach ($orders as $order) {
            $order->forceFill(['review_requested_at' => now()])->saveQuietly();
            Mail::to($order->email)->queue(new ReviewRequest($order));
        }

        return $orders->count();
    }

    protected function tellStaff(ProductReview|ProductQuestion $feedback): void
    {
        $staff = array_values(array_filter(app(OrderSettings::class)->notify_emails ?? []));

        if ($this->settings->notify_staff && $staff) {
            Mail::to($staff)->queue(new NewFeedbackForStaff($feedback));
        }
    }
}
