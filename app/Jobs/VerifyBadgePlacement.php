<?php

namespace App\Jobs;

use App\Models\Product;
use App\Mail\BadgeWarningMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class VerifyBadgePlacement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Product $product
    ) {
    }

    public function handle(): void
    {
        $product = $this->product;

        if ($product->submission_type !== 'badge') {
            return;
        }

        $urlToCheck = $product->badge_placement_url ?: $product->link;

        if (empty($urlToCheck)) {
            Log::warning('Badge verification: No URL to check', ['product_id' => $product->id]);
            return;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->timeout(15)->get($urlToCheck);

            if ($response->failed()) {
                $this->recordFailure($product, "HTTP request failed with status: {$response->status()}");
                return;
            }

            $html = $response->body();
            $expectedUrl = url("/product/{$product->slug}");
            $expectedDomain = 'softwareontheweb.com/product/' . $product->slug;

            // Check for the backlink in the HTML
            $badgeFound = $this->checkForBacklink($html, $expectedUrl, $expectedDomain);

            if ($badgeFound) {
                $this->recordSuccess($product);
            } else {
                $this->recordFailure($product, 'Backlink not found in page HTML');
            }
        } catch (\Exception $e) {
            $this->recordFailure($product, "Exception: {$e->getMessage()}");
        }
    }

    private function checkForBacklink(string $html, string $expectedUrl, string $expectedDomain): bool
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $links = $doc->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Check if the link points to our product page
            if (str_contains($href, $expectedDomain) || str_contains($href, $expectedUrl)) {
                $rel = strtolower($link->getAttribute('rel'));

                // It's a dofollow link if rel doesn't contain "nofollow"
                if (!str_contains($rel, 'nofollow')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function recordSuccess(Product $product): void
    {
        $product->update([
            'badge_verified' => true,
            'badge_verified_at' => now(),
            'badge_consecutive_failures' => 0,
            'badge_warning_sent_at' => null,
        ]);

        Log::info('Badge verification: Success', [
            'product_id' => $product->id,
            'slug' => $product->slug,
        ]);
    }

    private function recordFailure(Product $product, string $reason): void
    {
        $failures = $product->badge_consecutive_failures + 1;

        $product->update([
            'badge_consecutive_failures' => $failures,
        ]);

        Log::warning('Badge verification: Failed', [
            'product_id' => $product->id,
            'slug' => $product->slug,
            'failures' => $failures,
            'reason' => $reason,
        ]);

        // At 3 consecutive failures: send warning email
        if ($failures >= 3 && !$product->badge_warning_sent_at) {
            $this->sendWarningEmail($product);
        }

        // At 7 consecutive failures: unpublish
        if ($failures >= 7) {
            $product->update([
                'is_published' => false,
            ]);

            Log::warning('Badge verification: Product unpublished due to missing badge', [
                'product_id' => $product->id,
                'slug' => $product->slug,
            ]);
        }
    }

    private function sendWarningEmail(Product $product): void
    {
        try {
            $user = $product->user;
            if ($user && $user->email) {
                Mail::to($user->email)->send(new BadgeWarningMail($product));
                $product->update(['badge_warning_sent_at' => now()]);

                Log::info('Badge verification: Warning email sent', [
                    'product_id' => $product->id,
                    'user_email' => $user->email,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Badge verification: Failed to send warning email', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
