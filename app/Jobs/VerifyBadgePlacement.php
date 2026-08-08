<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\BadgeVerificationManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyBadgePlacement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public Product $product
    ) {}

    public function handle(BadgeVerificationManager $manager): void
    {
        $product = $this->product;

        if ($product->submission_type !== 'badge') {
            return;
        }

        try {
            $manager->verify($product, 'automatic');
        } catch (\Throwable $e) {
            Log::error('Badge verification job failed.', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
