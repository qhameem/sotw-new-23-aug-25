<?php

namespace App\Jobs;

use App\Models\NewsletterSubscriber;
use App\Services\EmailOctopusNewsletterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncNewsletterSubscriberToEmailOctopus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $newsletterSubscriberId) {}

    public function handle(EmailOctopusNewsletterService $emailOctopus): void
    {
        $subscriber = NewsletterSubscriber::find($this->newsletterSubscriberId);

        if (! $subscriber) {
            return;
        }

        $contactId = $emailOctopus->subscribe($subscriber);

        $subscriber->update([
            'status' => 'synced',
            'provider_contact_id' => $contactId,
            'synced_at' => now(),
            'last_error' => null,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        NewsletterSubscriber::whereKey($this->newsletterSubscriberId)->update([
            'status' => 'failed',
            'last_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
