<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DomainRatingService
{
    private const ENDPOINT = 'https://api.ahrefs.com/v3/public/domain-rating-free';

    public const BATCH_SIZE = 1000;

    public function get(): ?float
    {
        return Cache::get($this->cacheKey());
    }

    public function refresh(): bool
    {
        $key = config('services.ahrefs.api_key');
        $domain = $this->domain();

        if (blank($key) || blank($domain)) {
            return false;
        }

        try {
            $response = $this->client($key)->get(self::ENDPOINT, ['target' => $domain]);
        } catch (ConnectionException) {
            return false;
        }

        $rating = $response->json('domain_rating.domain_rating');

        if (! $response->successful() || ! is_numeric($rating) || $rating < 0 || $rating > 100) {
            return false;
        }

        return Cache::forever($this->cacheKey(), (float) $rating);
    }

    public function fetchBatch(array $domains): array
    {
        $key = config('services.ahrefs.api_key');
        if (blank($key)) {
            throw new RuntimeException('AHREFS_API_KEY is missing.');
        }

        try {
            $response = $this->client($key)->timeout(30)
                ->retry([1000, 3000], when: static fn ($exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && ($exception->response->tooManyRequests() || $exception->response->serverError())), throw: false)
                ->post(self::ENDPOINT, ['targets' => $domains, 'version' => 'dr3']);
        } catch (ConnectionException) {
            throw new RuntimeException('Unable to connect to Ahrefs.');
        }

        if (! $response->successful()) {
            throw new RuntimeException('Ahrefs returned HTTP '.$response->status().'.');
        }

        $targets = $response->json('domain_rating.targets', $response->json('targets'));
        if (! is_array($targets)) {
            throw new RuntimeException('Ahrefs returned an unexpected batch response.');
        }

        return collect($targets)->filter(function ($entry) {
            $rating = $entry['domain_rating'] ?? null;

            return is_string($entry['target'] ?? null) && is_numeric($rating) && $rating >= 0 && $rating <= 100;
        })->mapWithKeys(fn ($entry) => [$entry['target'] => (float) $entry['domain_rating']])->all();
    }

    private function client(string $key): PendingRequest
    {
        return Http::withToken($key)->acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(10);
    }

    private function domain(): ?string
    {
        return parse_url(config('app.url'), PHP_URL_HOST) ?: null;
    }

    private function cacheKey(): string
    {
        return 'site.ahrefs_domain_rating.'.$this->domain();
    }
}
