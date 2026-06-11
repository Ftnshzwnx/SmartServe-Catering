<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CateringNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailSubject;
    public string $greeting;
    public array $lines;
    public ?string $actionText;
    public ?string $actionUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $greeting, array $lines, ?string $actionText = null, ?string $actionUrl = null)
    {
        $this->mailSubject = $subject;
        $this->greeting = $greeting;
        $this->lines = $lines;
        $this->actionText = $actionText;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.catering_notification',
        );
    }
}
