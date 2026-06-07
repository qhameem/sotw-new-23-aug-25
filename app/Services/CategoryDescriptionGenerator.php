<?php

namespace App\Services;

use App\Models\Category;
use App\Support\CategoryTypeRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryDescriptionGenerator
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 30;
    private const TEMPERATURE = 0.75;
    private const MAX_ATTEMPTS = 3;

    /**
     * Generate SEO description and meta description for a category.
     *
     * Returns an array with 'description' and 'meta_description' or null on failure.
     */
    public function generate(string $categoryName, array $runtimeContext = []): ?array
    {
        $providerRouter = app(AiProviderRoutingService::class);
        $candidates = $providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']);

        if ($candidates === []) {
            Log::warning('CategoryDescriptionGenerator: No AI provider key is set.');
            return null;
        }

        if (empty(trim($categoryName))) {
            return null;
        }

        try {
            $lastFailureReason = 'unknown';
            $bestResult = null;
            $context = $this->buildCategoryContext($categoryName, $runtimeContext);

            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                $result = null;

                foreach ($providerRouter->orderedConfiguredProviders(['groq', 'gemini', 'openrouter']) as $candidate) {
                    $result = $this->requestCategoryCopy($candidate['provider'], $candidate['key'], $categoryName, $context, $attempt);

                    if ($result !== null) {
                        break;
                    }
                }

                if ($result === null) {
                    $lastFailureReason = 'request_failed';
                    continue;
                }

                $result = $this->normalizeResult($result);

                if (!$this->hasValidMetaLength($result['meta_description'])) {
                    $result['meta_description'] = $this->repairMetaLength($categoryName, $result['meta_description'], $context);
                    $lastFailureReason = 'invalid_meta_length';
                }

                if ($this->contentSoundsOverTemplated($categoryName, $result['description'], $result['meta_description'])) {
                    $lastFailureReason = 'content_too_templated';
                    Log::info('CategoryDescriptionGenerator: Retrying because generated copy sounds too templated.', [
                        'category' => $categoryName,
                        'attempt' => $attempt,
                    ]);
                    continue;
                }

                if ($bestResult === null && !empty($result['description']) && $this->hasValidMetaLength($result['meta_description'])) {
                    $bestResult = $result;
                }

                if ($this->contentSoundsTooSimilar($categoryName, $result['description'], $result['meta_description'])) {
                    $lastFailureReason = 'content_too_similar';
                    Log::info('CategoryDescriptionGenerator: Retrying because description and meta description are too similar.', [
                        'category' => $categoryName,
                        'attempt' => $attempt,
                    ]);
                    continue;
                }

                return $result;
            }

            if ($bestResult !== null) {
                if ($this->contentSoundsTooSimilar($categoryName, $bestResult['description'], $bestResult['meta_description'])
                    || $this->contentSoundsOverTemplated($categoryName, $bestResult['description'], $bestResult['meta_description'])) {
                    $bestResult['meta_description'] = $this->buildFallbackMetaDescription($categoryName, $context);
                }

                Log::info('CategoryDescriptionGenerator: Returning repaired fallback result after retries.', [
                    'category' => $categoryName,
                    'reason' => $lastFailureReason,
                ]);

                return $bestResult;
            }

            Log::warning('CategoryDescriptionGenerator: Unable to generate distinct category SEO copy.', [
                'category' => $categoryName,
                'reason' => $lastFailureReason,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::warning('CategoryDescriptionGenerator: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    private function requestCategoryCopy(string $provider, string $apiKey, string $categoryName, array $context, int $attempt): ?array
    {
        $prompt = $this->buildPrompt($categoryName, $context, $attempt);
        $response = match ($provider) {
            'gemini' => Http::withHeaders([
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
            ]),
            'openrouter' => Http::timeout(self::TIMEOUT)->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-OpenRouter-Title' => config('app.name'),
            ])->post(self::OPENROUTER_API_URL, [
                'model' => (string) config('services.openrouter.model', 'openrouter/auto'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => self::TEMPERATURE,
            ]),
            default => Http::timeout(self::TIMEOUT)->withToken($apiKey)->post(self::GROQ_API_URL, [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => self::TEMPERATURE,
                'response_format' => ['type' => 'json_object'],
            ]),
        };

        if (!$response->successful()) {
            Log::warning('CategoryDescriptionGenerator: Provider API error', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
                'attempt' => $attempt,
            ]);
            app(AiProviderRoutingService::class)->recordHttpFailure($provider, $response);

            return null;
        }

        app(AiProviderRoutingService::class)->recordHttpSuccess($provider, $response);
        $content = $provider === 'gemini'
            ? $response->json('candidates.0.content.parts.0.text')
            : $response->json('choices.0.message.content');

        if (!is_string($content)) {
            return null;
        }

        $data = $this->decodeJsonText($content);

        if (!isset($data['description'], $data['meta_description'])) {
            return null;
        }

        return [
            'description' => trim((string) $data['description']),
            'meta_description' => trim((string) $data['meta_description']),
        ];
    }

    private function decodeJsonText(string $content): ?array
    {
        $cleaned = trim(str_replace(['```json', '```'], '', $content));
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

    private function normalizeResult(array $result): array
    {
        return [
            'description' => $this->normalizeWhitespace((string) ($result['description'] ?? '')),
            'meta_description' => $this->normalizeWhitespace((string) ($result['meta_description'] ?? '')),
        ];
    }

    private function buildPrompt(string $categoryName, array $context, int $attempt): string
    {
        $retryInstructions = '';

        if ($attempt > 1) {
            $retryInstructions = "\n\nRETRY RULES:\n"
                . "- The last attempt was too repetitive, too similar across both fields, too generic, or did not follow the length constraint.\n"
                . "- Use noticeably different wording, rhythm, and sentence construction between the description and the meta description.\n"
                . "- Never repeat the description's opening phrase inside the meta description.\n"
                . "- Avoid generic openings such as '{$categoryName} software helps businesses' or 'This type of software is perfect for'.";
        }

        $keywords = !empty($context['keyword_hints'])
            ? implode(', ', $context['keyword_hints'])
            : 'No extra workflow hints available';

        $sampleProducts = !empty($context['sample_products'])
            ? implode(', ', $context['sample_products'])
            : 'None available';

        $peerCategories = !empty($context['peer_categories'])
            ? implode(', ', $context['peer_categories'])
            : 'None available';

        $typeLabel = $context['type_label'] ?? 'General category';
        $typeSpecificInstruction = $context['type_specific_instruction'] ?? 'Focus on concrete workflows, evaluation criteria, and buyer intent.';

        return <<<PROMPT
You are an experienced human editor writing taxonomy copy for a software discovery site. Write naturally, clearly, and convincingly. Your job is to write the category description and meta description for "{$categoryName}" so both feel genuinely human-written, useful, and easy to trust.

OBJECTIVE:
- Help readers quickly understand what "{$categoryName}" is, why it matters, and who it helps.
- Keep the writing grounded, specific, and easy to scan.

CATEGORY CONTEXT:
- Taxonomy type: {$typeLabel}
- Writing angle: {$typeSpecificInstruction}
- Workflow hints: {$keywords}
- Example products already associated with this category: {$sampleProducts}
- Nearby categories or peers: {$peerCategories}

HUMAN WRITING RULES:
- Write like a real person explaining a category to another person.
- Use simple words, short sentences, and contractions when they feel natural.
- Mix sentence lengths so the copy does not sound robotic.
- Keep a light human touch, but stay controlled and professional.
- Avoid jargon, filler, and generic marketing hype.
- Do not use cliches like "game-changing", "revolutionary", "cutting-edge", or "unleash your potential".
- Be honest. If the source material is limited, stay specific to what is commonly true about the category and avoid empty claims.
- You may add at most 1-2 subtle, natural phrases that make the copy feel less mechanical, but do not become chatty.
- Do not open with "{$categoryName} software helps businesses".
- Do not write "This type of software is perfect for".
- Do not write "must-have for".
- Do not reuse stock endings like "Compare features and buyer fit".

CATEGORY SEO RULES:
- "description": Write 2-3 short, compelling sentences. Make it specific to the taxonomy type above instead of forcing a generic "software helps businesses" structure every time.
- "meta_description": Write a punchy, click-optimized meta description that is exactly between 140 and 155 characters long.
- The description and meta description must not sound like rewrites of each other.
- Do not reuse the same opening phrase, sentence structure, or key wording across both fields.
- The description should feel like natural editorial copy.
- The meta description should feel like a distinct search snippet written to earn the click.
- Prefer concrete workflows, outputs, or buyer concerns over abstract claims.
- If the taxonomy type is "Use Case", describe the job to be done and when someone starts looking for tools in this area.
- If the taxonomy type is "Best for", describe the audience fit and the team profile it serves well.
- If the taxonomy type is "Platform", describe where the software runs and what platform-specific buyers care about.
- If the taxonomy type is "Software Category", describe what teams compare, what the tools do, and the problems they solve.
{$retryInstructions}

Return the response STRICTLY as a JSON object with exactly two keys: "description" and "meta_description".
Do not include any markdown formatting, code blocks, or explanations. Just the raw JSON object.
PROMPT;
    }

    private function hasValidMetaLength(string $metaDescription): bool
    {
        $length = mb_strlen(trim($metaDescription));

        return $length >= 140 && $length <= 155;
    }

    private function repairMetaLength(string $categoryName, string $metaDescription, array $context): string
    {
        $normalized = $this->dedupeSentences($this->normalizeWhitespace($metaDescription));

        if ($this->hasValidMetaLength($normalized)) {
            return $normalized;
        }

        $fittedOriginal = mb_strlen($normalized) > 155
            ? $this->trimToLength($normalized, 155)
            : $normalized;

        if ($this->hasValidMetaLength($fittedOriginal) && !$this->containsBannedMetaPhrase($fittedOriginal)) {
            return $fittedOriginal;
        }

        return $this->buildFallbackMetaDescription($categoryName, $context);
    }

    private function buildFallbackMetaDescription(string $categoryName, array $context): string
    {
        $type = $context['type'] ?? null;
        $workflowHint = $context['keyword_hints'][0] ?? 'real workflows';

        $templates = match ($type) {
            CategoryTypeRegistry::USE_CASE => [
                "Compare {$categoryName} tools for {$workflowHint}. Explore practical options, buyer fit, and standout features for real teams and workflows.",
                "Find software for {$categoryName}. Compare workflows, features, and buyer fit to choose tools that handle the job with less friction.",
                "Browse {$categoryName} software by workflow, pricing, and buyer fit. Find tools that handle the task cleanly without extra complexity.",
            ],
            CategoryTypeRegistry::BEST_FOR => [
                "Compare software suited to {$categoryName}. Review features, pricing, and buyer fit to find tools that match how this audience actually works.",
                "Find software that fits {$categoryName}. Compare pricing, features, and workflow needs to shortlist tools with a stronger audience fit.",
                "Browse tools built for {$categoryName}. Compare buyer fit, features, and pricing to find options that match day-to-day working needs.",
            ],
            CategoryTypeRegistry::PLATFORM => [
                "Compare {$categoryName} software by use case, features, and pricing. Find tools built for your preferred platform and day-to-day workflow.",
                "Browse {$categoryName} software for real workflows. Compare pricing, features, and buyer fit to find the right platform-first option.",
                "Find {$categoryName} software that fits your workflow. Compare features, pricing, and practical buyer needs across the leading options.",
            ],
            default => [
                "Compare {$categoryName} software by workflow, pricing, and buyer fit. Find tools that match your team, budget, and day-to-day needs.",
                "Browse {$categoryName} software by real use, standout features, and pricing. Find options that fit your team without extra guesswork.",
                "Find {$categoryName} software with clearer pricing, practical workflows, and buyer-fit guidance so you can build a sharper shortlist.",
            ],
        };

        foreach ($this->stableTemplateOrder($templates, $categoryName) as $template) {
            $candidate = $this->fitMetaLength($template);

            if ($this->hasValidMetaLength($candidate) && !$this->containsBannedMetaPhrase($candidate)) {
                return $candidate;
            }
        }

        return $this->fitMetaLength($templates[0]);
    }

    private function stableTemplateOrder(array $templates, string $seed): array
    {
        if (count($templates) <= 1) {
            return $templates;
        }

        $index = abs(crc32(strtolower($seed))) % count($templates);

        return array_merge(array_slice($templates, $index), array_slice($templates, 0, $index));
    }

    private function fitMetaLength(string $text): string
    {
        $text = $this->dedupeSentences($this->normalizeWhitespace($text));

        if ($text === '') {
            return $text;
        }

        if (mb_strlen($text) > 155) {
            $text = $this->trimToLength($text, 155);
        }

        return $this->normalizeWhitespace($text);
    }

    private function trimToLength(string $text, int $maxLength): string
    {
        $text = $this->normalizeWhitespace($text);

        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $trimmed = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($trimmed, ' ');

        if ($lastSpace !== false && $lastSpace >= (int) floor($maxLength * 0.7)) {
            $trimmed = mb_substr($trimmed, 0, $lastSpace);
        }

        return rtrim($trimmed, " ,;:-.") . '.';
    }

    private function normalizeWhitespace(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function dedupeSentences(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $seen = [];
        $unique = [];

        foreach ($parts as $part) {
            $normalized = strtolower(trim(preg_replace('/\s+/u', ' ', $part)));

            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $unique[] = trim($part);
        }

        return trim(implode(' ', $unique));
    }

    private function contentSoundsTooSimilar(string $categoryName, string $description, string $metaDescription): bool
    {
        $descriptionTokens = $this->significantTokens($description, $categoryName);
        $metaTokens = $this->significantTokens($metaDescription, $categoryName);

        if (empty($descriptionTokens) || empty($metaTokens)) {
            return false;
        }

        $descriptionOpening = implode(' ', array_slice($descriptionTokens, 0, 6));
        $metaOpening = implode(' ', array_slice($metaTokens, 0, 6));

        if (!empty($descriptionOpening) && $descriptionOpening === $metaOpening) {
            return true;
        }

        $shared = array_unique(array_intersect($descriptionTokens, $metaTokens));
        $union = array_unique(array_merge($descriptionTokens, $metaTokens));
        $overlap = count($union) > 0 ? count($shared) / count($union) : 0;

        return count($shared) >= 5 && $overlap >= 0.5;
    }

    private function significantTokens(string $text, string $categoryName): array
    {
        preg_match_all('/[a-z0-9]+/i', strtolower($text), $matches);
        $tokens = $matches[0] ?? [];

        preg_match_all('/[a-z0-9]+/i', strtolower($categoryName), $categoryMatches);
        $categoryTokens = $categoryMatches[0] ?? [];

        $stopWords = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'best', 'businesses', 'by', 'for',
            'from', 'helps', 'how', 'in', 'into', 'is', 'it', 'its', 'of', 'on', 'or',
            'that', 'the', 'their', 'this', 'to', 'tools', 'use', 'what', 'who', 'why',
            'with', 'your',
        ];

        return array_values(array_filter($tokens, static function (string $token) use ($stopWords, $categoryTokens) {
            return strlen($token) > 2
                && !in_array($token, $stopWords, true)
                && !in_array($token, $categoryTokens, true);
        }));
    }

    private function buildCategoryContext(string $categoryName, array $runtimeContext = []): array
    {
        $normalizedName = trim($categoryName);
        $existingCategory = Category::query()
            ->with(['types:id,name', 'products:id,name'])
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalizedName)])
            ->first();

        $type = $this->resolveContextType(
            $runtimeContext['category_type'] ?? null,
            $existingCategory?->types->pluck('name')->all() ?? []
        );

        $typeLabel = match ($type) {
            CategoryTypeRegistry::USE_CASE => 'Use Case',
            CategoryTypeRegistry::BEST_FOR => 'Best for',
            CategoryTypeRegistry::PLATFORM => 'Platform',
            CategoryTypeRegistry::SOFTWARE => 'Software Category',
            default => 'General category',
        };

        $sampleProducts = $existingCategory?->products
            ? $existingCategory->products->pluck('name')->filter()->take(5)->values()->all()
            : [];

        $peerCategories = [];

        if ($existingCategory && $existingCategory->types->isNotEmpty()) {
            $typeIds = $existingCategory->types->pluck('id');
            $peerCategories = Category::query()
                ->where('id', '!=', $existingCategory->id)
                ->whereHas('types', fn ($query) => $query->whereIn('types.id', $typeIds))
                ->orderBy('name')
                ->limit(6)
                ->pluck('name')
                ->all();
        }

        $keywordHints = $runtimeContext['keyword_hints'] ?? $this->buildKeywordHints($normalizedName, $type, $peerCategories);

        return [
            'type' => $type,
            'type_label' => $typeLabel,
            'sample_products' => $sampleProducts,
            'peer_categories' => $peerCategories,
            'keyword_hints' => $keywordHints,
            'type_specific_instruction' => $this->typeSpecificInstruction($type, $normalizedName),
        ];
    }

    private function resolveContextType(?string $rawType, array $typeLabels = []): ?string
    {
        $normalized = strtolower(trim((string) $rawType));

        return match (true) {
            in_array($normalized, ['use_case', 'use case', 'use cases'], true) => CategoryTypeRegistry::USE_CASE,
            in_array($normalized, ['best_for', 'best for'], true) => CategoryTypeRegistry::BEST_FOR,
            in_array($normalized, ['platform'], true) => CategoryTypeRegistry::PLATFORM,
            in_array($normalized, ['category', 'software', 'software category', 'software categories'], true) => CategoryTypeRegistry::SOFTWARE,
            $this->matchesTypeLabelBucket($typeLabels, CategoryTypeRegistry::USE_CASE) => CategoryTypeRegistry::USE_CASE,
            $this->matchesTypeLabelBucket($typeLabels, CategoryTypeRegistry::BEST_FOR) => CategoryTypeRegistry::BEST_FOR,
            $this->matchesTypeLabelBucket($typeLabels, CategoryTypeRegistry::PLATFORM) => CategoryTypeRegistry::PLATFORM,
            $this->matchesTypeLabelBucket($typeLabels, CategoryTypeRegistry::SOFTWARE) => CategoryTypeRegistry::SOFTWARE,
            default => null,
        };
    }

    private function matchesTypeLabelBucket(array $typeLabels, string $bucket): bool
    {
        $normalizedLabels = array_map(static fn (string $label) => strtolower(trim($label)), $typeLabels);
        $bucketLabels = array_map(static fn (string $label) => strtolower($label), CategoryTypeRegistry::namesFor($bucket));

        return count(array_intersect($normalizedLabels, $bucketLabels)) > 0;
    }

    private function typeSpecificInstruction(?string $type, string $categoryName): string
    {
        return match ($type) {
            CategoryTypeRegistry::USE_CASE => "Frame {$categoryName} as a job to be done. Explain the workflow or outcome someone wants, and when they start looking for tools in this area.",
            CategoryTypeRegistry::BEST_FOR => "Treat {$categoryName} as an audience or team profile. Focus on who it suits, what they care about, and why the fit is practical.",
            CategoryTypeRegistry::PLATFORM => "Explain what buyers on {$categoryName} care about, including platform compatibility, workflow constraints, and day-to-day usage.",
            CategoryTypeRegistry::SOFTWARE => "Describe what this software category does, what teams compare, and which practical problems it helps solve.",
            default => "Focus on concrete workflows, evaluation criteria, and buyer intent instead of generic software copy.",
        };
    }

    private function buildKeywordHints(string $categoryName, ?string $type, array $peerCategories): array
    {
        $name = strtolower($categoryName);

        $keywords = match (true) {
            str_contains($name, 'photo') || str_contains($name, 'photography') || str_contains($name, 'image') => ['studio shots', 'background cleanup', 'lifestyle scenes', 'marketplace listings', 'ad creatives'],
            str_contains($name, 'seo') => ['keyword targeting', 'content optimization', 'ranking opportunities', 'search traffic'],
            str_contains($name, 'email') => ['campaign copy', 'follow-ups', 'outreach', 'reply rates'],
            str_contains($name, 'support') => ['ticket handling', 'customer replies', 'triage', 'knowledge base'],
            str_contains($name, 'recruit') || str_contains($name, 'candidate') || str_contains($name, 'interview') || str_contains($name, 'resume') => ['candidate screening', 'interview prep', 'job applications', 'hiring workflows'],
            str_contains($name, 'code') || str_contains($name, 'debug') || str_contains($name, 'api') || str_contains($name, 'developer') => ['shipping code', 'debugging', 'integrations', 'developer workflows'],
            str_contains($name, 'video') => ['editing', 'captions', 'clips', 'social publishing'],
            str_contains($name, 'presentation') || str_contains($name, 'slide') => ['slide decks', 'presentations', 'speaker notes', 'client updates'],
            str_contains($name, 'website') => ['landing pages', 'site publishing', 'templates', 'page editing'],
            default => [],
        };

        if ($type === CategoryTypeRegistry::USE_CASE && empty($keywords)) {
            $keywords = [strtolower($categoryName), 'day-to-day workflow', 'buyer intent', 'practical outputs'];
        }

        if (empty($keywords) && !empty($peerCategories)) {
            $keywords = array_map(static fn (string $peer) => strtolower($peer), array_slice($peerCategories, 0, 4));
        }

        return array_values(array_unique(array_filter($keywords)));
    }

    private function contentSoundsOverTemplated(string $categoryName, string $description, string $metaDescription): bool
    {
        $combined = strtolower($description . ' ' . $metaDescription);
        $genericPhrases = [
            strtolower($categoryName . ' software helps businesses'),
            'this type of software is perfect for',
            'must-have for',
            'best light',
            'compare features and buyer fit',
            'find the right match faster',
            'make your shortlist with confidence',
        ];

        foreach ($genericPhrases as $phrase) {
            if ($phrase !== '' && str_contains($combined, $phrase)) {
                return true;
            }
        }

        return $this->containsBannedMetaPhrase($metaDescription);
    }

    private function containsBannedMetaPhrase(string $metaDescription): bool
    {
        $normalized = strtolower($metaDescription);

        return str_contains($normalized, 'compare features and buyer fit')
            || str_contains($normalized, 'find the right match faster')
            || str_contains($normalized, 'make your shortlist with confidence')
            || $this->hasRepeatedSentence($metaDescription);
    }

    private function hasRepeatedSentence(string $text): bool
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', strtolower($this->normalizeWhitespace($text))) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts)));

        return count($parts) !== count(array_unique($parts));
    }
}
