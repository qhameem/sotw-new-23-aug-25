<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Mail\Mailables\Address;

class ProductApproved extends Mailable
{
    use Queueable, SerializesModels;

    protected $template;

    /**
     * Create a new message instance.
     */
    public function __construct(public Product $product)
    {
        $this->template = EmailTemplate::where('name', 'product_approved')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replacePlaceholders($this->template->subject),
            from: new Address($this->template->from_email, $this->template->from_name),
            replyTo: [
                new Address($this->template->reply_to_email, $this->template->from_name),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function build(): Mailable
    {
        $content = $this->replacePlaceholders($this->template->body);

        if ($this->template->is_html) {
            return $this->html($content);
        } else {
            return $this->text($content);
        }
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

    /**
     * Replace placeholders in the email content.
     */
    protected function replacePlaceholders(string $content): string
    {
        $productUrl = route('products.show', $this->product->slug);
        $siteName = config('app.name');

        // Get product publish time from settings.json
        $productPublishTime = '07:00'; // Default value
        if (Storage::disk('local')->exists('settings.json')) {
            $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
            $productPublishTime = $settings['product_publish_time'] ?? '07:00';
        }

        // Format the product's published_at date with the retrieved time
        $productPublishDatetime = $this->product->published_at
                                    ? $this->product->published_at->format('Y-m-d') . ' ' . $productPublishTime . ' UTC'
                                    : 'N/A';

        return str_replace(
            ['{{ user_name }}', '{{ product_name }}', '{{ product_url }}', '{{ site_name }}', '{{ product_publish_datetime }}'],
            [$this->product->user->name, $this->product->name, $productUrl, $siteName, $productPublishDatetime],
            $content
        );
    }
}
