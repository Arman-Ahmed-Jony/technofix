@php($isReview = $feedback instanceof \App\Models\ProductReview)
<x-mail::message>
# {{ $isReview ? 'New review to approve' : 'New question to answer' }}

**Product:** {{ $feedback->product->name }}<br>
**Customer:** {{ $feedback->customer?->name }} · {{ $feedback->customer?->phone }}

@if ($isReview)
**Rating:** {{ str_repeat('★', $feedback->rating) }}{{ str_repeat('☆', 5 - $feedback->rating) }} ({{ $feedback->rating }}/5){{ $feedback->isVerified() ? ' · Verified purchase' : '' }}

@if ($feedback->title)
**{{ $feedback->title }}**
@endif

{{ $feedback->body }}
@else
> {{ $feedback->question }}
@endif

<x-mail::button :url="$isReview ? \App\Filament\Resources\ProductReviews\ProductReviewResource::getUrl('edit', ['record' => $feedback]) : \App\Filament\Resources\ProductQuestions\ProductQuestionResource::getUrl('edit', ['record' => $feedback])">
{{ $isReview ? 'Review and approve' : 'Write the answer' }}
</x-mail::button>

{{ $isReview ? 'It appears on the product page once you approve it.' : 'It appears on the product page with your answer, and the customer gets an email.' }}
</x-mail::message>
