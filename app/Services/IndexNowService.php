<?php

namespace App\Services;

use App\Contracts\UrlNotificationProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IndexNowService implements UrlNotificationProvider
{
    public function isEnabled(): bool
    {
        return filter_var(config('services.indexnow.enabled', false), FILTER_VALIDATE_BOOL)
            && filled($this->key());
    }

    public function key(): ?string
    {
        $key = trim((string) config('services.indexnow.key', ''));

        return $key !== '' ? $key : null;
    }

    public function keyLocation(): ?string
    {
        $key = $this->key();

        if (! $key) {
            return null;
        }

        return route('indexnow.key', ['key' => $key]);
    }

    public function submitUrls(array $urls): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $normalizedUrls = $this->normalizeUrls($urls);

        if ($normalizedUrls->isEmpty()) {
            return;
        }

        $host = parse_url($normalizedUrls->first(), PHP_URL_HOST);
        $keyLocation = $this->keyLocation();

        if (! is_string($host) || $host === '' || ! $keyLocation) {
            throw new RuntimeException('IndexNow configuration is incomplete.');
        }

        $response = Http::asJson()
            ->timeout((int) config('services.indexnow.timeout', 10))
            ->post((string) config('services.indexnow.endpoint', 'https://api.indexnow.org/indexnow'), [
                'host' => $host,
                'key' => $this->key(),
                'keyLocation' => $keyLocation,
                'urlList' => $normalizedUrls->values()->all(),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('IndexNow submission failed with status ' . $response->status() . '.');
        }

        Log::info('IndexNow submitted URLs successfully.', [
            'host' => $host,
            'count' => $normalizedUrls->count(),
        ]);
    }

    public function notifyUrls(array $urls, string $type = self::TYPE_UPDATED): void
    {
        $this->submitUrls($urls);
    }

    protected function normalizeUrls(array $urls): Collection
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        return collect($urls)
            ->filter(fn ($url) => is_string($url) && filter_var($url, FILTER_VALIDATE_URL))
            ->map(fn (string $url) => trim($url))
            ->filter(function (string $url) use ($appHost) {
                $host = parse_url($url, PHP_URL_HOST);

                return is_string($host) && $host !== '' && $host === $appHost;
            })
            ->unique()
            ->values();
    }
}
