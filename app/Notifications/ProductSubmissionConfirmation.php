<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductSubmissionConfirmation extends Notification
{

    public $product;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your product "' . $this->product->name . '" has been submitted for approval',
            'link' => route('products.my'),
            'product_id' => $this->product->id,
        ];
    }
    
    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'message' => 'Your product "' . $this->product->name . '" has been submitted for approval',
            'link' => route('products.my'),
            'product_id' => $this->product->id,
            'created_at' => now()->toISOString(),
        ];
    }
}