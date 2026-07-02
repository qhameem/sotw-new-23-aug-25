<?php

namespace App\Jobs;

use App\Services\UrlNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitUrlNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public array $updatedUrls = [],
        public array $deletedUrls = [],
    ) {
    }

    public function handle(UrlNotificationService $notifications): void
    {
        try {
            $notifications->submit($this->updatedUrls, $this->deletedUrls);
        } catch (\Throwable $exception) {
            Log::warning('URL notification submission failed.', [
                'updated_urls' => $this->updatedUrls,
                'deleted_urls' => $this->deletedUrls,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('URL notification submission failed.', [
            'updated_urls' => $this->updatedUrls,
            'deleted_urls' => $this->deletedUrls,
            'error' => $exception->getMessage(),
        ]);
    }
}
