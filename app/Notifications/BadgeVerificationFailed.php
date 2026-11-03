<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BadgeVerificationFailed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $placementUrl,
        public readonly string $failureMessage,
        public readonly ?string $reason = null,
        public readonly ?int $httpStatus = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $channel === 'database';
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Badge verification failed for {$this->productName} (#{$this->productId}): {$this->failureMessage}",
            'link' => route('admin.products.show', $this->productId),
            'product_id' => $this->productId,
            'placement_url' => $this->placementUrl,
            'reason' => $this->reason,
            'http_status' => $this->httpStatus,
        ];
    }

}
