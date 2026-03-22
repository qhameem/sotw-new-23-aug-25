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

<h2><strong>Top Use Cases</strong></h2>
<ul>
  <li>[Specific "Problem -> Solution" use case 1, e.g. "Automating invoice data entry for accounting teams"]</li>
  <li>[Specific "Problem -> Solution" use case 2]</li>
  <li>[Specific "Problem -> Solution" use case 3]</li>
</ul>

<h2><strong>Known Alternatives</strong></h2>
<ul>
  <li>[Alternative 1: Name the tool and a brief reason to choose this product instead, e.g. "A lightweight, privacy-focused alternative to Google Analytics."]</li>
  <li>[Alternative 2]</li>
</ul>

<h2><strong>Integrations & Ecosystem</strong></h2>
<ul>
  <li>[List integrations, APIs, or platforms it works with, e.g. "Integrates seamlessly with Slack, Notion, and Zapier."]</li>
</ul>

<h2><strong>Pros & Cons</strong></h2>
<ul>
  <li><strong>Pros:</strong> [List 2-3 key advantages]</li>
  <li><strong>Limitations:</strong> [List 1-2 honest limitations, e.g. "Not ideal for enterprise-level teams requiring custom SLAs."]</li>
</ul>

<h2><strong>Frequently Asked Questions</strong></h2>
<dl>
  <dt><strong>[Common question 1 about the product?]</strong></dt>
  <dd>[Direct 1-2 sentence answer.]</dd>
  <dt><strong>[Common question 2 about the product?]</strong></dt>
  <dd>[Direct 1-2 sentence answer.]</dd>
</dl>

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
                    'max_tokens' => 1200,
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
