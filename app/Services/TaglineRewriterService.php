<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TaglineRewriterService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile'; // Powerful 70B model
    private const TIMEOUT = 60;
    private const TAGLINE_SOFT_MAX = 88;
    private const PRODUCT_PAGE_TAGLINE_SOFT_MAX = 120;
    private const TAGLINE_HARD_MAX = 140;
    private const PRODUCT_PAGE_TAGLINE_HARD_MAX = 160;

    /**
     * Generate two distinct taglines from raw content:
     *   - 'tagline': A short, punchy one-line explanation
     *   - 'product_page_tagline': A slightly fuller one-line explanation
     *
     * Returns an associative array ['tagline' => '...', 'product_page_tagline' => '...'], or null on failure.
     */
    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?array
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            Log::warning('TaglineRewriterService: GROQ_API_KEY is not set.');
            return null;
        }

        // We can generate taglines even if rawDescription is empty, as long as we have page context
        if (empty(trim($rawDescription)) && empty(trim($pageTextContext))) {
            return null;
        }

        // Truncate context to avoid hitting token limits
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
   Good examples:
   - "Run one-person companies with AI agents"
   - "Self-updating knowledge bases"
   - "Orchestrate multi-agent workflows from your desktop"
   Bad examples (too vague or copy-pasted):
   - "The best way to work" (too short and generic)
   - "Your AI companion" (says nothing about what it does)
   - Anything copied word-for-word from the website
2. "product_page_tagline": Aim for 45-110 characters. Hard max 160 characters. It should still be a one-line explanation, just slightly fuller or more specific than the short tagline. It must explain the product, not wander into broad brand messaging.

Respond ONLY with valid JSON in the exact structure below. Do NOT wrap it in markdown blockquotes or add any other text.
{
    "tagline": "...",
    "product_page_tagline": "..."
}
PROMPT;

        try {
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
                    'response_format' => ['type' => 'json_object'], // Enforce JSON response
                ]);

            if (!$response->successful()) {
                Log::warning('TaglineRewriterService: Groq API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (!is_string($content)) {
                return null;
            }

            $decoded = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['tagline']) && isset($decoded['product_page_tagline'])) {
                return $this->normalizeGeneratedTaglines($decoded);
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('TaglineRewriterService: Exception', ['message' => $e->getMessage()]);
            return null;
        }
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
