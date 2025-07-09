<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductApprovedInApp extends Notification implements ShouldQueue // Added ShouldQueue for background processing
{
    use Queueable;

    public Product $product;

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
        return ['database']; // Specify database channel
    }

    /**
     * Get the array representation of the notification.
     * This method is used when the 'database' channel is specified.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'message' => "Your product '{$this->product->name}' has been approved.",
            'link' => route('products.show', $this->product->slug), // Link to the product page
            'notification_type' => 'product_approval', // Custom type for easy filtering/identification
        ];
    }
}
