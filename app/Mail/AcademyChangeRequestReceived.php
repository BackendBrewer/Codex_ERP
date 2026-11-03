<?php

namespace App\Mail;

use App\Models\AcademyChangeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AcademyChangeRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public AcademyChangeRequest $changeRequest) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Academy change request · '.$this->changeRequest->academy->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.academy-change-request',
            with: ['changeRequest' => $this->changeRequest],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
