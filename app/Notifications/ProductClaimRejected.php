<?php

namespace App\Notifications;

use App\Models\ProductClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProductClaimRejected extends Notification
{
    use Queueable;

    public function __construct(private readonly ProductClaim $claim, private readonly ?string $message = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message ?: 'Your claim for ' . $this->claim->product->name . ' was not approved.',
            'link' => route('products.claim.create', $this->claim->product),
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
