<?php

namespace App\Mail;

use App\Models\PaidSubmissionCheckout;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaidSubmissionPaidAdminNotification extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Product $product,
        public PaidSubmissionCheckout $checkout
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Paid product submission received'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.paid-submission-paid-admin',
        );
    }
}
