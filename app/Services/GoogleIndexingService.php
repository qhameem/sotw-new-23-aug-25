<?php

namespace App\Services;

use App\Contracts\UrlNotificationProvider;
use Google\Client as GoogleClient;
use Google\Service\Indexing;
use Google\Service\Indexing\UrlNotification;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleIndexingService implements UrlNotificationProvider
{
    public function isEnabled(): bool
    {
        if (! filter_var(config('services.google_indexing.enabled', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        try {
            return $this->credentialsConfig() !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function notifyUrls(array $urls, string $type = self::TYPE_UPDATED): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $normalizedUrls = $this->normalizeUrls($urls);

        if ($normalizedUrls->isEmpty()) {
            return;
        }

        $service = new Indexing($this->makeClient());

        foreach ($normalizedUrls as $url) {
            $notification = new UrlNotification();
            $notification->setType($type);
            $notification->setUrl($url);

            $service->urlNotifications->publish($notification);
        }

        Log::info('Google Indexing API submitted URLs successfully.', [
            'count' => $normalizedUrls->count(),
            'type' => $type,
        ]);
    }

    protected function makeClient(): GoogleClient
    {
        $credentials = $this->credentialsConfig();

        if ($credentials === null) {
            throw new RuntimeException('Google Indexing API credentials are missing.');
        }

        $client = new GoogleClient();
        $client->setApplicationName((string) config('app.name', 'Laravel'));
        $client->setScopes([Indexing::INDEXING]);
        $client->setAuthConfig($credentials);
        $client->setHttpClient(new GuzzleClient([
            'timeout' => (int) config('services.google_indexing.timeout', 10),
        ]));

        return $client;
    }

    protected function credentialsConfig(): array|string|null
    {
        $path = $this->resolveCredentialsPath();

        if ($path !== '') {
            if (! is_file($path)) {
                throw new RuntimeException('Google Indexing API credential file was not found.');
            }

            return $path;
        }

        $json = trim((string) config('services.google_indexing.service_account_json', ''));

        if ($json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $decoded = json_decode(base64_decode($json, true) ?: '', true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        throw new RuntimeException('Google Indexing API credentials must be valid JSON or base64-encoded JSON.');
    }

    protected function resolveCredentialsPath(): string
    {
        $path = trim((string) config('services.google_indexing.service_account_json_path', ''));

        if ($path === '') {
            return '';
        }

        if (is_file($path)) {
            return $path;
        }

        $relativePath = base_path($path);

        return is_file($relativePath) ? $relativePath : $path;
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
