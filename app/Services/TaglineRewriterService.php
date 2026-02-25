<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaglineRewriterService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile'; // Powerful 70B model
    private const TIMEOUT = 60;

    /**
     * Generate two distinct taglines from raw content:
     *   - 'tagline': A punchy, 50-character phrase
     *   - 'product_page_tagline': A detailed, 120-character sentence
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
You are an expert SaaS Copywriter. Your goal is to write two distinct, high-converting taglines for "{$productName}" based on its raw description and website context.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

Constraints:
1. "tagline": A maximum of 50 characters. It must be a punchy, engaging phrase that captures the core essence (e.g., "The ultimate task manager for creatives"). Do NOT just use the product name.
2. "product_page_tagline": A maximum of 120 characters. It must be a complete sentence that elaborates on the value proposition and who it's for, without repeating the short tagline verbatim (e.g., "Organize your creative chaos into beautifully structured projects and hit every deadline.").

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
                return [
                    'tagline' => trim($decoded['tagline']),
                    'product_page_tagline' => trim($decoded['product_page_tagline']),
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('TaglineRewriterService: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
