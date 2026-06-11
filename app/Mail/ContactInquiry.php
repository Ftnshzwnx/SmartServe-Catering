<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiry extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
