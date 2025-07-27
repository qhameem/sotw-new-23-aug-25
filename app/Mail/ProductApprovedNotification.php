<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class ProductApprovedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public Product $product;
    public ?EmailTemplate $template;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Product $product)
    {
        $this->user = $user;
        $this->product = $product;
        $this->template = EmailTemplate::where('name', 'product_approved')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->replacePlaceholders($this->template->subject);

        return new Envelope(
            from: new Address($this->template->from_email, $this->template->from_name),
            replyTo: [new Address($this->template->reply_to_email, $this->template->reply_to_name)],
            subject: $subject,
        );
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $body = $this->replacePlaceholders($this->template->body);

        if ($this->template->is_html) {
            return $this->html($body);
        }

        return $this->text($body);
    }

    /**
     * Replace placeholders in the given content.
     *
     * @param string $content
     * @return string
     */
    private function replacePlaceholders(string $content): string
    {
        $placeholders = [
            '{{ user_name }}' => $this->user->name,
            '{{ user_first_name }}' => $this->user->profile->first_name ?? $this->user->name,
            '{{ product_name }}' => $this->product->name,
            '{{ product_url }}' => route('products.show', $this->product),
            '{{ product_publish_datetime }}' => $this->product->published_at ? $this->product->published_at->format('F j, Y, g:i a') : 'N/A',
            '{{ approval_date }}' => now()->format('F j, Y'),
            '{{ dashboard_url }}' => route('dashboard'),
            '{{ site_name }}' => config('app.name'),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
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
