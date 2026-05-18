<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DescriptionRewriterService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GEMINI_MODEL = 'gemini-2.5-flash';
    private const MODEL = 'llama-3.3-70b-versatile'; // Powerful 70B model
    private const TIMEOUT = 60; // Allow more time for the larger model

    /**
     * Rewrite a raw product description into a structured format:
     *   - 1-sentence headline (bolded)
     *   - 1-sentence elaboration
     *   - Multiple structured sections in HTML
     *
     * Returns HTML, or null on failure.
     */
    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?string
    {
        $googleApiKey = config('services.google.api_key');
        $groqApiKey = config('services.groq.key');

        if (empty($googleApiKey) && empty($groqApiKey)) {
            Log::warning('DescriptionRewriterService: No AI provider key is set.');
            return null;
        }

        if (empty(trim($rawDescription))) {
            return null;
        }

        // Truncate context to avoid hitting token limits
        $context = mb_substr(strip_tags($pageTextContext), 0, 8000); // 70B can handle more context
        $includeAlternatives = $this->shouldIncludeAlternatives($context);
        $includeIntegrations = $this->shouldIncludeIntegrations($context);
        $includeFaq = $this->shouldIncludeFaq($rawDescription, $context);
        $alternativesRules = $includeAlternatives
            ? <<<RULES
- Include the alternatives section because the source material contains grounded comparison signals or named alternatives.
RULES
            : <<<RULES
- Do NOT add an alternatives or comparison section for this product. The source material does not support a grounded comparison without filler.
RULES;
        $alternativesStructure = $includeAlternatives
            ? <<<HTML

<h2><strong>How does {$productName} compare to alternatives?</strong></h2>
<ul>
  <li>[Alternative 1: Name the tool and a brief, grounded reason to choose this product instead.]</li>
  <li>[Alternative 2]</li>
</ul>
HTML
            : '';
        $integrationsRules = $includeIntegrations
            ? <<<RULES
- Include the integrations section because the source material contains grounded ecosystem, API, or integration details.
RULES
            : <<<RULES
- Do NOT add an integrations or ecosystem section for this product. The source material does not support a specific integrations section without filler.
RULES;
        $integrationsStructure = $includeIntegrations
            ? <<<HTML

<h2><strong>What integrations and ecosystem support does {$productName} offer?</strong></h2>
<ul>
  <li>[List integrations, APIs, or platforms it works with, e.g. "Integrates with Slack, Notion, and Zapier."]</li>
</ul>
HTML
            : '';
        $faqRules = $includeFaq
            ? <<<RULES
- Include the FAQ section because the source material appears detailed enough to support at least 2 grounded, non-generic user questions.
- Make the FAQ questions sound like real user search queries.
RULES
            : <<<RULES
- Do NOT add a FAQ section for this product. The source material is not specific enough to support 2 grounded, non-generic FAQs without filler.
RULES;
        $faqStructure = $includeFaq
            ? <<<HTML

<h2><strong>Frequently asked questions about {$productName}</strong></h2>
<dl>
  <dt><strong>[Question 1 written like a real user search, starting with What, How, Who, Does, or Is. Prefer safe factual questions about what the product does, who it is for, how it works, or what workflows it supports.]</strong></dt>
  <dd>[Direct 1-2 sentence answer based only on supported facts from the source material.]</dd>
  <dt><strong>[Question 2 written like a real user search, starting with What, How, Who, Does, or Is. Do NOT ask about pricing, customer support, integrations, or alternatives unless the source material clearly supports those details.]</strong></dt>
  <dd>[Direct 1-2 sentence answer based only on supported facts from the source material.]</dd>
</dl>
HTML
            : '';
        $alternativesStyleCheck = $includeAlternatives
            ? '- Is the alternatives section grounded in explicit comparison signals from the source material?'
            : '- Did you omit the alternatives section entirely instead of inventing weak competitor comparisons?';
        $integrationsStyleCheck = $includeIntegrations
            ? '- Is the integrations section based on explicit APIs, integrations, or ecosystem details from the source material?'
            : '- Did you omit the integrations section entirely instead of padding the page with vague ecosystem language?';
        $faqStyleCheck = $includeFaq
            ? '- Are the FAQ questions and answers limited to facts that are clearly supported?'
            : '- Did you omit the FAQ section entirely instead of padding the page with synthetic questions?';

        $prompt = <<<PROMPT
You are an experienced human writer with 20+ years of experience. Write naturally, clearly, and convincingly. Your job is to write or rewrite the product description for "{$productName}" so it feels genuinely human-written, useful, easy to trust, and easy for AI search engines to extract accurately.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

OBJECTIVE:
- Make the description sound like a real person wrote it.
- Explain what the product does, why it matters, and who it helps.
- Keep the writing grounded, specific, and easy to scan.
- Make the page more citation-friendly for AI Overviews, ChatGPT, Perplexity, and similar engines.
- Help a user decide quickly without overwhelming them with unnecessary detail.

HUMAN WRITING RULES:
- Write like a real person explaining a tool to another person.
- Use simple words, short sentences, and contractions when they feel natural.
- Mix sentence lengths so the copy does not sound robotic.
- Keep a light human touch, but stay controlled and professional.
- Avoid jargon, filler, and generic marketing hype.
- Do not use cliches like "game-changing", "revolutionary", "cutting-edge", or "unleash your potential".
- Be honest. If the source material is limited, stay specific to what is supported and avoid inventing claims.
- You may add at most 1-2 subtle, natural phrases that make the copy feel less mechanical, but do not become chatty.

ANTI-SLOP QUALITY RULES:
- Avoid generic AI-marketing words and phrases such as "cutting-edge", "revolutionary", "seamless", "robust", "comprehensive", "transformative", "game-changing", "in today's landscape", "it's worth noting", and "furthermore".
- Prefer plain language over inflated vocabulary. Say "use" instead of "utilize", "help" instead of "facilitate", and "show" instead of "showcase" when possible.
- Every paragraph and section should contain concrete, product-specific information that would sound wrong if pasted onto a different tool.
- Do not pad sections with filler transitions, generic summaries, or empty closing lines.
- Do not force every list item into the same rhythm or wording pattern. Keep the phrasing natural.
- If a section cannot be made specific with supported facts, keep it short and conservative rather than generic.
- Avoid vague audience labels like "visual storytellers", "teams", "professionals", or "creators" unless the source material clearly uses or supports them.
- Avoid unverified quality claims like "accurate", "reliable", "powerful", or "seamless" unless the source explicitly supports them.
- When mentioning integrations or supported software, name the actual products from the source material instead of vague phrases like "popular editing software".
- Do not repeat the same idea across the intro, features, and audience sections.
- Prefer 3-4 strong specifics over long, generic lists.

AEO / AI SEARCH RULES:
- The first visible paragraph must work as a direct answer snippet that clearly explains what "{$productName}" is, who it is for, and its main benefit.
- The first paragraph should be about 40-60 words so it can be extracted cleanly by AI engines.
- Put the most important factual answer first. Do not bury the core explanation later in the page.
- Use question-based headings because AI engines extract answers more reliably from question-format sections.
{$alternativesRules}
{$integrationsRules}
{$faqRules}
- Prefer concrete entities and attributes when supported by the source material, such as product category, target users, integrations, workflows, pricing model, and notable alternatives.
- Do not invent claims, integrations, pricing details, customer results, or competitor comparisons that are not supported by the provided information.
- If pricing, support, integrations, or alternatives are not clearly supported by the source material, avoid mentioning specific details about them.
- Limitations must be based on explicit source material. Prefer grounded constraints like platform availability, early-access status, manual workflow steps, missing features, or integrations marked "coming soon".
- Do not guess operational drawbacks such as performance limits, compute requirements, scaling issues, or team workflow constraints unless the source clearly states them.

STRUCTURE RULES:
- Preserve the exact HTML structure, section order, headings, and list types shown below for the sections that are included.
- Do not add extra sections beyond the included structure below. Do not rename, merge, or reorder included sections.
- Return ONLY HTML. No markdown fences, no commentary, no labels.
- Keep the first two lines as exactly two separate <p> paragraphs.
- Mention "{$productName}" naturally in the opening paragraph.
- Keep each list item concise, specific, and focused on user value.
- Use question-style H2 headings exactly as shown below.
- Keep the overall description compact and decision-friendly. Do not pad the page just to make it feel comprehensive.

<p><strong>[Write a 40-60 word Quick Answer that directly explains what {$productName} is, who it helps, and why someone would choose it. This entire line MUST be wrapped in <strong> tags and must read like a standalone answer snippet.]</strong></p>
<p>[Write a second sentence that expands on the main workflow, category, or differentiator without hype. Do NOT bold this line.]</p>

<h2><strong>What is {$productName}?</strong></h2>
<p>[Write 2 short sentences in plain English explaining what the product does and how it fits into a user's workflow. Keep it specific and practical.]</p>

<h2><strong>What are the key features of {$productName}?</strong></h2>
<ul>
  <li>[Feature 1: Focus on the BENEFIT, e.g. "Automated workflows that save 10+ hours weekly" rather than "Has automation."]</li>
  <li>[Feature 2: Focus on technical impact or user experience.]</li>
  <li>[Feature 3: Focus on a unique selling point.]</li>
  <li>[Feature 4: Optional. Include only if it adds a distinct supported point.]</li>
</ul>

<h2><strong>Who is {$productName} best for?</strong></h2>
<ul>
  <li>[Specific audience 1, e.g. "Founders scaling their first GTM team"]</li>
  <li>[Specific audience 2]</li>
  <li>[Specific audience 3: Optional. Include only if well supported and distinct.]</li>
</ul>
{$alternativesStructure}
{$integrationsStructure}

<h2><strong>What are the pros and limitations of {$productName}?</strong></h2>
<ul>
  <li><strong>Pros:</strong> [List 2-3 key advantages]</li>
  <li><strong>Limitations:</strong> [List 1-2 honest limitations based only on explicit source facts. Prefer concrete constraints like "macOS only for now", "desktop app ships later", "manual import required in the self-hosted path", or "some integrations are coming soon". If no grounded limitation is supported, say "Not clearly stated in the available source material."]</li>
</ul>
{$faqStructure}

STYLE CHECK BEFORE YOU RESPOND:
- Does it sound human, plainspoken, and useful?
- Does the first paragraph work as a direct answer snippet?
- Is the structure exactly preserved?
- Are the claims grounded in the provided information?
- Would a user understand what the product is and whether it might fit them within 30 seconds?
{$alternativesStyleCheck}
{$integrationsStyleCheck}
{$faqStyleCheck}
- Are the limitations explicitly supported by the source rather than inferred from what similar tools usually struggle with?
- Did you avoid vague audience labels, generic praise, and broad integration wording when the source supports something more specific?
- Does each section include concrete details that are specific to {$productName} rather than generic SaaS filler?
- Is the writing free from obvious AI-style hype and repetition?
PROMPT;

        try {
            if (!empty($googleApiKey)) {
                $response = Http::withHeaders([
                    'X-goog-api-key' => $googleApiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(self::TIMEOUT)->post(self::GEMINI_API_URL, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

                if ($response->successful()) {
                    $content = $response->json('candidates.0.content.parts.0.text');

                    return is_string($content) ? trim($this->stripMarkdownFence($content)) : null;
                }

                Log::warning('DescriptionRewriterService: Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            if (empty($groqApiKey)) {
                return null;
            }

            $response = Http::timeout(self::TIMEOUT)
                ->withToken($groqApiKey)
                ->post(self::GROQ_API_URL, [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.55, // Slightly more natural while keeping the structure stable
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

            return is_string($content) ? trim($this->stripMarkdownFence($content)) : null;

        } catch (\Exception $e) {
            Log::warning('DescriptionRewriterService: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function shouldIncludeFaq(string $rawDescription, string $pageTextContext): bool
    {
        $source = trim($rawDescription . "\n" . $pageTextContext);

        if ($source === '') {
            return false;
        }

        $normalized = mb_strtolower($source);

        if (str_contains($normalized, 'frequently asked questions') || str_contains($normalized, 'faq')) {
            return true;
        }

        $questionHeadingCount = preg_match_all('/(?:^|\n)\s*(?:h[1-3]:\s*)?(what|how|who|does|is|can|why|when)\b/im', $source);
        $hasSupportSignals = $this->containsAny($normalized, [
            'docs',
            'documentation',
            'help center',
            'knowledge base',
            'troubleshooting',
            'getting started',
            'setup',
            'onboarding',
        ]);
        $hasIntegrationSignals = $this->shouldIncludeIntegrations($pageTextContext);
        $hasPricingSignals = $this->containsAny($normalized, [
            'pricing',
            'plans',
            'free trial',
            'enterprise',
            'subscription',
            'billing',
        ]);
        $hasWorkflowSignals = $this->containsAny($normalized, [
            'workflow',
            'workflows',
            'use case',
            'use cases',
        ]);

        return mb_strlen($normalized) >= 600
            && $questionHeadingCount >= 2
            && ($hasSupportSignals || $hasIntegrationSignals || $hasPricingSignals || $hasWorkflowSignals);
    }

    private function shouldIncludeAlternatives(string $pageTextContext): bool
    {
        $normalized = mb_strtolower(trim($pageTextContext));

        if ($normalized === '') {
            return false;
        }

        if (preg_match('/(?:^|\n)\s*h[1-3]:\s*.*\b(compare|comparison|alternative|alternatives|versus|vs\.?)\b/im', $pageTextContext)) {
            return true;
        }

        return $this->containsAny($normalized, [
            'compare',
            'comparison',
            'alternative',
            'alternatives',
            'versus',
            'vs ',
            'unlike ',
            'switch from',
        ]);
    }

    private function shouldIncludeIntegrations(string $pageTextContext): bool
    {
        $normalized = mb_strtolower(trim($pageTextContext));

        if ($normalized === '') {
            return false;
        }

        if (preg_match('/(?:^|\n)\s*h[1-3]:\s*.*\b(integration|integrations|api|apis|sdk|webhook|plugin|extension)\b/im', $pageTextContext)) {
            return true;
        }

        $signals = 0;

        if ($this->containsAny($normalized, [
            'integration',
            'integrations',
            'api',
            'apis',
            'sdk',
            'webhook',
            'webhooks',
            'plugin',
            'plugins',
            'extension',
            'extensions',
        ])) {
            $signals++;
        }

        if ($this->containsAny($normalized, [
            'slack',
            'zapier',
            'notion',
            'github',
            'gitlab',
            'stripe',
            'paypal',
            'hubspot',
            'salesforce',
            'google calendar',
            'zoom',
            'teams',
            'discord',
            'mixpanel',
            'amplitude',
            'firebase',
        ])) {
            $signals++;
        }

        return $signals >= 2;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function stripMarkdownFence(string $content): string
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $trimmed) ?? $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?? $trimmed;
        }

        return trim($trimmed);
    }
}
