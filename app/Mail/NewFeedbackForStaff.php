<?php

namespace App\Mail;

use App\Models\ProductQuestion;
use App\Models\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A customer wrote a review (to approve) or asked a question (to answer). */
class NewFeedbackForStaff extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProductReview|ProductQuestion $feedback)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $product = $this->feedback->product->name;

        return new Envelope(subject: $this->feedback instanceof ProductReview
            ? "New {$this->feedback->rating}★ review to approve: {$product}"
            : "New question to answer: {$product}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.feedback.staff');
    }
}
