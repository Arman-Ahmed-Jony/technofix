<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A few days after delivery: "How do you like what you bought?" with a review link per product. */
class ReviewRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'How are you finding your order '.$this->order->number.'?');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.feedback.review-request', with: [
            'products' => $this->order->items()->with('product')->get()->pluck('product')->filter()->unique('id')->values(),
        ]);
    }
}
