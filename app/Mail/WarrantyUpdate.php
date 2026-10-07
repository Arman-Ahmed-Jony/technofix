<?php

namespace App\Mail;

use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/** Tells the customer about their warranty claim: received, a new status, or a new note from the shop. */
class WarrantyUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public WarrantyClaim $claim, public WarrantyClaimEvent $event, public bool $created = false)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $product = Str::limit($this->claim->product, 50);

        return new Envelope(subject: $this->created
            ? "We received your {$product} for warranty (#{$this->claim->number})"
            : "Warranty update: {$product} — {$this->event->status->customerLabel()}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.warranty.update');
    }
}
