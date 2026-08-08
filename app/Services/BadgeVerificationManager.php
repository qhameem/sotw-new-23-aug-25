<?php

namespace App\Services;

use App\Mail\BadgeWarningMail;
use App\Models\BadgeVerificationAttempt;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BadgeVerificationManager
{
    public function __construct(private BadgeService $badgeService) {}

    public function verify(Product $product, string $trigger, ?User $admin = null, ?string $requestIp = null): array
    {
        $url = $product->badge_placement_url ?: $product->link;
        $result = $this->badgeService->verifyPlacementUrl($url);

        return $this->record($product, $result, $trigger, $admin, $requestIp);
    }

    public function record(Product $product, array $result, string $trigger, ?User $admin = null, ?string $requestIp = null): array
    {
        $verified = (bool) ($result['verified'] ?? false);
        $wasPublished = (bool) $product->is_published;

        BadgeVerificationAttempt::create([
            'product_id' => $product->id,
            'triggered_by_user_id' => $admin?->id,
            'trigger' => $trigger,
            'url' => $product->badge_placement_url ?: $product->link,
            'verified' => $verified,
            'http_status' => $result['http_status'] ?? null,
            'response_hash' => $result['response_hash'] ?? null,
            'matched_element' => $result['matched_element'] ?? null,
            'message' => $result['message'] ?? null,
            'request_ip' => $requestIp,
            'checked_at' => now(),
        ]);

        if ($verified) {
            $product->update([
                'badge_verified' => true,
                'badge_verified_at' => now(),
                'badge_consecutive_failures' => 0,
                'badge_warning_sent_at' => null,
            ]);

            return $result;
        }

        $product->update([
            'badge_verified' => false,
            'badge_consecutive_failures' => ((int) $product->badge_consecutive_failures) + 1,
            'is_published' => false,
        ]);

        if (! $product->badge_warning_sent_at && $product->user?->email) {
            try {
                Mail::to($product->user->email)->send(new BadgeWarningMail($product, $wasPublished));
                $product->update(['badge_warning_sent_at' => now()]);
            } catch (\Throwable $e) {
                Log::error('Failed to send badge verification failure email.', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('Badge verification failed; publication blocked.', [
            'product_id' => $product->id,
            'trigger' => $trigger,
            'was_published' => $wasPublished,
            'message' => $result['message'] ?? null,
        ]);

        $result['was_published'] = $wasPublished;

        return $result;
    }
}
