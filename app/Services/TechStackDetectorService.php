<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TechStackDetectorService
{
    public function detect(string $url): array
    {
        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return [];
            }

            $html = $response->body();
            $headers = $response->headers();

            $detected = [];

            // A simple detection logic. This can be expanded significantly.
            if (str_contains($html, 'wp-content')) {
                $detected[] = 'WordPress';
            }
            if (str_contains($html, 'Shopify')) {
                $detected[] = 'Shopify';
            }
            if (str_contains($html, 'next/static')) {
                $detected[] = 'Next.js';
            }
            if (str_contains($html, 'laravel_session')) {
                $detected[] = 'Laravel';
            }
            if (isset($headers['X-Powered-By']) && str_contains($headers['X-Powered-By'][0], 'Next.js')) {
                $detected[] = 'Next.js';
            }

            return array_unique($detected);
        } catch (\Exception $e) {
            Log::error("Failed to detect tech stack for {$url}: " . $e->getMessage());
            return [];
        }
    }
}