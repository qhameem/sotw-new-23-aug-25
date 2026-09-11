<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaglineRewriterService
{
    private const TAGLINE_SOFT_MAX = 88;

    private const TAGLINE_HARD_MAX = 140;

    private array $failures = [];

    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?array
    {
        $this->failures = [];
        $providerRouter = app(AiProviderRoutingService::class);

        $providers = $providerRouter->orderedConfiguredProviders(['openrouter', 'groq', 'gemini']);

        if ($providers === []) {
            Log::warning('TaglineRewriterService: No AI provider key is set.');
            $this->recordFailure('system', null, 'No AI provider key is set.');

            return null;
        }

        if (empty(trim($rawDescription)) && empty(trim($pageTextContext))) {
            return null;
        }

        $rawDescription = $this->compactSourceText(
            $rawDescription,
            max(250, (int) config('services.ai_tagline.max_description_characters', 1000))
        );
        $context = $this->compactSourceText(
            $pageTextContext,
            max(500, (int) config('services.ai_tagline.max_context_characters', 2000))
        );

        $prompt = <<<PROMPT
Write three distinct, clear, factual tagline candidates for "{$productName}".

Source description: {$rawDescription}
Website context: {$context}

Rules:
- Explain the product's primary function using specific, searchable language.
- Create original directory copy, not a quotation from the website.
- Do not reproduce or lightly paraphrase a title or heading from the source.
- Combine the product category with its strongest verified differentiator.
- Preserve useful product terminology. Do not invent claims.
- Avoid hype, vague wording, promotional introductions, and exclamation marks.
- Aim for 35-85 characters. Hard maximum: 140 characters.
- Return JSON only.

{
    "candidates": ["...", "...", "..."]
}
PROMPT;

        foreach ($providers as $candidate) {
            $cacheKey = 'ai_tagline:v4:'.hash('sha256', implode('|', [
                $candidate['provider'],
                $productName,
                $rawDescription,
                $context,
            ]));
            $cached = Cache::get($cacheKey);

            if (is_array($cached) && isset($cached['tagline'])) {
                return $cached;
            }

            $sourceHeadings = $this->extractSourceHeadings($context);
            $forbiddenHeadings = implode("\n- ", array_slice($sourceHeadings, 0, 12));
            $originalityRetryPrompt = <<<PROMPT
Write three original, factual tagline candidates for "{$productName}" using the source description below.

Source description: {$rawDescription}

Do not copy or lightly paraphrase any of these website headings:
- {$forbiddenHeadings}

Rules:
- State the product category and primary function.
- Preserve verified product terminology, but use a new sentence structure.
- Do not invent claims or use hype.
- Aim for 35-85 characters. Hard maximum: 140 characters.
- Return JSON only: {"candidates":["...","...","..."]}
PROMPT;

            try {
                $providerProducedContent = false;

                for ($attempt = 0; $attempt < 2; $attempt++) {
                    $attemptPrompt = $attempt === 0
                        ? $prompt
                        : $originalityRetryPrompt;
                    $content = match ($candidate['provider']) {
                        'groq' => $this->generateWithGroq($candidate['key'], $attemptPrompt),
                        'openrouter' => $this->generateWithOpenRouter($candidate['key'], $attemptPrompt),
                        default => $this->generateWithGemini($candidate['key'], $attemptPrompt),
                    };

                    if (! is_string($content) || trim($content) === '') {
                        break;
                    }

                    $providerProducedContent = true;

                    $decoded = $this->decodeJsonResponse($content);
                    $normalized = is_array($decoded)
                        ? $this->normalizeGeneratedTagline($decoded, $sourceHeadings)
                        : null;

                    if ($normalized !== null) {
                        Cache::put(
                            $cacheKey,
                            $normalized,
                            now()->addMinutes(max(1, (int) config('services.ai_tagline.cache_minutes', 1440)))
                        );

                        return $normalized;
                    }
                }

                if ($providerProducedContent) {
                    $this->recordFailure(
                        $candidate['provider'],
                        null,
                        'The provider returned no usable original tagline candidates.'
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('TaglineRewriterService: Provider exception', [
                    'provider' => $candidate['provider'],
                    'message' => $e->getMessage(),
                ]);
                $this->recordFailure($candidate['provider'], null, $e->getMessage());
            }
        }

        return null;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    private function generateWithGemini(string $apiKey, string $prompt): ?string
    {
        $model = (string) config('services.google.gemini_model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.google.gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout('gemini'))->post($baseUrl.'/models/'.$model.':generateContent', [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => $this->maxOutputTokens(),
                'responseMimeType' => 'application/json',
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('gemini', $response);
            $content = $response->json('candidates.0.content.parts.0.text');

            return is_string($content) ? $content : null;
        }

        Log::warning('TaglineRewriterService: Gemini API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('gemini', $response);
        $this->recordFailure('gemini', $response->status(), $response->body());

        return null;
    }

    private function generateWithGroq(string $apiKey, string $prompt): ?string
    {
        $baseUrl = rtrim((string) config('services.groq.base_url', 'https://api.groq.com/openai/v1'), '/');

        $response = Http::timeout($this->timeout('groq'))
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => (string) config('services.groq.model', 'openai/gpt-oss-120b'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.4,
                'max_tokens' => $this->maxOutputTokens(),
            ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('groq', $response);
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('TaglineRewriterService: Groq API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('groq', $response);
        $this->recordFailure('groq', $response->status(), $response->body());

        return null;
    }

    private function generateWithOpenRouter(string $apiKey, string $prompt): ?string
    {
        $baseUrl = rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');

        $response = Http::timeout($this->timeout('openrouter'))
            ->withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])
            ->post($baseUrl.'/chat/completions', [
                'model' => (string) config('services.openrouter.model', 'openrouter/free'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.4,
                'max_tokens' => $this->maxOutputTokens(),
            ]);

        if ($response->successful()) {
            app(AiProviderRoutingService::class)->recordHttpSuccess('openrouter', $response);
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('TaglineRewriterService: OpenRouter API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        app(AiProviderRoutingService::class)->recordHttpFailure('openrouter', $response);
        $this->recordFailure('openrouter', $response->status(), $response->body());

        return null;
    }

    private function recordFailure(string $provider, ?int $status, string $body): void
    {
        $this->failures[] = [
            'provider' => $provider,
            'status' => $status,
            'body' => $body,
        ];
    }

    private function compactSourceText(string $text, int $maxCharacters): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[^\S\r\n]+/u', ' ', trim($text)) ?? '';
        $text = preg_replace('/\R+/u', "\n", $text) ?? $text;

        return mb_substr($text, 0, $maxCharacters);
    }

    private function timeout(string $provider): int
    {
        $fallback = max(1, (int) config('services.ai_tagline.timeout', 15));

        return match ($provider) {
            'openrouter' => max($fallback, (int) config('services.openrouter.timeout', 45)),
            'gemini' => max($fallback, (int) config('services.google.gemini_timeout', 30)),
            default => $fallback,
        };
    }

    private function maxOutputTokens(): int
    {
        return max(64, (int) config('services.ai_tagline.max_output_tokens', 120));
    }

    private function decodeJsonResponse(string $content): ?array
    {
        $cleaned = trim($content);

        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $cleaned) ?? $cleaned;
            $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;
        }

        $decoded = json_decode($cleaned, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $cleaned, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeGeneratedTagline(array $decoded, array $sourceHeadings = []): ?array
    {
        $candidates = isset($decoded['candidates']) && is_array($decoded['candidates'])
            ? $decoded['candidates']
            : [$decoded['tagline'] ?? ''];

        foreach ($candidates as $candidate) {
            $tagline = $this->normalizeGeneratedLine(
                is_scalar($candidate) ? (string) $candidate : '',
                self::TAGLINE_SOFT_MAX,
                self::TAGLINE_HARD_MAX
            );

            if ($tagline !== '' && ! $this->isTooSimilarToSource($tagline, $sourceHeadings)) {
                return ['tagline' => $tagline];
            }
        }

        return null;
    }

    private function extractSourceHeadings(string $context): array
    {
        preg_match_all('/^(?:Title|H[1-3]):\s*(.+)$/imu', $context, $matches);

        return array_values(array_filter(array_map('trim', $matches[1] ?? [])));
    }

    private function isTooSimilarToSource(string $tagline, array $sourceHeadings): bool
    {
        $candidateTokens = $this->meaningfulTokens($tagline);

        foreach ($sourceHeadings as $heading) {
            $headingTokens = $this->meaningfulTokens((string) $heading);

            if ($candidateTokens === [] || $headingTokens === []) {
                continue;
            }

            $intersection = count(array_intersect($candidateTokens, $headingTokens));
            $coverage = $intersection / min(count($candidateTokens), count($headingTokens));
            $union = count(array_unique(array_merge($candidateTokens, $headingTokens)));
            $jaccard = $union > 0 ? $intersection / $union : 0;

            if ($coverage >= 0.9 || $jaccard >= 0.8) {
                return true;
            }
        }

        return false;
    }

    private function meaningfulTokens(string $text): array
    {
        $text = mb_strtolower(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ignored = ['a', 'an', 'and', 'for', 'of', 'the', 'that', 'to', 'with', 'your'];

        return array_values(array_unique(array_diff($tokens, $ignored)));
    }

    private function normalizeGeneratedLine(string $text, int $softMax, int $hardMax): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        $text = trim($text, " \t\n\r\0\x0B\"'`");

        if ($text === '') {
            return '';
        }

        $text = $this->dropPromotionalLeadIn($text);
        $text = preg_replace('/\s+[—–]\s+/u', ' - ', $text) ?? $text;

        if (mb_strlen($text) > $softMax) {
            $text = Str::limit($text, $softMax, '...');
        } elseif (mb_strlen($text) > $hardMax) {
            $text = Str::limit($text, $hardMax, '...');
        }

        return trim(rtrim($text, ' .!?,;:-'));
    }

    private function dropPromotionalLeadIn(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($sentences) || count($sentences) < 2) {
            return $text;
        }

        $firstSentence = mb_strtolower(trim($sentences[0]));
        $leadIns = [
            'meet ',
            'say hello',
            'introducing ',
            'finally',
            'your ',
            'stop ',
            'forget ',
            'no more ',
            'why ',
            'tired of ',
        ];

        foreach ($leadIns as $leadIn) {
            if (str_starts_with($firstSentence, $leadIn)) {
                return trim(implode(' ', array_slice($sentences, 1)));
            }
        }

        return $text;
    }
}
