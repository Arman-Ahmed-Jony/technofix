{{-- Questions + reviews on the product page (App\Services\ProductFeedback). Only signed-in customers can write;
     guests see "Sign in to …" links that bring them back here. Guests' pages are cached, so nothing personal is shown
     to them; signed-in visitors always get a fresh page. --}}
@php
    $fb = app(\App\Services\ProductFeedback::class);
    $settings = $fb->settings();
    $customer = auth('customer')->user();
    $shop = app(\App\Support\Seo::class)->siteName();
    $signIn = fn (string $section, string $write) => route('account.login', ['back' => '/'.$product->slug.'?write='.$write.'#'.$section]);
    $sent = session('feedback_sent');
@endphp

{{-- ─── Questions ─── --}}
@if ($settings->questions_enabled)
    @php
        $questions = $fb->questions($product);
    @endphp
    <section id="questions" class="card mt-4 scroll-mt-44 p-5 sm:p-7 lg:scroll-mt-48" x-data="{ open: @js(request('write') === 'question' || $errors->question->any()) }">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Questions <span class="font-mono text-base text-faint">({{ $questions->count() }})</span></h2>
                <p class="mt-1 text-sm text-muted">Have a question about this product? Our experts will answer.</p>
            </div>
            @if ($customer)
                <button type="button" class="btn btn-sm btn-outline" x-show="! open" @click="open = true; $nextTick(() => $refs.question.focus())"><x-icon name="message-circle-plus" class="size-4" /> Ask a question</button>
            @else
                <a href="{{ $signIn('questions', 'question') }}" class="btn btn-sm btn-outline"><x-icon name="login-2" class="size-4" /> Sign in to ask</a>
            @endif
        </div>

        @if ($sent === 'question')
            <p class="mt-5 flex items-start gap-2.5 rounded-2xl bg-emerald-500/10 px-4 py-3 text-[14px] text-emerald-800 ring-1 ring-emerald-500/25 dark:text-emerald-300" role="status">
                <x-icon name="circle-check" class="mt-0.5 size-5 shrink-0" />
                <span>Thanks — we’ve got your question. Our team will answer it here soon{{ $customer?->email ? ' and email you' : '' }}.</span>
            </p>
        @endif

        @if ($customer)
            <form x-show="open" x-cloak method="post" action="{{ route('product.question', $product) }}" class="mt-5 rounded-2xl bg-card-2 p-4 ring-1 ring-line sm:p-5" novalidate>
                @csrf
                <label for="pq-question" class="mb-1.5 block text-[13px] font-medium text-ink">Your question</label>
                <textarea id="pq-question" x-ref="question" name="question" rows="3" maxlength="500" placeholder="e.g. Does it come with a charger? Will it work with…"
                    @class(['input h-auto resize-y py-2.5', 'border-rose-500' => $errors->question->has('question')])>{{ old('question') }}</textarea>
                @error('question', 'question')<p class="mt-1.5 text-[12.5px] text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[12.5px] text-muted">Shown as <span class="font-medium text-ink-2">{{ \App\Models\ProductReview::publicName($customer->name) }}</span> once answered.</p>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-sm btn-soft" @click="open = false">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary"><x-icon name="send" class="size-4" /> Send question</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($questions->isNotEmpty())
            <ul class="mt-5 space-y-3">
                @foreach ($questions as $q)
                    <li class="rounded-2xl border border-line p-4">
                        <div class="flex gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-brand-500/10 font-display text-[12px] font-bold text-highlight">Q</span>
                            <div class="min-w-0">
                                <p class="text-[14.5px] font-medium whitespace-pre-line text-ink">{{ $q->question }}</p>
                                <p class="mt-0.5 text-[12px] text-muted">{{ $q->authorName() }} · {{ $q->created_at->format('j M Y') }}</p>
                            </div>
                        </div>
                        <div class="mt-3 flex gap-3 rounded-xl bg-card-2 p-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-lg bg-emerald-500/10 font-display text-[12px] font-bold text-emerald-700 dark:text-emerald-400">A</span>
                            <div class="min-w-0">
                                <p class="text-[14px] leading-relaxed whitespace-pre-line text-ink-2">{{ $q->answer }}</p>
                                <p class="mt-0.5 text-[12px] text-muted">{{ $shop }} · {{ $q->answered_at?->format('j M Y') }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @elseif ($sent !== 'question')
            <div class="mt-5 flex flex-col items-center gap-2.5 rounded-2xl bg-card-2 px-4 py-10 text-center text-sm text-muted ring-1 ring-line">
                <x-icon name="message-circle" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.4]" /> No questions yet — be the first to ask.
            </div>
        @endif
    </section>
