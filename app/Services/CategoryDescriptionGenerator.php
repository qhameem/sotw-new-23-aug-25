<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryDescriptionGenerator
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const TIMEOUT = 30;

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

        $prompt = <<<PROMPT
You are an expert SEO copywriter for a software directory website. Your task is to write a category description and a meta description for a software category named "{$categoryName}".

Guidelines:
1. Description: Write 2-3 short, compelling sentences explaining what "{$categoryName}" software is and why businesses use it.
2. Meta Description: Write a punchy, click-optimized meta description that is exactly between 140 and 155 characters long. It MUST not be shorter than 140 characters.

Return the response STRICTLY as a JSON object with exactly two keys: "description" and "meta_description".
Do not include any markdown formatting, code blocks, or explanations. Just the raw JSON object.
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
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (!$response->successful()) {
                Log::warning('CategoryDescriptionGenerator: Groq API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $content = $response->json('choices.0.message.content');

            if (!is_string($content)) {
                return null;
            }

            $data = json_decode($content, true);

            if (isset($data['description']) && isset($data['meta_description'])) {
                return [
                    'description' => trim($data['description']),
                    'meta_description' => trim($data['meta_description']),
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('CategoryDescriptionGenerator: Exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
