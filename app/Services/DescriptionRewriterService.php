<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DescriptionRewriterService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 60;

    public function rewrite(string $productName, string $rawDescription, string $pageTextContext = ''): ?string
    {
        $productName = $this->normalizeProductName($productName);
        $googleApiKey = config('services.google.api_key');
        $groqApiKey = config('services.groq.key');

        if (empty($googleApiKey) && empty($groqApiKey)) {
            Log::warning('DescriptionRewriterService: No AI provider key is set.');
            return null;
        }

        if (empty(trim($rawDescription))) {
            return null;
        }

        $context = mb_substr(strip_tags($pageTextContext), 0, 8000);
        $options = [
            'include_alternatives' => $this->shouldIncludeAlternatives($context),
            'include_integrations' => $this->shouldIncludeIntegrations($context),
            'include_faq' => $this->shouldIncludeFaq($rawDescription, $context),
        ];
        $productPattern = $this->detectProductPattern($rawDescription, $context);

        $prompt = $this->buildPrompt($productName, $rawDescription, $context, $options, $productPattern);

        try {
            $rawResponse = null;
            $provider = null;

            if (!empty($googleApiKey)) {
                $rawResponse = $this->generateWithGemini($googleApiKey, $prompt);
                $provider = $rawResponse !== null ? 'gemini' : null;
            }

            if ($rawResponse === null && !empty($groqApiKey)) {
                $rawResponse = $this->generateWithGroq($groqApiKey, $prompt);
                $provider = $rawResponse !== null ? 'groq' : null;
            }

            if (!is_string($rawResponse) || trim($rawResponse) === '') {
                return null;
            }

            $payload = $this->decodeStructuredResponse($rawResponse);

            if ($payload === null) {
                Log::warning('DescriptionRewriterService: Failed to decode structured response.', [
                    'response' => $rawResponse,
                ]);

                return null;
            }

            $payload = $this->refineEditorialFieldsIfNeeded(
                $payload,
                $productName,
                $rawDescription,
                $context,
                $productPattern,
                $provider,
                $googleApiKey,
                $groqApiKey
            );

            $payload = $this->repairLowQualityEditorialFields(
                $payload,
                $productName,
                $rawDescription,
                $context,
                $productPattern,
                $provider,
                $googleApiKey,
                $groqApiKey
            );

            return $this->renderHtml($productName, $payload, $options);
        } catch (\Exception $e) {
            Log::warning('DescriptionRewriterService: Exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function buildPrompt(string $productName, string $rawDescription, string $context, array $options, ?string $productPattern): string
    {
        $optionalRules = [];
        $optionalRules[] = $options['include_alternatives']
            ? '- Fill the `alternatives` array with up to 2 grounded comparison bullets.'
            : '- Return `alternatives` as an empty array.';
        $optionalRules[] = $options['include_integrations']
            ? '- Fill the `integrations` array with specific integrations, APIs, or supported platforms.'
            : '- Return `integrations` as an empty array.';
        $optionalRules[] = $options['include_faq']
            ? '- Fill the `faq` array with up to 2 grounded question/answer pairs.'
            : '- Return `faq` as an empty array.';

        $optionalRulesText = implode("\n", $optionalRules);
        $patternGuidance = $this->buildProductPatternGuidance($productPattern);

        return <<<PROMPT
You are an experienced human writer with 20+ years of experience. Write naturally, clearly, and convincingly. Your job is to rewrite the product description for "{$productName}" so it feels human-written, useful, easy to trust, and easy for AI search engines to extract accurately.

Raw information: "{$rawDescription}"

Additional context: "{$context}"

OBJECTIVE:
- Explain what the product does, who it helps, and why someone would choose it.
- Keep the writing grounded, specific, compact, and easy to scan.
- Help a user decide quickly without overwhelming them with unnecessary detail.
{$patternGuidance}

WRITING RULES:
- Write like a calm editor, not a landing-page marketer.
- Use plain language and short sentences when possible.
- Avoid hype, filler, generic marketing claims, and repeated ideas.
- Avoid vague audience labels like "professionals", "creators", or "teams" unless the source clearly supports them.
- Avoid ad-like words and labels such as "AI-powered", "polished", "professional-looking", "instant", "instantly", "complimentary", "powerful", or "seamless" unless the source explicitly uses and supports them.
- Avoid generic phrases like "online presence", "quickly and easily", "simple online presence", "professional look", or "saves time and effort" unless the source explicitly supports them.
- Name actual supported platforms or integrations when the source provides them.
- Use normal capitalization. Do not write in all lowercase.
- Mention supported source platforms at most once in the summary and once in the body. Do not repeat the same platform list in multiple bullets.
- Make `key_features` about workflows, capabilities, or user value, not one bullet per source website.
- Make `pros` specific to the product's workflow or value. Do not use vague praise.
- Do not invent claims, limitations, pricing details, integrations, customer outcomes, or competitor comparisons.
- Limitations must be grounded in explicit source facts. If there are no clear limitations, say so plainly.
- If limitations are unclear, return exactly: "Not clearly stated in the available source material."
- Do not turn supported input types into a guessed limitation such as "limited to supported platforms" unless the source explicitly states that constraint.

OUTPUT RULES:
- Return ONLY valid JSON.
- Do not return HTML.
- Do not return Markdown.
- Do not wrap the JSON in code fences.
- Keep arrays concise and specific.
- Use sentence case in all string values.
- Keep `summary` to roughly 40-60 words.
- Keep `supporting_sentence` to 1 sentence.
- Keep `what_it_is` to 2 short sentences maximum.
- Keep `key_features` to 3-4 bullets.
- Keep `best_for` to 2-3 bullets.
- Keep `pros` to 2-3 bullets.
- Keep `limitations` to 1-2 bullets.
{$optionalRulesText}

JSON SHAPE:
{
  "summary": "string",
  "supporting_sentence": "string",
  "what_it_is": "string",
  "key_features": ["string"],
  "best_for": ["string"],
  "pros": ["string"],
  "limitations": ["string"],
  "alternatives": ["string"],
  "integrations": ["string"],
  "faq": [
    {
      "question": "string",
      "answer": "string"
    }
  ]
}
PROMPT;
    }

    private function buildFieldRepairPrompt(
        string $field,
        string $productName,
        string $rawDescription,
        string $context,
        ?string $productPattern,
        mixed $currentValue,
        array $issues
    ): string {
        $encodedCurrentValue = json_encode($currentValue, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $issueText = implode('; ', $issues);
        $patternGuidance = $this->buildProductPatternGuidance($productPattern, true);

        if ($field === 'pros') {
            return <<<PROMPT
You are fixing the `pros` field in a structured product description for "{$productName}".

SOURCE FACTS:
- Raw information: "{$rawDescription}"
- Additional context: "{$context}"

CURRENT VALUE:
{$encodedCurrentValue}

PROBLEMS TO FIX:
- {$issueText}

TASK:
- Return a better `pros` array with 2-3 concise bullets.
- Each bullet must describe concrete workflow value or product value.
- Do not repeat platform lists unless truly necessary.
- Do not use vague bullets like "supports multiple platforms", "easy to use", or "builds a website in seconds".
- Do not add unsupported claims.
{$patternGuidance}

RETURN ONLY VALID JSON:
{
  "pros": ["string"]
}
PROMPT;
        }

        return <<<PROMPT
You are fixing the `{$field}` field in a structured product description for "{$productName}".

SOURCE FACTS:
- Raw information: "{$rawDescription}"
- Additional context: "{$context}"

CURRENT VALUE:
{$encodedCurrentValue}

PROBLEMS TO FIX:
- {$issueText}

TASK:
- Rewrite only `{$field}`.
- Keep the wording specific, calm, and editorial.
- Avoid phrases like "online presence", "in seconds", "various platforms", or repeated platform lists.
- Do not add unsupported claims.
- Do not repeat the same platform list already used elsewhere in the description.
- Keep `summary` to roughly 40-60 words.
- Keep `what_it_is` to 2 short sentences maximum.
{$patternGuidance}

RETURN ONLY VALID JSON:
{
  "{$field}": "string"
}
PROMPT;
    }

    private function buildFieldRefinementPrompt(string $productName, string $rawDescription, string $context, ?string $productPattern, array $payload): string
    {
        $currentFields = json_encode([
            'summary' => (string) ($payload['summary'] ?? ''),
            'what_it_is' => (string) ($payload['what_it_is'] ?? ''),
            'pros' => array_values(array_filter($payload['pros'] ?? [], 'is_string')),
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $patternGuidance = $this->buildProductPatternGuidance($productPattern, true);

        return <<<PROMPT
You are editing three fields in a structured product description for "{$productName}".

SOURCE FACTS:
- Raw information: "{$rawDescription}"
- Additional context: "{$context}"

CURRENT FIELDS:
{$currentFields}

TASK:
- Rewrite only `summary`, `what_it_is`, and `pros`.
- Keep every claim grounded in the source facts above.
- Make the wording more specific, calm, and editorial.
- Remove generic wording, filler, and repeated ideas.
- Mention supported platforms at most once across these edited fields.
- Do not repeat the same platform list in both `summary` and `what_it_is`.
- Do not add pricing, comparisons, integrations, or limitations that are not clearly supported.
- Keep `summary` to roughly 40-60 words.
- Keep `what_it_is` to 2 short sentences maximum.
- Keep `pros` to 2-3 concise bullets focused on concrete user value or workflow value.
- Do not use hype words like "polished", "powerful", "seamless", "instant", or "professional-looking".
- Do not use generic phrases like "online presence", "saves time and effort", or "quick and easy".
{$patternGuidance}

RETURN ONLY VALID JSON:
{
  "summary": "string",
  "what_it_is": "string",
  "pros": ["string"]
}
PROMPT;
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
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($response->successful()) {
            $content = $response->json('candidates.0.content.parts.0.text');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Gemini API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    private function generateWithProvider(?string $provider, string $prompt, ?string $googleApiKey, ?string $groqApiKey): ?string
    {
        if ($provider === 'gemini' && !empty($googleApiKey)) {
            return $this->generateWithGemini($googleApiKey, $prompt);
        }

        if ($provider === 'groq' && !empty($groqApiKey)) {
            return $this->generateWithGroq($groqApiKey, $prompt);
        }

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
                'temperature' => 0.55,
                'max_tokens' => 1200,
            ]);

        if ($response->successful()) {
            $content = $response->json('choices.0.message.content');

            return is_string($content) ? $content : null;
        }

        Log::warning('DescriptionRewriterService: Groq API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    private function decodeStructuredResponse(string $content): ?array
    {
        $cleaned = $this->stripMarkdownFence($content);
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

    private function renderHtml(string $productName, array $payload, array $options): ?string
    {
        $summary = $this->cleanSentence($payload['summary'] ?? '');
        $supportingSentence = $this->cleanSentence($payload['supporting_sentence'] ?? '');
        $whatItIs = $this->cleanParagraph($payload['what_it_is'] ?? '');
        $keyFeatures = $this->cleanStringArray($payload['key_features'] ?? [], 4);
        $bestFor = $this->cleanStringArray($payload['best_for'] ?? [], 3);
        $pros = $this->cleanStringArray($payload['pros'] ?? [], 3);
        $limitations = $this->cleanLimitationsArray($payload['limitations'] ?? [], 2);
        $alternatives = $options['include_alternatives'] ? $this->cleanStringArray($payload['alternatives'] ?? [], 2) : [];
        $integrations = $options['include_integrations'] ? $this->cleanStringArray($payload['integrations'] ?? [], 3) : [];
        $faq = $options['include_faq'] ? $this->cleanFaqArray($payload['faq'] ?? [], 2) : [];

        if ($summary === '' || $supportingSentence === '' || $whatItIs === '' || $keyFeatures === [] || $bestFor === [] || $pros === [] || $limitations === []) {
            return null;
        }

        $html = [];
        $html[] = '<p><strong>' . e($summary) . '</strong></p>';
        $html[] = '<p>' . e($supportingSentence) . '</p>';
        $html[] = '<h2><strong>What is ' . e($productName) . '?</strong></h2>';
        $html[] = '<p>' . e($whatItIs) . '</p>';
        $html[] = '<h2><strong>What are the key features of ' . e($productName) . '?</strong></h2>';
        $html[] = $this->renderList($keyFeatures);
        $html[] = '<h2><strong>Who is ' . e($productName) . ' best for?</strong></h2>';
        $html[] = $this->renderList($bestFor);

        if ($alternatives !== []) {
            $html[] = '<h2><strong>How does ' . e($productName) . ' compare to alternatives?</strong></h2>';
            $html[] = $this->renderList($alternatives);
        }

        if ($integrations !== []) {
            $html[] = '<h2><strong>What integrations and ecosystem support does ' . e($productName) . ' offer?</strong></h2>';
            $html[] = $this->renderList($integrations);
        }

        $html[] = '<h2><strong>What are the pros and limitations of ' . e($productName) . '?</strong></h2>';
        $html[] = '<ul>'
            . '<li><strong>Pros:</strong> ' . e(implode('; ', $pros)) . '</li>'
            . '<li><strong>Limitations:</strong> ' . e(implode('; ', $limitations)) . '</li>'
            . '</ul>';

        if ($faq !== []) {
            $html[] = '<h2><strong>Frequently asked questions about ' . e($productName) . '</strong></h2>';
            $html[] = $this->renderFaq($faq);
        }

        return implode("\n", $html);
    }

    private function renderList(array $items): string
    {
        $html = '<ul>';

        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }

        return $html . '</ul>';
    }

    private function renderFaq(array $items): string
    {
        $html = '<dl>';

        foreach ($items as $item) {
            $html .= '<dt><strong>' . e($item['question']) . '</strong></dt>';
            $html .= '<dd>' . e($item['answer']) . '</dd>';
        }

        return $html . '</dl>';
    }

    private function cleanStringArray(array $items, int $limit): array
    {
        $cleaned = [];

        foreach ($items as $item) {
            if (!is_string($item)) {
                continue;
            }

            $value = $this->cleanSentence($item);

            if ($value === '') {
                continue;
            }

            $cleaned[] = $value;

            if (count($cleaned) >= $limit) {
                break;
            }
        }

        return array_values(array_unique($cleaned));
    }

    private function cleanLimitationsArray(array $items, int $limit): array
    {
        $cleaned = $this->cleanStringArray($items, $limit);

        if ($cleaned === []) {
            return [];
        }

        $normalized = [];

        foreach ($cleaned as $item) {
            $lower = mb_strtolower($item);

            if (
                str_contains($lower, 'not clearly stated') ||
                str_contains($lower, 'no clear limitations') ||
                str_contains($lower, 'not mentioned in the source') ||
                str_contains($lower, 'not mentioned in the available source') ||
                str_contains($lower, 'no clear limitations mentioned')
            ) {
                return ['Not clearly stated in the available source material.'];
            }

            if (
                (str_contains($lower, 'supported platform') || str_contains($lower, 'specific platform') || str_contains($lower, 'links from'))
                && !str_contains($lower, 'source')
                && !str_contains($lower, 'available')
                && !str_contains($lower, 'coming soon')
                && !str_contains($lower, 'currently')
            ) {
                return ['Not clearly stated in the available source material.'];
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private function cleanFaqArray(array $items, int $limit): array
    {
        $cleaned = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = $this->cleanSentence((string) ($item['question'] ?? ''));
            $answer = $this->cleanSentence((string) ($item['answer'] ?? ''));

            if ($question === '' || $answer === '') {
                continue;
            }

            $cleaned[] = [
                'question' => $question,
                'answer' => $answer,
            ];

            if (count($cleaned) >= $limit) {
                break;
            }
        }

        return $cleaned;
    }

    private function refineEditorialFieldsIfNeeded(
        array $payload,
        string $productName,
        string $rawDescription,
        string $context,
        ?string $productPattern,
        ?string $provider,
        ?string $googleApiKey,
        ?string $groqApiKey
    ): array {
        if (!$this->shouldRefineEditorialFields($payload)) {
            return $payload;
        }

        $prompt = $this->buildFieldRefinementPrompt($productName, $rawDescription, $context, $productPattern, $payload);
        $response = $this->generateWithProvider($provider, $prompt, $googleApiKey, $groqApiKey);

        if (!is_string($response) || trim($response) === '') {
            return $payload;
        }

        $refined = $this->decodeStructuredResponse($response);

        if (!is_array($refined)) {
            return $payload;
        }

        if (is_string($refined['summary'] ?? null) && trim($refined['summary']) !== '') {
            $payload['summary'] = $refined['summary'];
        }

        if (is_string($refined['what_it_is'] ?? null) && trim($refined['what_it_is']) !== '') {
            $payload['what_it_is'] = $refined['what_it_is'];
        }

        if (is_array($refined['pros'] ?? null) && $refined['pros'] !== []) {
            $payload['pros'] = $refined['pros'];
        }

        return $payload;
    }

    private function repairLowQualityEditorialFields(
        array $payload,
        string $productName,
        string $rawDescription,
        string $context,
        ?string $productPattern,
        ?string $provider,
        ?string $googleApiKey,
        ?string $groqApiKey
    ): array {
        $fieldsToCheck = ['summary', 'what_it_is', 'pros'];

        foreach ($fieldsToCheck as $field) {
            $issues = $this->getEditorialQualityIssues($field, $payload, $productPattern);

            if ($issues === []) {
                continue;
            }

            $prompt = $this->buildFieldRepairPrompt(
                $field,
                $productName,
                $rawDescription,
                $context,
                $productPattern,
                $payload[$field] ?? ($field === 'pros' ? [] : ''),
                $issues
            );

            $response = $this->generateWithProvider($provider, $prompt, $googleApiKey, $groqApiKey);

            if (!is_string($response) || trim($response) === '') {
                continue;
            }

            $repaired = $this->decodeStructuredResponse($response);

            if (!is_array($repaired)) {
                continue;
            }

            if ($field === 'pros') {
                $candidate = $this->cleanStringArray($repaired['pros'] ?? [], 3);

                if ($candidate !== [] && $this->getEditorialQualityIssues('pros', ['pros' => $candidate] + $payload, $productPattern) === []) {
                    $payload['pros'] = $candidate;
                }

                continue;
            }

            if (!is_string($repaired[$field] ?? null)) {
                continue;
            }

            $candidate = $field === 'summary'
                ? $this->cleanSentence($repaired[$field])
                : $this->cleanParagraph($repaired[$field]);

            if ($candidate === '') {
                continue;
            }

            $candidatePayload = $payload;
            $candidatePayload[$field] = $candidate;

            if ($this->getEditorialQualityIssues($field, $candidatePayload, $productPattern) === []) {
                $payload[$field] = $candidate;
            }
        }

        return $payload;
    }

    private function shouldRefineEditorialFields(array $payload): bool
    {
        $summary = mb_strtolower((string) ($payload['summary'] ?? ''));
        $whatItIs = mb_strtolower((string) ($payload['what_it_is'] ?? ''));
        $pros = array_map(static fn ($item) => mb_strtolower((string) $item), $payload['pros'] ?? []);

        $combined = trim($summary . ' ' . $whatItIs . ' ' . implode(' ', $pros));

        if ($combined === '') {
            return false;
        }

        $weakPhrases = [
            'online presence',
            'saves time and effort',
            'quick and easy',
            'professional-looking',
            'popular platform',
            'popular platforms',
            'in seconds',
            'quickly',
            'easy to use',
            'simple website',
            'basic website',
            'polished website',
            'individuals',
            'professionals',
        ];

        if ($this->containsAny($combined, $weakPhrases)) {
            return true;
        }

        return $this->countNamedPlatformMentions($combined) > 6;
    }

    private function getEditorialQualityIssues(string $field, array $payload, ?string $productPattern = null): array
    {
        $issues = [];
        $value = $payload[$field] ?? ($field === 'pros' ? [] : '');

        $text = is_array($value) ? implode(' ', array_map('strval', $value)) : (string) $value;
        $normalized = mb_strtolower($text);

        if ($normalized === '') {
            return [];
        }

        if (str_contains($normalized, 'online presence')) {
            $issues[] = 'Uses the generic phrase "online presence".';
        }

        if (
            preg_match('/\bin seconds\b/i', $text)
            || preg_match('/\bvarious platforms\b/i', $text)
            || preg_match('/\bpopular platforms\b/i', $text)
            || preg_match('/\bsupports multiple platforms\b/i', $text)
        ) {
            $issues[] = 'Uses generic wording instead of specific product value.';
        }

        if ($field === 'pros') {
            if (
                preg_match('/\beasy to use\b/i', $text)
                || preg_match('/\bno design experience\b/i', $text)
                || preg_match('/\bno design skills\b/i', $text)
                || preg_match('/\bfree preview is available\b/i', $text)
            ) {
                $issues[] = 'Pros are too generic or read like filler.';
            }
        }

        if ($field === 'what_it_is' && preg_match('/\bsupports links from\b/i', $text)) {
            $issues[] = 'Explains supported links instead of the product workflow.';
        }

        if ($productPattern === 'link_to_website') {
            if (
                preg_match('/\bprofessional layout\b/i', $text)
                || preg_match('/\bsocial media presence\b/i', $text)
                || preg_match('/\bwebsite quickly\b/i', $text)
            ) {
                $issues[] = 'Uses generic link-to-website wording instead of the source-to-site transformation.';
            }

            if (
                $field === 'summary'
                && preg_match('/\bgoogle maps link\b/i', $text)
                && !preg_match('/\b(listing|profile|review|business information|business details)\b/i', $text)
            ) {
                $issues[] = 'Over-focuses on one link type instead of the broader source material.';
            }
        }

        $summaryMentions = $this->countNamedPlatformMentions((string) ($payload['summary'] ?? ''));
        $whatItIsMentions = $this->countNamedPlatformMentions((string) ($payload['what_it_is'] ?? ''));

        if (
            $field === 'summary'
            && $summaryMentions >= 3
            && $whatItIsMentions >= 2
        ) {
            $issues[] = 'Repeats the same platform list too heavily.';
        }

        if (
            $field === 'what_it_is'
            && $whatItIsMentions >= 2
            && $summaryMentions >= 3
        ) {
            $issues[] = 'Repeats the same platform list too heavily.';
        }

        return array_values(array_unique($issues));
    }

    private function detectProductPattern(string $rawDescription, string $context): ?string
    {
        $normalized = mb_strtolower(trim($rawDescription . "\n" . $context));

        if ($normalized === '') {
            return null;
        }

        $hasWebsiteSignals = $this->containsAny($normalized, [
            'website',
            'websites',
            'site',
            'sites',
        ]);

        $hasSourceSignals = $this->containsAny($normalized, [
            'paste a link',
            'paste your link',
            'from a link',
            'existing links',
            'google maps',
            'tripadvisor',
            'facebook',
            'instagram',
            'linkedin',
            'reviews',
            'review links',
            'social media',
            'business listing',
            'business listings',
            'profiles',
            'screenshots',
        ]);

        $hasTransformationSignals = $this->containsAny($normalized, [
            'turns links into',
            'transforms links into',
            'create a website from',
            'generate a website from',
            'build a website from',
            'uses existing content',
        ]);

        if ($hasWebsiteSignals && $hasSourceSignals && $hasTransformationSignals) {
            return 'link_to_website';
        }

        return null;
    }

    private function buildProductPatternGuidance(?string $productPattern, bool $compact = false): string
    {
        if ($productPattern !== 'link_to_website') {
            return '';
        }

        $lines = [
            '- This product appears to turn existing listings, profiles, reviews, or links into a website.',
            '- Describe the transformation from existing business content to a ready-to-use site.',
            '- Prefer phrases like "existing listings and profiles", "reviews and business details", or "source content the user already maintains".',
            '- Avoid framing it as a generic "online presence" tool.',
            '- Avoid over-focusing on one source like Google Maps when the product supports several source types.',
            '- In `pros`, emphasize reusing current content, previewing before publish, and reducing manual site setup.',
        ];

        if ($compact) {
            return "\nPRODUCT-TYPE GUIDANCE:\n" . implode("\n", $lines);
        }

        return "\nPRODUCT-TYPE GUIDANCE:\n" . implode("\n", $lines);
    }

    private function cleanParagraph(string $text): string
    {
        $text = $this->normalizeText($text);
        $text = $this->normalizeSentenceCase($text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function cleanSentence(string $text): string
    {
        $text = $this->normalizeText($text);
        $text = $this->normalizeSentenceCase($text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        return trim($text, " \t\n\r\0\x0B;");
    }

    private function normalizeText(string $text): string
    {
        $text = trim(strip_tags($this->stripMarkdownFence($text)));
        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text) ?? $text;
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\binstant(?:ly)?\b/i', 'quickly', $text) ?? $text;
        $text = preg_replace('/\bin moments\b/i', 'quickly', $text) ?? $text;
        $text = preg_replace('/\balmost instantly\b/i', 'quickly', $text) ?? $text;
        $text = preg_replace('/\bAI-powered\b/i', 'AI', $text) ?? $text;
        $text = preg_replace('/\btraditional web design\b/i', 'building a site from scratch', $text) ?? $text;
        $text = preg_replace('/\bprofessional online presence\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bfunctional web presence\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bfull web presence\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bbasic website\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bsimple website\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bpolished website\b/i', 'website', $text) ?? $text;
        $text = preg_replace('/\bquick and easy to use\b/i', 'easy to use', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = $this->normalizeKnownTerms($text);

        return trim($text);
    }

    private function countNamedPlatformMentions(string $text): int
    {
        $count = 0;

        foreach ([
            'google maps',
            'tripadvisor',
            'facebook',
            'instagram',
            'linkedin',
            'github',
            'slack',
            'zapier',
            'notion',
        ] as $platform) {
            preg_match_all('/\b' . preg_quote($platform, '/') . '\b/i', $text, $matches);
            $count += count($matches[0] ?? []);
        }

        return $count;
    }

    private function normalizeSentenceCase(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = preg_replace_callback('/(^|[.!?]\s+)([a-z])/', function (array $matches) {
            return $matches[1] . mb_strtoupper($matches[2]);
        }, $text) ?? $text;

        return ucfirst($text);
    }

    private function normalizeKnownTerms(string $text): string
    {
        $patterns = [
            '/\bgoogle maps\b/i' => 'Google Maps',
            '/\btripadvisor\b/i' => 'TripAdvisor',
            '/\bfacebook\b/i' => 'Facebook',
            '/\binstagram\b/i' => 'Instagram',
            '/\blinkedin\b/i' => 'LinkedIn',
            '/\bgithub\b/i' => 'GitHub',
            '/\bslack\b/i' => 'Slack',
            '/\bzapier\b/i' => 'Zapier',
            '/\bnotion\b/i' => 'Notion',
            '/\bapis\b/i' => 'APIs',
            '/\bapi\b/i' => 'API',
            '/\bai\b/i' => 'AI',
            '/\bsaas\b/i' => 'SaaS',
            '/\bwowable\b/i' => 'Wowable',
            '/\bchangelogfy\b/i' => 'Changelogfy',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }

    private function normalizeProductName(string $productName): string
    {
        $productName = trim($productName);

        if ($productName === '') {
            return 'this product';
        }

        if (str_contains($productName, '|')) {
            $parts = array_values(array_filter(array_map('trim', explode('|', $productName))));
            $lastPart = end($parts);

            if (is_string($lastPart) && $lastPart !== '' && str_word_count($lastPart) <= 4) {
                $productName = $lastPart;
            }
        }

        return $this->normalizeKnownTerms($this->normalizeSentenceCase($productName));
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
            'google maps',
            'tripadvisor',
            'facebook',
            'instagram',
            'linkedin',
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
