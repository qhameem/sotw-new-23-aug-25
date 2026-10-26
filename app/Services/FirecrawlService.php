<?php

namespace App\Services;

use App\Support\ExtractionPageGuard;
use App\Support\PublicUrlGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirecrawlService
{
    public function insufficient(string $markdown): bool
    {
        return mb_strlen(trim($markdown)) < max(1, (int) config('services.firecrawl.min_characters', 300))
            || preg_match('/enable javascript|verify you are human|checking your browser|access denied|just a moment/i', $markdown) === 1;
    }

    public function scrape(string $url): ?array
    {
        if (! config('services.firecrawl.enabled') || ! config('services.firecrawl.api_key')) {
            return null;
        }

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl($url);
            $key = 'firecrawl:v1:'.hash('sha256', $url);
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return $cached ?: null;
            }

            // Avoid duplicate paid requests while another extraction is running.
            return Cache::lock($key.':lock', 45)->get(function () use ($url, $key) {
                $cached = Cache::get($key);
                if (is_array($cached)) {
                    return $cached ?: null;
                }
                $timeout = max(5, min(30, (int) config('services.firecrawl.timeout', 20)));
                $response = Http::withToken(config('services.firecrawl.api_key'))->acceptJson()
                    ->connectTimeout(5)->timeout($timeout + 3)
                    ->post('https://api.firecrawl.dev/v2/scrape', [
                        'url' => $url,
                        'formats' => ['markdown', 'html'],
                        'onlyMainContent' => true,
                        'timeout' => $timeout * 1000,
                        'maxAge' => max(0, (int) config('services.firecrawl.cache_minutes', 1440)) * 60 * 1000,
                    ]);
                $data = $response->json('data');
                $status = (int) data_get($data, 'metadata.statusCode', 0);
                if (! $response->successful() || $response->json('success') !== true || ! is_array($data)
                    || $status < 200 || $status >= 300 || ! empty($data['metadata']['error'])
                    || ! is_string($data['markdown'] ?? null) || $this->insufficient($data['markdown'])) {
                    Cache::put($key, [], now()->addMinutes(5));

                    return null;
                }
                $html = is_string($data['html'] ?? null) ? $data['html'] : '';
                ExtractionPageGuard::assertUsable($html);
                $result = [
                    'url' => $url,
                    'markdown' => mb_substr($data['markdown'], 0, 24000),
                    'html' => mb_substr($html, 0, 262144),
                    'retrieved_at' => now()->toIso8601String(),
                ];
                Cache::put($key, $result, now()->addMinutes(max(1, (int) config('services.firecrawl.cache_minutes', 1440))));

                return $result;
            }) ?: null;
        } catch (\Throwable) {
            // Provider failures must not prevent manual submission or ordinary extraction.
            return null;
        }
    }
}
