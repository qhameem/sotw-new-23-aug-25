<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
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
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Badge verification failed: {$this->productName} (#{$this->productId})")
            ->line("Regular badge verification failed for {$this->productName} (#{$this->productId}).")
            ->line('Checked URL: '.$this->placementUrl)
            ->line('Reason: '.$this->failureMessage)
            ->line('Published products remain live pending admin review.')
            ->action('Review product', route('admin.products.show', $this->productId));
    }
}
