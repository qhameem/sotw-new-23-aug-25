<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DomainRatingService
{
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
            $response = Http::withToken($key)
                ->acceptJson()
                ->withoutRedirecting()
                ->connectTimeout(3)
                ->timeout(10)
                ->get('https://api.ahrefs.com/v3/public/domain-rating-free', ['target' => $domain]);
        } catch (ConnectionException) {
            return false;
        }

        $rating = $response->json('domain_rating.domain_rating');

        if (! $response->successful() || ! is_numeric($rating) || $rating < 0 || $rating > 100) {
            return false;
        }

        return Cache::forever($this->cacheKey(), (float) $rating);
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
