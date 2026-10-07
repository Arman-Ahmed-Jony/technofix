<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductFeedback;
use App\Support\Blocklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Reviews and questions sent from a product page (signed-in customers only — the routes use auth:customer). */
class ProductFeedbackController extends Controller
{
    public function __construct(protected ProductFeedback $feedback) {}

    public function review(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === ProductStatus::Published, 404);
        $customer = $request->user('customer');
        $this->refuseBlocked($customer->phone, 'body');

        $state = $this->feedback->reviewState($customer, $product)['state'];
        if ($state !== 'can_review') {
            throw ValidationException::withMessages(['body' => match ($state) {
                'reviewed' => 'You have already reviewed this product.',
                'not_buyer' => 'Only customers who bought this product from us can review it.',
                default => 'Reviews are switched off at the moment.',
            }])->errorBag('review');
        }

        $data = $request->validateWithBag('review', [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'rating.required' => 'Choose how many stars you give it.',
            'body.required' => 'Tell other customers what you think.',
            'body.min' => 'Please write a little more (at least 10 characters).',
        ]);

        $this->feedback->submitReview($customer, $product, $data);

        return redirect()->to($product->url.'#reviews')->with('feedback_sent', 'review');
    }

    public function question(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === ProductStatus::Published && $this->feedback->settings()->questions_enabled, 404);
        $customer = $request->user('customer');
        $this->refuseBlocked($customer->phone, 'question');

        $data = $request->validateWithBag('question', [
            'question' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'question.required' => 'Type your question.',
            'question.min' => 'Please write a little more (at least 10 characters).',
        ]);

        $this->feedback->submitQuestion($customer, $product, $data['question']);

        return redirect()->to($product->url.'#questions')->with('feedback_sent', 'question');
    }

    protected function refuseBlocked(?string $phone, string $field): void
    {
        if (Blocklist::phoneBlocked($phone)) {
            throw ValidationException::withMessages([$field => Blocklist::message()])->errorBag($field === 'question' ? 'question' : 'review');
        }
    }
}
