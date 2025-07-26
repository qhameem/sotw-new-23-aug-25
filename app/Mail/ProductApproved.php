<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProductApproved extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Product $product)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Product has been Approved',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.product.approved',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    public function build()
    {
        EmailLog::create([
            'product_id' => $this->product->id,
            'user_id' => $this->product->user_id,
            'status' => 'building',
            'message' => 'Queue worker is building the email.'
        ]);

        try {
            return $this->markdown('emails.product.approved');
        } catch (\Exception $e) {
            EmailLog::create([
                'product_id' => $this->product->id,
                'user_id' => $this->product->user_id,
                'status' => 'failed',
                'message' => 'Failed during email build: ' . $e->getMessage()
            ]);
            throw $e;
        }
    }
}
