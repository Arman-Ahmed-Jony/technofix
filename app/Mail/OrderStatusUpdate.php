<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the customer their order was confirmed, shipped, delivered or cancelled. */
class OrderStatusUpdate extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Statuses customers hear about: status => subject ("{number}" is filled in). */
    public const SUBJECTS = [
        'confirmed' => 'Your order {number} is confirmed',
        'shipped' => 'Your order {number} is on the way',
        'delivered' => 'Your order {number} was delivered',
        'cancelled' => 'Your order {number} was cancelled',
    ];

    public function __construct(public Order $order, public OrderStatus $status)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: str_replace('{number}', $this->order->number, self::SUBJECTS[$this->status->value]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.status');
    }
}
