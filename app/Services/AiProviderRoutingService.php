<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AiProviderRoutingService
{
    private const CACHE_PREFIX = 'ai_provider_runtime_status_v1:';
    private const SUCCESS_TTL_MINUTES = 60;
    private const FAILURE_TTL_HOURS = 12;

    public function orderedConfiguredProviders(array $providers): array
    {
        $candidates = [];

        foreach ($providers as $provider) {
            $key = $this->apiKeyFor($provider);

            if (!filled($key)) {
                continue;
            }

            $candidates[] = [
                'provider' => $provider,
                'key' => $key,
                'score' => $this->scoreProvider($provider),
            ];
        }

        usort($candidates, static function (array $left, array $right): int {
            return $right['score'] <=> $left['score'];
        });

        return array_map(static function (array $candidate): array {
            unset($candidate['score']);

            return $candidate;
        }, $candidates);
    }

    public function recordHttpSuccess(string $provider, Response $response): void
    {
        $status = $this->currentStatus($provider);
        $checkedAt = now();

        $merged = array_merge($status, [
            'provider' => $provider,
            'state' => 'ok',
            'checked_at' => $checkedAt->toIso8601String(),
            'last_success_at' => $checkedAt->toIso8601String(),
            'last_failure_at' => $status['last_failure_at'] ?? null,
            'status' => $response->status(),
            'retry_at' => null,
        ], $this->extractMetrics($provider, $response, $checkedAt));

        $this->putStatus($provider, $merged, now()->addMinutes(self::SUCCESS_TTL_MINUTES));
    }

    public function recordHttpFailure(string $provider, Response $response): void
    {
        $status = $this->currentStatus($provider);
        $checkedAt = now();
        $retryAt = $this->extractRetryAt($provider, $response, $checkedAt);
        $httpStatus = $response->status();
        $state = in_array($httpStatus, [402, 429], true) ? 'limited' : 'error';

        $merged = array_merge($status, [
            'provider' => $provider,
            'state' => $state,
            'checked_at' => $checkedAt->toIso8601String(),
            'last_failure_at' => $checkedAt->toIso8601String(),
            'status' => $httpStatus,
            'retry_at' => $retryAt?->toIso8601String(),
            'message' => $this->extractMessage($response->body()),
        ], $this->extractMetrics($provider, $response, $checkedAt));

        if ($provider === 'gemini' && $merged['daily_reset_at'] === null) {
            $merged['daily_reset_at'] = $this->nextGeminiDailyResetAt()?->toIso8601String();
        }

        $ttl = $retryAt && $retryAt->isFuture()
            ? $retryAt
            : now()->addHours(self::FAILURE_TTL_HOURS);

        $this->putStatus($provider, $merged, $ttl);
    }

    public function currentStatus(string $provider): array
    {
        return Cache::get($this->cacheKey($provider), $this->defaultStatus($provider));
    }

    public function apiKeyFor(string $provider): ?string
    {
        if (!$this->providerEnabled($provider)) {
            return null;
        }

        return match ($provider) {
            'groq' => $this->filledOrNull(config('services.groq.key')),
            'gemini' => $this->filledOrNull(config('services.google.api_key')),
            'openrouter' => $this->filledOrNull(config('services.openrouter.key')),
            default => null,
        };
    }

    public function modelFor(string $provider): ?string
    {
        return match ($provider) {
            'groq' => 'llama-3.3-70b-versatile',
            'gemini' => 'gemini-2.5-flash',
            'openrouter' => (string) config('services.openrouter.model', 'openrouter/auto'),
            default => null,
        };
    }

    private function scoreProvider(string $provider): int
    {
        $status = $this->effectiveStatus($provider);
        $score = match ($provider) {
            'groq' => 300,
            'gemini' => 250,
            'openrouter' => 220,
            default => 0,
        };

        $retryAt = isset($status['retry_at']) && is_string($status['retry_at']) ? Carbon::parse($status['retry_at']) : null;
        if ($retryAt && $retryAt->isFuture()) {
            return -10000;
        }

        $score += match ($status['state'] ?? null) {
            'ok' => 40,
            'limited' => -300,
            'error' => -80,
            default => 0,
        };

        if (isset($status['request_remaining']) && is_numeric($status['request_remaining'])) {
            $score += min((int) $status['request_remaining'], 1000) / 5;
        }

        if (isset($status['token_remaining']) && is_numeric($status['token_remaining'])) {
            $score += min((int) $status['token_remaining'], 50000) / 1000;
        }

        if (isset($status['credit_remaining']) && is_numeric($status['credit_remaining'])) {
            $score += (float) $status['credit_remaining'] > 0 ? 35 : -200;
        }

        if ($provider === 'gemini' && ($status['state'] ?? null) !== 'limited') {
            $score += 10;
        }

        return (int) round($score);
    }

    private function effectiveStatus(string $provider): array
    {
        $runtime = $this->currentStatus($provider);
        $dashboard = $this->dashboardSnapshot($provider);

        if ($dashboard === null) {
            return $runtime;
        }

        $merged = $dashboard;

        foreach ($runtime as $key => $value) {
            if ($value !== null && $value !== 'not_checked') {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    private function extractMetrics(string $provider, Response $response, Carbon $checkedAt): array
    {
        if ($provider === 'groq') {
            return [
                'request_limit' => $this->normalizeNumericHeader($response->header('x-ratelimit-limit-requests')),
                'request_remaining' => $this->normalizeNumericHeader($response->header('x-ratelimit-remaining-requests')),
                'request_reset_at' => $this->parseHeaderResetAt($response->header('x-ratelimit-reset-requests'), $checkedAt)?->toIso8601String(),
                'token_limit' => $this->normalizeNumericHeader($response->header('x-ratelimit-limit-tokens')),
                'token_remaining' => $this->normalizeNumericHeader($response->header('x-ratelimit-remaining-tokens')),
                'token_reset_at' => $this->parseHeaderResetAt($response->header('x-ratelimit-reset-tokens'), $checkedAt)?->toIso8601String(),
                'daily_reset_at' => null,
                'credit_remaining' => null,
            ];
        }

        if ($provider === 'openrouter') {
            $decoded = json_decode($response->body(), true);

            return [
                'request_limit' => null,
                'request_remaining' => null,
                'request_reset_at' => null,
                'token_limit' => null,
                'token_remaining' => null,
                'token_reset_at' => null,
                'daily_reset_at' => null,
                'credit_remaining' => is_numeric(data_get($decoded, 'limit_remaining'))
                    ? (float) data_get($decoded, 'limit_remaining')
                    : (is_numeric(data_get($decoded, 'data.limit_remaining')) ? (float) data_get($decoded, 'data.limit_remaining') : null),
            ];
        }

        if ($provider === 'gemini') {
            return [
                'request_limit' => null,
                'request_remaining' => null,
                'request_reset_at' => null,
                'token_limit' => null,
                'token_remaining' => null,
                'token_reset_at' => null,
                'daily_reset_at' => $this->nextGeminiDailyResetAt()?->toIso8601String(),
                'credit_remaining' => null,
            ];
        }

        return [];
    }

    private function extractRetryAt(string $provider, Response $response, Carbon $checkedAt): ?Carbon
    {
        if ($provider === 'groq') {
            return $this->parseHeaderResetAt($response->header('x-ratelimit-reset-requests'), $checkedAt)
                ?? $this->parseRetryAfterToTime($response->header('retry-after'), $checkedAt);
        }

        if ($provider === 'openrouter') {
            return $this->parseRetryAfterToTime($response->header('retry-after'), $checkedAt);
        }

        if ($provider === 'gemini') {
            if (preg_match('/"retryDelay"\s*:\s*"([^"]+)"/', $response->body(), $matches) === 1) {
                $seconds = $this->parseDurationToSeconds($matches[1]);

                return $seconds !== null ? $checkedAt->copy()->addSeconds($seconds) : null;
            }

            return null;
        }

        return null;
    }

    private function extractMessage(string $body): ?string
    {
        $decoded = json_decode($body, true);

        foreach (['error.message', 'message', 'data.message'] as $path) {
            $message = data_get($decoded, $path);
            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }
        }

        return null;
    }

    private function parseHeaderResetAt(string|array|null $value, Carbon $checkedAt): ?Carbon
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $seconds = $this->parseDurationToSeconds($value);

        return $seconds !== null ? $checkedAt->copy()->addSeconds($seconds) : null;
    }

    private function parseRetryAfterToTime(string|array|null $value, Carbon $checkedAt): ?Carbon
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        if (is_numeric($trimmed)) {
            return $checkedAt->copy()->addSeconds((int) ceil((float) $trimmed));
        }

        try {
            return Carbon::parse($trimmed);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeNumericHeader(string|array|null $value): ?int
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d]/', '', $value);

        return $normalized !== '' ? (int) $normalized : null;
    }

    private function parseDurationToSeconds(string $value): ?int
    {
        preg_match_all('/(\d+(?:\.\d+)?)([hms])/i', trim($value), $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $seconds = 0.0;

        foreach ($matches as $match) {
            $amount = (float) $match[1];
            $unit = strtolower($match[2]);

            $seconds += match ($unit) {
                'h' => $amount * 3600,
                'm' => $amount * 60,
                default => $amount,
            };
        }

        return (int) ceil($seconds);
    }

    private function putStatus(string $provider, array $status, Carbon $expiresAt): void
    {
        Cache::put($this->cacheKey($provider), $status, $expiresAt);
    }

    private function cacheKey(string $provider): string
    {
        return self::CACHE_PREFIX . $provider;
    }

    private function dashboardSnapshot(string $provider): ?array
    {
        $snapshots = app(AiProviderStatusService::class)->latestSnapshots();

        foreach ($snapshots as $snapshot) {
            if (($snapshot['provider'] ?? null) === $provider) {
                return is_array($snapshot) ? $snapshot : null;
            }
        }

        return null;
    }

    private function defaultStatus(string $provider): array
    {
        return [
            'provider' => $provider,
            'state' => 'not_checked',
            'checked_at' => null,
            'last_success_at' => null,
            'last_failure_at' => null,
            'status' => null,
            'retry_at' => null,
            'request_limit' => null,
            'request_remaining' => null,
            'request_reset_at' => null,
            'token_limit' => null,
            'token_remaining' => null,
            'token_reset_at' => null,
            'daily_reset_at' => $provider === 'gemini' ? $this->nextGeminiDailyResetAt()?->toIso8601String() : null,
            'credit_remaining' => null,
            'message' => null,
        ];
    }

    private function nextGeminiDailyResetAt(): ?Carbon
    {
        try {
            return Carbon::now('America/Los_Angeles')->addDay()->startOfDay()->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function filledOrNull(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    private function providerEnabled(string $provider): bool
    {
        if ($provider === '') {
            return true;
        }

        if (!Storage::disk('local')->exists('settings.json')) {
            return true;
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true) ?: [];

        return filter_var(data_get($settings, "ai_providers.{$provider}.enabled", true), FILTER_VALIDATE_BOOL);
    }
}
