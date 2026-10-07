<?php

namespace App\Mail;

use App\Models\ProductQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the customer that their product question has an answer. */
class QuestionAnswered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProductQuestion $question)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your question about {$this->question->product->name} has an answer");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.feedback.question-answered');
    }
}
