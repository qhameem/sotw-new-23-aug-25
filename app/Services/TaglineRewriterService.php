<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaglineRewriterService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 60;
    private const TAGLINE_SOFT_MAX = 88;
    private const PRODUCT_PAGE_TAGLINE_SOFT_MAX = 120;
    private const TAGLINE_HARD_MAX = 140;
    private const PRODUCT_PAGE_TAGLINE_HARD_MAX = 160;
    private array $failures = [];

    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?array
    {
        $this->failures = [];
        $providerRouter = app(AiProviderRoutingService::class);

        if ($providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']) === []) {
            Log::warning('TaglineRewriterService: No AI provider key is set.');
            $this->recordFailure('system', null, 'No AI provider key is set.');
            return null;
        }

        if (empty(trim($rawDescription)) && empty(trim($pageTextContext))) {
            return null;
        }

        $context = mb_substr(strip_tags($pageTextContext), 0, 8000);

        $prompt = <<<PROMPT
You are an experienced human product copywriter and editor. Write like a sharp person with taste, not like an AI assistant, brand strategist, or landing-page generator.

Your goal is to write two distinct taglines for "{$productName}" based on its raw description and website context.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

CRITICAL RULES:
- You MUST rewrite the taglines in your own words. Do not copy whole lines from the website. But if the source contains a short, distinctive positioning phrase that is clearly the best explanation of the product, you may keep that phrase.
- Focus first on clearly explaining what the product does.
- Both lines must feel punchy, useful, and easy to scan in one glance.
- The second line can be slightly fuller, but it is NOT a mini-description or paragraph.
- Write like a calm human editor. Use plain language and concrete verbs.
- Avoid hype, filler, buzzwords, slogans, dramatic setups, rhetorical questions, and clever-but-vague copy.
- Avoid lead-ins like "Meet...", "Say hello to...", "Your X shouldn't...", "Finally...", or similar ad-style openings.
- Avoid vague phrases like "redefine productivity", "supercharge your workflow", "next-generation", "all-in-one solution", or "built for modern teams".
- Preserve strong category or positioning hooks from the source when they are specific and useful, such as "one-person company", instead of flattening them into generic words like "company", "business", "platform", or "tool".
- Prefer the product's clearest native terminology when it improves clarity. For example, do not replace "AI agents" with broader substitutes like "AI team" unless the source itself clearly uses that wording.
- Mention pricing, "no subscription", "free", or "one-time purchase" only when the source clearly presents that as a meaningful differentiator or buyer reason to choose the product, especially in categories where recurring subscriptions are the norm.
- Do not use exclamation marks.
- Prefer one clean sentence or phrase per field.

Constraints:
1. "tagline": Aim for 35-85 characters. Hard max 140 characters. It should read like a Product Hunt-style one-liner: short, natural, specific, and instantly clear.
2. "product_page_tagline": Aim for 45-110 characters. Hard max 160 characters. It should still be a one-line explanation, just slightly fuller or more specific than the short tagline. It must explain the product, not wander into broad brand messaging.

Respond ONLY with valid JSON in the exact structure below. Do NOT wrap it in markdown blockquotes or add any other text.
{
    "tagline": "...",
    "product_page_tagline": "..."
}
PROMPT;

        try {
            foreach ($providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']) as $candidate) {
                $content = match ($candidate['provider']) {
                    'groq' => $this->generateWithGroq($candidate['key'], $prompt),
                    'openrouter' => $this->generateWithOpenRouter($candidate['key'], $prompt),
                    default => $this->generateWithGemini($candidate['key'], $prompt),
                };

                if (!is_string($content) || trim($content) === '') {
                    continue;
                }

                $decoded = $this->decodeJsonResponse($content);

                if (!is_array($decoded) || !isset($decoded['tagline']) || !isset($decoded['product_page_tagline'])) {
                    continue;
                }

                $normalized = $this->normalizeGeneratedTaglines($decoded);

                if ($normalized !== null) {
                    return $normalized;
                }
            }
        } catch (\Exception $e) {
            Log::warning('TaglineRewriterService: Exception', ['message' => $e->getMessage()]);
            return null;
        }

        return null;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    private function generateWithGemini(string $apiKey, string $prompt): ?string
    {
        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(self::TIMEOUT)->post(self::GEMINI_API_URL, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
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
        $response = Http::timeout(self::TIMEOUT)
            ->withToken($apiKey)
            ->post(self::GROQ_API_URL, [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.4,
                'response_format' => ['type' => 'json_object'],
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
        $response = Http::timeout(self::TIMEOUT)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])
            ->post(self::OPENROUTER_API_URL, [
                'model' => (string) config('services.openrouter.model', 'openrouter/auto'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.4,
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

    private function normalizeGeneratedTaglines(array $decoded): ?array
    {
        $tagline = $this->normalizeGeneratedLine(
            (string) ($decoded['tagline'] ?? ''),
            self::TAGLINE_SOFT_MAX,
            self::TAGLINE_HARD_MAX
        );

        $productPageTagline = $this->normalizeGeneratedLine(
            (string) ($decoded['product_page_tagline'] ?? ''),
            self::PRODUCT_PAGE_TAGLINE_SOFT_MAX,
            self::PRODUCT_PAGE_TAGLINE_HARD_MAX
        );

        if ($tagline === '' || $productPageTagline === '') {
            return null;
        }

        return [
            'tagline' => $tagline,
            'product_page_tagline' => $productPageTagline,
        ];
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

        return trim(rtrim($text, " .!?,;:-"));
    }

    private function dropPromotionalLeadIn(string $text): string
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (!is_array($sentences) || count($sentences) < 2) {
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
