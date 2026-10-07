<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to customers who asked to be told when a product can be ordered again. */
class BackInStock extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product, public ?string $name = null)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "It’s back: {$this->product->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.stock.back-in-stock');
    }
}
