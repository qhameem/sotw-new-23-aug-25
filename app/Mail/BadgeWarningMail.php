<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BadgeWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Product $product
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action Required: Badge missing on your site for {$this->product->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.badge-warning',
        );
    }
}
