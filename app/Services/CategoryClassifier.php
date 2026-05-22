<?php

namespace App\Services;

use App\Models\Category;
use App\Support\CategoryTypeRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryClassifier
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent';
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL = 'llama-3.3-70b-versatile';
    private const MAX_CONTENT_LENGTH = 8000;

    public function classify(string $text): array
    {
        try {
            $categories = Category::whereHas('types', fn($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE)))->pluck('name')->implode(', ');
            $useCases = Category::whereHas('types', fn($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE)))->pluck('name')->implode(', ');
            $bestFor = Category::whereHas('types', fn($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR)))->pluck('name')->implode(', ');
            $pricing = Category::whereHas('types', fn($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING)))->pluck('name')->implode(', ');
            $platforms = Category::whereHas('types', fn($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM)))->pluck('name')->implode(', ');
            $preparedContent = $this->prepareWebsiteContent($text);

            $promptTemplate = file_get_contents(resource_path('prompts/category_classification_prompt.txt'));
            $prompt = str_replace(
                ['{available_categories}', '{available_use_cases}', '{available_best_for_tags}', '{available_pricing_models}', '{available_platforms}', '{website_content}'],
                [$categories, $useCases, $bestFor, $pricing, $platforms, $preparedContent],
                $promptTemplate
            );

            $apiKey = config('services.google.api_key');
            $groqApiKey = config('services.groq.key');

            $responseText = null;

            if ($apiKey) {
                $responseText = $this->classifyWithGemini($apiKey, $prompt);
            }

            if ($responseText === null && $groqApiKey) {
                $responseText = $this->classifyWithGroq($groqApiKey, $prompt);
            }

            if (!is_string($responseText) || trim($responseText) === '') {
                return ['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => []];
            }

            $jsonResponse = $this->decodeJsonResponse($responseText);

            Log::info('Category Classification Response', [
                'raw_response' => $responseText,
                'json_decoded' => $jsonResponse
            ]);

            if ($jsonResponse === null) {
                Log::error('Failed to decode JSON from category classifier.', ['response' => $responseText]);
                return ['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => []];
            }

            return [
                'categories' => $jsonResponse['categories'] ?? [],
                'use_cases' => $jsonResponse['use_cases'] ?? [],
                'best_for' => $jsonResponse['best_for'] ?? [],
                'pricing' => $jsonResponse['pricing'] ?? [],
                'platforms' => $jsonResponse['platforms'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to classify categories.', ['error' => $e->getMessage()]);
            return ['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => []];
        }
    }

    private function prepareWebsiteContent(string $text): string
    {
        $cleaned = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $text) ?? $text;
        $cleaned = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $cleaned) ?? $cleaned;
        $cleaned = strip_tags($cleaned);
        $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;

        return mb_substr(trim($cleaned), 0, self::MAX_CONTENT_LENGTH);
    }

    private function classifyWithGemini(string $apiKey, string $prompt): ?string
    {
        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post(self::GEMINI_API_URL, [
            'contents' => [['parts' => [['text' => $prompt]]]]
        ]);

        if ($response->failed()) {
            Log::warning('Category classifier Gemini request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    private function classifyWithGroq(string $apiKey, string $prompt): ?string
    {
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post(self::GROQ_API_URL, [
                'model' => self::GROQ_MODEL,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ]);

        if ($response->failed()) {
            Log::warning('Category classifier Groq request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return data_get($response->json(), 'choices.0.message.content');
    }

    private function decodeJsonResponse(string $responseText): ?array
    {
        $cleanedText = trim(str_replace(['```json', '```'], '', $responseText));
        $decoded = json_decode($cleanedText, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $cleanedText, $matches) !== 1) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : null;
    }
}
