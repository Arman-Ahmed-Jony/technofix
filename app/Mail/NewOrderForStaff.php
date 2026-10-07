<?php

namespace App\Mail;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** "New order" alert for the shop's staff (website orders). */
class NewOrderForStaff extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New order {$this->order->number} · BDT ".number_format($this->order->total)." · {$this->order->name}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.staff', with: [
            'adminUrl' => OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'),
        ]);
    }
}
