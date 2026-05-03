<?php

namespace App\Notifications;

use App\Models\ProductClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductClaimApproved extends Notification
{
    use Queueable;

    public function __construct(private readonly ProductClaim $claim)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Your claim for ' . $this->claim->product->name . ' was approved.',
            'link' => route('products.my'),
            'product_claim_id' => $this->claim->id,
            'product_id' => $this->claim->product_id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable) + [
            'created_at' => now()->toISOString(),
        ];
    }
}
