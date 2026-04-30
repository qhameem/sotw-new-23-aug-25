<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryDescriptionGenerator
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 30;
    private const TEMPERATURE = 0.55;
    private const MAX_ATTEMPTS = 2;

    /**
     * Generate SEO description and meta description for a software category
     *
     * Returns an array with 'description' and 'meta_description' or null on failure.
     */
    public function generate(string $categoryName): ?array
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            Log::warning('CategoryDescriptionGenerator: GROQ_API_KEY is not set.');
            return null;
        }

        if (empty(trim($categoryName))) {
            return null;
        }

        try {
            $lastFailureReason = 'unknown';
            $bestResult = null;

            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                $result = $this->requestCategoryCopy($apiKey, $categoryName, $attempt);

                if ($result === null) {
                    $lastFailureReason = 'request_failed';
                    continue;
                }

                $result = $this->normalizeResult($result);

                if (!$this->hasValidMetaLength($result['meta_description'])) {
                    $result['meta_description'] = $this->repairMetaLength($categoryName, $result['meta_description']);
                    $lastFailureReason = 'invalid_meta_length';
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
                if ($this->contentSoundsTooSimilar($categoryName, $bestResult['description'], $bestResult['meta_description'])) {
                    $bestResult['meta_description'] = $this->buildFallbackMetaDescription($categoryName);
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

    private function requestCategoryCopy(string $apiKey, string $categoryName, int $attempt): ?array
    {
        $response = Http::timeout(self::TIMEOUT)
            ->withToken($apiKey)
            ->post(self::GROQ_API_URL, [
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->buildPrompt($categoryName, $attempt),
                    ],
                ],
                'temperature' => self::TEMPERATURE,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            Log::warning('CategoryDescriptionGenerator: Groq API error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'attempt' => $attempt,
            ]);

            return null;
        }

        $content = $response->json('choices.0.message.content');

        if (!is_string($content)) {
            return null;
        }

        $data = json_decode($content, true);

        if (!isset($data['description'], $data['meta_description'])) {
            return null;
        }

        return [
            'description' => trim((string) $data['description']),
            'meta_description' => trim((string) $data['meta_description']),
        ];
    }

    private function normalizeResult(array $result): array
    {
        return [
            'description' => $this->normalizeWhitespace((string) ($result['description'] ?? '')),
            'meta_description' => $this->normalizeWhitespace((string) ($result['meta_description'] ?? '')),
        ];
    }

    private function buildPrompt(string $categoryName, int $attempt): string
    {
        $retryInstructions = '';

        if ($attempt > 1) {
            $retryInstructions = "\n\nRETRY RULES:\n"
                . "- The last attempt was too repetitive, too similar across both fields, or did not follow the length constraint.\n"
                . "- Use noticeably different wording, rhythm, and sentence construction between the description and the meta description.\n"
                . "- Never repeat the description's opening phrase inside the meta description.";
        }

        return <<<PROMPT
You are an experienced human writer with 20+ years of experience. Write naturally, clearly, and convincingly. Your job is to write the category description and meta description for "{$categoryName}" so both feel genuinely human-written, useful, and easy to trust.

OBJECTIVE:
- Help readers quickly understand what "{$categoryName}" software is, why it matters, and who it helps.
- Keep the writing grounded, specific, and easy to scan.

HUMAN WRITING RULES:
- Write like a real person explaining a category to another person.
- Use simple words, short sentences, and contractions when they feel natural.
- Mix sentence lengths so the copy does not sound robotic.
- Keep a light human touch, but stay controlled and professional.
- Avoid jargon, filler, and generic marketing hype.
- Do not use cliches like "game-changing", "revolutionary", "cutting-edge", or "unleash your potential".
- Be honest. If the source material is limited, stay specific to what is commonly true about the category and avoid empty claims.
- You may add at most 1-2 subtle, natural phrases that make the copy feel less mechanical, but do not become chatty.

CATEGORY SEO RULES:
- "description": Write 2-3 short, compelling sentences explaining what "{$categoryName}" software is, why businesses use it, and what kind of buyer it fits best.
- "meta_description": Write a punchy, click-optimized meta description that is exactly between 140 and 155 characters long.
- The description and meta description must not sound like rewrites of each other.
- Do not reuse the same opening phrase, sentence structure, or key wording across both fields.
- The description should feel like natural editorial copy.
- The meta description should feel like a distinct search snippet written to earn the click.
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

    private function repairMetaLength(string $categoryName, string $metaDescription): string
    {
        $normalized = $this->normalizeWhitespace($metaDescription);

        if ($this->hasValidMetaLength($normalized)) {
            return $normalized;
        }

        $fittedOriginal = $this->fitMetaLength($normalized);

        if ($this->hasValidMetaLength($fittedOriginal)) {
            return $fittedOriginal;
        }

        return $this->buildFallbackMetaDescription($categoryName);
    }

    private function buildFallbackMetaDescription(string $categoryName): string
    {
        $templates = [
            "Explore {$categoryName} software by features, pricing, buyer fit, and standout tools so you can find the right option without wasting weeks on demos.",
            "Compare {$categoryName} software by features, pricing, buyer fit, and real-world use so your team can choose the right option with less guesswork.",
            "Find {$categoryName} software that fits your team with clearer pricing, standout features, and practical buyer guidance for a faster shortlist.",
        ];

        foreach ($templates as $template) {
            $candidate = $this->fitMetaLength($template);

            if ($this->hasValidMetaLength($candidate)) {
                return $candidate;
            }
        }

        return $this->fitMetaLength($templates[0]);
    }

    private function fitMetaLength(string $text): string
    {
        $text = $this->normalizeWhitespace($text);

        if ($text === '') {
            return $text;
        }

        if (mb_strlen($text) > 155) {
            $text = $this->trimToLength($text, 155);
        }

        $suffixes = [
            ' Compare features and buyer fit.',
            ' See which options fit best.',
            ' Find the right match faster.',
            ' Make your shortlist with confidence.',
        ];

        foreach ($suffixes as $suffix) {
            if (mb_strlen($text) >= 140) {
                break;
            }

            $candidate = rtrim(rtrim($text, '.! '), ',') . $suffix;

            if (mb_strlen($candidate) <= 155) {
                $text = $candidate;
                break;
            }
        }

        if (mb_strlen($text) < 140) {
            $text = $this->trimToLength($text . ' Compare features, buyer fit, and practical tradeoffs.', 155);
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

        return count($shared) >= 6 && $overlap >= 0.6;
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
}
