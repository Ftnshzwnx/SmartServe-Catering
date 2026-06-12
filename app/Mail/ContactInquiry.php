<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactInquiry extends Mailable
{
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Catering Inquiry from ' . $this->data['name'],
            replyTo: [new \Illuminate\Mail\Mailables\Address($this->data['email'], $this->data['name'])],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_inquiry',
            with: ['data' => $this->data],
        );
    }
}
