<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SiteFeedback extends Mailable
{
    public function __construct(
        public string $feedbackType,
        public string $feedbackMessage,
        public ?string $contactEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: $this->contactEmail ? [new Address($this->contactEmail)] : [],
            subject: 'Site feedback: '.$this->feedbackType,
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.site-feedback');
    }
}
