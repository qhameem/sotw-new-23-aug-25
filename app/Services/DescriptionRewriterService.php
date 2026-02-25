<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DescriptionRewriterService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile'; // Powerful 70B model
    private const TIMEOUT = 60; // Allow more time for the larger model

    /**
     * Rewrite a raw product description into a structured format:
     *   - 1-sentence headline (bolded)
     *   - 1-sentence elaboration
     *   - Key Features (impact-driven bullet list)
     *   - Ideal For (bullet list)
     *
     * Returns plain text with markdown-style formatting, or null on failure.
     */
    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?string
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            Log::warning('DescriptionRewriterService: GROQ_API_KEY is not set.');
            return null;
        }

        if (empty(trim($rawDescription))) {
            return null;
        }

        // Truncate context to avoid hitting token limits
        $context = mb_substr(strip_tags($pageTextContext), 0, 8000); // 70B can handle more context

        $prompt = <<<PROMPT
You are a Senior Product Copywriter and Marketing Specialist. Your goal is to transform a raw product description for "{$productName}" into a compelling, professional, and benefit-driven narrative. Focus on the *impact* and *value* the product provides to users.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

Respond ONLY with the structured description in this exact HTML format — no preamble, no meta-commentary:

<p><strong>[Write a single, punchy headline that captures the core value proposition. This entire line MUST be wrapped in <strong> tags.]</strong></p>
<p>[Write a second sentence that elaborates on how the product solves a main pain point. Do NOT bold this line.]</p>

<h2><strong>Key Features</strong></h2>
<ul>
  <li>[Feature 1: Focus on the BENEFIT, e.g. "Automated workflows that save 10+ hours weekly" rather than "Has automation."]</li>
  <li>[Feature 2: Focus on technical impact or user experience.]</li>
  <li>[Feature 3: Focus on a unique selling point.]</li>
  <li>[Feature 4: Impact-driven feature.]</li>
  <li>[Feature 5: Impact-driven feature.]</li>
</ul>

<h2><strong>Ideal For</strong></h2>
<ul>
  <li>[Specific audience 1, e.g. "Founders scaling their first GTM team"]</li>
  <li>[Specific audience 2]</li>
  <li>[Specific audience 3]</li>
</ul>

Tone: Professional, persuasive, and authoritative. Avoid generic marketing fluff like "game-changing" or "innovative" unless backed by specific facts.
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
                    'temperature' => 0.4, // Slightly creative but mostly factual
                    'max_tokens' => 400,
                ]);

            if (!$response->successful()) {
                Log::warning('DescriptionRewriterService: Groq API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');

            return is_string($content) ? trim($content) : null;

        } catch (\Exception $e) {
            Log::warning('DescriptionRewriterService: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
