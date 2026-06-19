<?php

namespace App\Mail;

use App\Models\PaidSubmissionCheckout;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Product $product;
    public User $user;
    public PaidSubmissionCheckout $checkout;

    /**
     * Create a new message instance.
     */
    public function __construct(Product $product, User $user, PaidSubmissionCheckout $checkout)
    {
        $this->product = $product;
        $this->user = $user;
        $this->checkout = $checkout;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Paid Submission Receipt',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
