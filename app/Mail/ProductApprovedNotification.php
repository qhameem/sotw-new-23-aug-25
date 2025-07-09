<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ProductApprovedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public Product $product;
    public string $approvalDate;
    public string $productViewLink;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Product $product)
    {
        $this->user = $user;
        $this->product = $product;
        $this->approvalDate = now()->format('F j, Y');
        $this->productViewLink = route('products.show', $this->product);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: "Your Product Submission '{$this->product->name}' Has Been Approved!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.product_approved',
            with: [
                'userFirstName' => $this->user->profile->first_name ?? $this->user->name,
                'userName' => $this->user->name,
                'productName' => $this->product->name,
                'submissionDate' => $this->product->created_at->format('F j, Y'),
                'approvalDate' => $this->approvalDate,
                'productViewLink' => $this->productViewLink,
                'dashboardLink' => route('dashboard'), // Assuming a 'dashboard' route exists
            ],
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
}
