<?php

namespace App\Jobs;

use App\Services\IndexNowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitIndexNowUrls implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public array $urls
    ) {
    }

    public function handle(IndexNowService $indexNow): void
    {
        $indexNow->submitUrls($this->urls);
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('IndexNow URL submission failed.', [
            'urls' => $this->urls,
            'error' => $exception->getMessage(),
        ]);
    }
}
