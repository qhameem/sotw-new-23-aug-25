<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiProviderStatusService
{
    private const CACHE_KEY = 'admin_ai_provider_status_v1';
    private const CACHE_TTL_MINUTES = 10;

    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';

    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GEMINI_MODEL = 'gemini-2.5-flash';
    private const OPENROUTER_KEY_API_URL = 'https://openrouter.ai/api/v1/key';
    private const OPENROUTER_MODEL = 'openrouter/auto';

    public function latestSnapshots(): array
    {
        return $this->applyProviderFlags(
            Cache::get(self::CACHE_KEY, $this->defaultSnapshots())
        );
    }

    public function refreshSnapshots(): array
    {
        $snapshots = [
            $this->probeGroq(),
            $this->probeGemini(),
            $this->probeOpenRouter(),
        ];

        Cache::put(self::CACHE_KEY, $snapshots, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $this->applyProviderFlags($snapshots);
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function defaultSnapshots(): array
    {
        return [
            $this->baseSnapshot(
                provider: 'groq',
                label: 'Groq',
                configured: filled((string) config('services.groq.key')),
                model: self::GROQ_MODEL,
                docsUrl: 'https://console.groq.com/docs/rate-limits',
                dashboardUrl: 'https://console.groq.com/settings/limits'
            ),
            $this->baseSnapshot(
                provider: 'gemini',
                label: 'Gemini',
                configured: filled((string) config('services.google.api_key')),
                model: self::GEMINI_MODEL,
                docsUrl: 'https://ai.google.dev/gemini-api/docs/rate-limits',
                dashboardUrl: 'https://aistudio.google.com/'
            ),
            $this->baseSnapshot(
                provider: 'openrouter',
                label: 'OpenRouter',
                configured: filled((string) config('services.openrouter.key')),
                model: (string) config('services.openrouter.model', self::OPENROUTER_MODEL),
                docsUrl: 'https://openrouter.ai/docs/api-reference/limits/',
                dashboardUrl: 'https://openrouter.ai/settings/keys'
            ),
        ];
    }

    private function baseSnapshot(
        string $provider,
        string $label,
        bool $configured,
        string $model,
        string $docsUrl,
        string $dashboardUrl
    ): array {
        return [
            'provider' => $provider,
            'label' => $label,
            'configured' => $configured,
            'enabled' => $this->providerEnabled($provider),
            'model' => $model,
            'docs_url' => $docsUrl,
            'dashboard_url' => $dashboardUrl,
            'state' => $configured ? 'not_checked' : 'missing_key',
            'status_label' => $configured ? 'Not checked yet' : 'Missing API key',
            'message' => $configured
                ? 'Click "Check AI quota now" to run a live status check.'
                : 'No API key is configured for this provider.',
            'checked_at' => null,
            'retry_at' => null,
            'request_limit' => null,
            'request_remaining' => null,
            'request_reset_at' => null,
            'token_limit' => null,
            'token_remaining' => null,
            'token_reset_at' => null,
            'credit_limit' => null,
            'credit_remaining' => null,
            'limit_reset_type' => null,
            'next_limit_reset_at' => null,
            'usage_total' => null,
            'usage_daily' => null,
            'usage_weekly' => null,
            'usage_monthly' => null,
            'daily_reset_at' => $provider === 'gemini' ? $this->nextGeminiDailyResetAt()?->toIso8601String() : null,
            'exact_usage_available' => in_array($provider, ['groq', 'openrouter'], true),
            'notes' => match ($provider) {
                'groq' => ['Groq exposes live rate-limit headers on API responses.'],
                'openrouter' => ['OpenRouter exposes key-level limit and remaining credit information via its key endpoint.'],
                default => ['Gemini does not expose exact live remaining quota in this API response; use AI Studio for exact counters.'],
            },
        ];
    }

    private function probeGroq(): array
    {
        $apiKey = (string) config('services.groq.key');
        $snapshot = $this->baseSnapshot(
            provider: 'groq',
            label: 'Groq',
            configured: filled($apiKey),
            model: self::GROQ_MODEL,
            docsUrl: 'https://console.groq.com/docs/rate-limits',
            dashboardUrl: 'https://console.groq.com/settings/limits'
        );

        if (!$snapshot['configured']) {
            return $snapshot;
        }

        $checkedAt = now();

        try {
            $response = Http::timeout(20)
                ->withToken($apiKey)
                ->post(self::GROQ_API_URL, [
                    'model' => self::GROQ_MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'ping',
                        ],
                    ],
                    'max_tokens' => 1,
                    'temperature' => 0,
                ]);

            $requestResetAt = $this->parseHeaderResetAt($response->header('x-ratelimit-reset-requests'), $checkedAt)
                ?? $this->parseRetryAfterToTime($response->header('retry-after'), $checkedAt);
            $tokenResetAt = $this->parseHeaderResetAt($response->header('x-ratelimit-reset-tokens'), $checkedAt);
            $retryAt = $this->parseRetryAfterToTime($response->header('retry-after'), $checkedAt);

            $snapshot['checked_at'] = $checkedAt->toIso8601String();
            $snapshot['request_limit'] = $this->normalizeNumericHeader($response->header('x-ratelimit-limit-requests'));
            $snapshot['request_remaining'] = $this->normalizeNumericHeader($response->header('x-ratelimit-remaining-requests'));
            $snapshot['request_reset_at'] = $requestResetAt?->toIso8601String();
            $snapshot['token_limit'] = $this->normalizeNumericHeader($response->header('x-ratelimit-limit-tokens'));
            $snapshot['token_remaining'] = $this->normalizeNumericHeader($response->header('x-ratelimit-remaining-tokens'));
            $snapshot['token_reset_at'] = $tokenResetAt?->toIso8601String();
            $snapshot['retry_at'] = $retryAt?->toIso8601String();

            if ($response->successful()) {
                $snapshot['state'] = 'ok';
                $snapshot['status_label'] = 'Available now';
                $snapshot['message'] = 'Live headers were fetched successfully.';
                return $snapshot;
            }

            if ($response->status() === 429) {
                $snapshot['state'] = 'limited';
                $snapshot['status_label'] = 'Rate limited or quota exhausted';
                $snapshot['message'] = $this->extractMessageFromBody($response->body())
                    ?: 'Groq is currently rate limited for this key.';
                return $snapshot;
            }

            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = 'Groq returned HTTP ' . $response->status() . '.';
            return $snapshot;
        } catch (\Throwable $e) {
            $snapshot['checked_at'] = $checkedAt->toIso8601String();
            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = Str::limit($e->getMessage(), 180, '...');

            return $snapshot;
        }
    }

    private function probeGemini(): array
    {
        $apiKey = (string) config('services.google.api_key');
        $snapshot = $this->baseSnapshot(
            provider: 'gemini',
            label: 'Gemini',
            configured: filled($apiKey),
            model: self::GEMINI_MODEL,
            docsUrl: 'https://ai.google.dev/gemini-api/docs/rate-limits',
            dashboardUrl: 'https://aistudio.google.com/'
        );

        if (!$snapshot['configured']) {
            return $snapshot;
        }

        $checkedAt = now();
        $dailyResetAt = $this->nextGeminiDailyResetAt();

        try {
            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post(self::GEMINI_API_URL, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'ping'],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 1,
                ],
            ]);

            $retryAt = $this->extractRetryAtFromGeminiBody($response->body(), $checkedAt);

            $snapshot['checked_at'] = $checkedAt->toIso8601String();
            $snapshot['retry_at'] = $retryAt?->toIso8601String();
            $snapshot['daily_reset_at'] = $dailyResetAt?->toIso8601String();

            if ($response->successful()) {
                $snapshot['state'] = 'ok';
                $snapshot['status_label'] = 'Available now';
                $snapshot['message'] = 'Probe succeeded. Gemini does not expose exact live remaining quota here, so use AI Studio for exact counters.';
                return $snapshot;
            }

            if ($response->status() === 429) {
                $snapshot['state'] = 'limited';
                $snapshot['status_label'] = 'Rate limited or quota exhausted';
                $snapshot['message'] = $this->extractMessageFromBody($response->body())
                    ?: 'Gemini is currently rate limited for this project.';
                return $snapshot;
            }

            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = 'Gemini returned HTTP ' . $response->status() . '.';
            return $snapshot;
        } catch (\Throwable $e) {
            $snapshot['checked_at'] = $checkedAt->toIso8601String();
            $snapshot['daily_reset_at'] = $dailyResetAt?->toIso8601String();
            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = Str::limit($e->getMessage(), 180, '...');

            return $snapshot;
        }
    }

    private function probeOpenRouter(): array
    {
        $apiKey = (string) config('services.openrouter.key');
        $snapshot = $this->baseSnapshot(
            provider: 'openrouter',
            label: 'OpenRouter',
            configured: filled($apiKey),
            model: (string) config('services.openrouter.model', self::OPENROUTER_MODEL),
            docsUrl: 'https://openrouter.ai/docs/api-reference/limits/',
            dashboardUrl: 'https://openrouter.ai/settings/keys'
        );

        if (!$snapshot['configured']) {
            return $snapshot;
        }

        $checkedAt = now();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])->timeout(20)->get(self::OPENROUTER_KEY_API_URL);

            $snapshot['checked_at'] = $checkedAt->toIso8601String();

            if ($response->successful()) {
                $data = $response->json('data', []);
                $limitResetType = is_string($data['limit_reset'] ?? null) ? trim((string) $data['limit_reset']) : null;
                $snapshot['state'] = 'ok';
                $snapshot['status_label'] = 'Available now';
                $snapshot['message'] = 'OpenRouter key details were fetched successfully.';
                $snapshot['credit_limit'] = is_numeric($data['limit'] ?? null) ? (float) $data['limit'] : null;
                $snapshot['credit_remaining'] = is_numeric($data['limit_remaining'] ?? null) ? (float) $data['limit_remaining'] : null;
                $snapshot['limit_reset_type'] = $limitResetType;
                $snapshot['next_limit_reset_at'] = $this->openRouterLimitResetAt($limitResetType)?->toIso8601String();
                $snapshot['usage_total'] = is_numeric($data['usage'] ?? null) ? (float) $data['usage'] : null;
                $snapshot['usage_daily'] = is_numeric($data['usage_daily'] ?? null) ? (float) $data['usage_daily'] : null;
                $snapshot['usage_weekly'] = is_numeric($data['usage_weekly'] ?? null) ? (float) $data['usage_weekly'] : null;
                $snapshot['usage_monthly'] = is_numeric($data['usage_monthly'] ?? null) ? (float) $data['usage_monthly'] : null;

                return $snapshot;
            }

            if (in_array($response->status(), [402, 429], true)) {
                $snapshot['state'] = 'limited';
                $snapshot['status_label'] = 'Rate limited or credits exhausted';
                $snapshot['message'] = $this->extractMessageFromBody($response->body())
                    ?: 'OpenRouter is currently limited for this key.';

                return $snapshot;
            }

            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = 'OpenRouter returned HTTP ' . $response->status() . '.';

            return $snapshot;
        } catch (\Throwable $e) {
            $snapshot['checked_at'] = $checkedAt->toIso8601String();
            $snapshot['state'] = 'error';
            $snapshot['status_label'] = 'Request failed';
            $snapshot['message'] = Str::limit($e->getMessage(), 180, '...');

            return $snapshot;
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

    private function extractRetryAtFromGeminiBody(string $body, Carbon $checkedAt): ?Carbon
    {
        if (preg_match('/"retryDelay"\s*:\s*"([^"]+)"/', $body, $matches) !== 1) {
            return null;
        }

        $seconds = $this->parseDurationToSeconds($matches[1]);

        return $seconds !== null ? $checkedAt->copy()->addSeconds($seconds) : null;
    }

    private function extractMessageFromBody(string $body): ?string
    {
        $decoded = json_decode($body, true);
        $message = data_get($decoded, 'error.message');

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return null;
    }

    private function nextGeminiDailyResetAt(): ?Carbon
    {
        try {
            return Carbon::now('America/Los_Angeles')->addDay()->startOfDay()->utc();
        } catch (\Throwable) {
            return null;
        }
    }

    private function openRouterLimitResetAt(?string $resetType): ?Carbon
    {
        if (!is_string($resetType) || trim($resetType) === '') {
            return null;
        }

        return match (trim(strtolower($resetType))) {
            'daily' => Carbon::now('UTC')->addDay()->startOfDay(),
            'weekly' => Carbon::now('UTC')->next(Carbon::MONDAY)->startOfDay(),
            'monthly' => Carbon::now('UTC')->addMonthNoOverflow()->startOfMonth(),
            default => null,
        };
    }

    private function applyProviderFlags(array $snapshots): array
    {
        return array_map(function (array $snapshot): array {
            $provider = (string) ($snapshot['provider'] ?? '');
            $enabled = $this->providerEnabled($provider);
            $snapshot['enabled'] = $enabled;

            if (!$enabled) {
                $snapshot['notes'] = array_values(array_unique(array_merge(
                    ['This provider is disabled in admin settings and will not be used for new AI requests.'],
                    is_array($snapshot['notes'] ?? null) ? $snapshot['notes'] : []
                )));
            }

            return $snapshot;
        }, $snapshots);
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