@endif

{{-- ─── Reviews ─── --}}
@if ($settings->reviews_enabled)
    @php
        $summary = $fb->summary($product);
        $reviews = $summary['count'] ? $fb->reviews($product) : collect();
        $state = $fb->reviewState($customer, $product);
        $own = $state['review'];
    @endphp
    <section id="reviews" class="card mt-4 scroll-mt-44 p-5 sm:p-7 lg:scroll-mt-48" x-data="{ open: @js($state['state'] === 'can_review' && (request('write') === 'review' || $errors->review->any())) }">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-semibold tracking-tight text-ink">Reviews <span class="font-mono text-base text-faint">({{ $summary['count'] }})</span></h2>
                <p class="mt-1 text-sm text-muted">Bought this product? Share your experience with others.</p>
            </div>
            @switch($state['state'])
                @case('guest')
                    <a href="{{ $signIn('reviews', 'review') }}" class="btn btn-sm btn-outline"><x-icon name="login-2" class="size-4" /> Sign in to write a review</a>
                    @break
                @case('can_review')
                    <button type="button" class="btn btn-sm btn-outline" x-show="! open" @click="open = true"><x-icon name="pencil" class="size-4" /> Write a review</button>
                    @break
                @case('reviewed')
                    <span class="chip bg-card-2 py-1.5 text-[12.5px] text-ink-2 ring-1 ring-line">
                        <x-icon :name="$own->status === \App\Enums\ReviewStatus::Pending ? 'clock' : 'circle-check'" class="size-4 text-highlight" />
                        {{ $own->status === \App\Enums\ReviewStatus::Pending ? 'Your review is waiting for approval' : 'You’ve reviewed this product' }}
                    </span>
                    @break
                @case('not_buyer')
                    <span class="flex max-w-xs items-start gap-1.5 text-[12.5px] text-muted"><x-icon name="info-circle" class="mt-px size-4 shrink-0" /> Only customers who bought this product from us can review it.</span>
                    @break
            @endswitch
        </div>

        @if ($sent === 'review')
            <p class="mt-5 flex items-start gap-2.5 rounded-2xl bg-emerald-500/10 px-4 py-3 text-[14px] text-emerald-800 ring-1 ring-emerald-500/25 dark:text-emerald-300" role="status">
                <x-icon name="circle-check" class="mt-0.5 size-5 shrink-0" />
                <span>Thank you for your review! It will appear here once our team has checked it.</span>
            </p>
        @endif

        @if ($summary['count'])
            <div class="mt-5 grid items-center gap-5 rounded-2xl bg-card-2 p-5 ring-1 ring-line sm:grid-cols-[auto_minmax(0,1fr)] sm:gap-8">
                <div class="text-center sm:px-4">
                    <p class="font-display text-[44px] leading-none font-semibold tracking-tight text-ink">{{ number_format($summary['average'], 1) }}</p>
                    <x-stars :rating="$summary['average']" size="size-5" class="mt-2" />
                    <p class="mt-1.5 text-[12.5px] text-muted">{{ $summary['count'] }} {{ Str::plural('review', $summary['count']) }}</p>
                </div>
                <ul class="space-y-1.5">
                    @foreach ($summary['stars'] as $stars => $total)
                        <li class="flex items-center gap-3 text-[12.5px]">
                            <span class="flex w-8 shrink-0 items-center gap-0.5 font-medium text-ink-2">{{ $stars }} <x-icon name="star-filled" class="size-3 text-amber-400" /></span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-line"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $summary['count'] ? round($total / $summary['count'] * 100) : 0 }}%"></span></span>
                            <span class="w-6 shrink-0 text-right font-mono text-muted">{{ $total }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($state['state'] === 'can_review')
            <form x-show="open" x-cloak method="post" action="{{ route('product.review', $product) }}" class="mt-5 space-y-4 rounded-2xl bg-card-2 p-4 ring-1 ring-line sm:p-5" novalidate>
                @csrf
                <fieldset>
                    <legend class="mb-1.5 text-[13px] font-medium text-ink">Your rating</legend>
                    <div class="flex items-center gap-1" x-data="{ rating: {{ (int) old('rating', 0) }}, hover: 0 }">
                        @for ($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer rounded-lg p-0.5 has-focus-visible:outline-2 has-focus-visible:outline-brand-500" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0">
                                <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-model.number="rating" @checked((int) old('rating') === $i)>
                                <x-icon name="star-filled" class="size-8 transition" ::class="(hover || rating) >= {{ $i }} ? 'text-amber-400 scale-105' : 'text-line-strong'" />
                                <span class="sr-only">{{ $i }} {{ Str::plural('star', $i) }}</span>
                            </label>
                        @endfor
                        <span class="ml-2 text-[13px] font-medium text-muted" x-text="['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'][hover || rating]"></span>
                    </div>
                    @error('rating', 'review')<p class="mt-1.5 text-[12.5px] text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </fieldset>
                <div>
                    <label for="pr-title" class="mb-1.5 block text-[13px] font-medium text-ink">Title <span class="font-normal text-muted">(optional)</span></label>
                    <input id="pr-title" name="title" value="{{ old('title') }}" maxlength="120" placeholder="e.g. Fast and quiet, great value" class="input">
                </div>
                <div>
                    <label for="pr-body" class="mb-1.5 block text-[13px] font-medium text-ink">Your review</label>
                    <textarea id="pr-body" name="body" rows="4" maxlength="2000" placeholder="What did you like? Anything others should know?"
                        @class(['input h-auto resize-y py-2.5', 'border-rose-500' => $errors->review->has('body')])>{{ old('body') }}</textarea>
                    @error('body', 'review')<p class="mt-1.5 text-[12.5px] text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-[12.5px] text-muted">Shown as <span class="font-medium text-ink-2">{{ \App\Models\ProductReview::publicName($customer->name) }}</span>, after our team checks it.</p>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-sm btn-soft" @click="open = false">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary"><x-icon name="send" class="size-4" /> Submit review</button>
                    </div>
                </div>
            </form>
        @endif

        @if ($reviews->isNotEmpty())
            <ul class="mt-2 divide-y divide-line">
                @foreach ($reviews as $review)
                    <li class="py-5 last:pb-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <x-stars :rating="$review->rating" />
                            @if ($review->title)<p class="text-[15px] font-semibold text-ink">{{ $review->title }}</p>@endif
                        </div>
                        <p class="mt-2 text-[14.5px] leading-relaxed whitespace-pre-line text-ink-2">{{ $review->body }}</p>
                        <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12.5px] text-muted">
                            <span class="font-medium text-ink-2">{{ $review->authorName() }}</span>
                            @if ($review->isVerified())
                                <span class="inline-flex items-center gap-1 font-medium text-emerald-700 dark:text-emerald-400"><x-icon name="rosette-discount-check" class="size-3.5" /> Verified purchase</span>
                            @endif
                            <span>· {{ ($review->approved_at ?? $review->created_at)->format('j M Y') }}</span>
                        </p>
                        @if ($review->reply)
                            <div class="mt-3 rounded-xl bg-card-2 p-3 ring-1 ring-line">
                                <p class="text-[12.5px] font-semibold text-ink">Reply from {{ $shop }}</p>
                                <p class="mt-1 text-[13.5px] leading-relaxed whitespace-pre-line text-ink-2">{{ $review->reply }}</p>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @elseif ($sent !== 'review')
            <div class="mt-5 flex flex-col items-center gap-2.5 rounded-2xl bg-card-2 px-4 py-10 text-center text-sm text-muted ring-1 ring-line">
                <x-icon name="star" class="size-7 [stroke:url(#tf-grad)] [stroke-width:1.4]" /> No reviews yet.
            </div>
        @endif
    </section>
@endif
