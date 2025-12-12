<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategoryClassifier
{
    public function classify(string $text): array
    {
        try {
            $categories = Category::whereHas('types', fn($q) => $q->where('name', 'Category'))->pluck('name')->implode(', ');
            $bestFor = Category::whereHas('types', fn($q) => $q->where('name', 'Best for'))->pluck('name')->implode(', ');
            $pricing = Category::whereHas('types', fn($q) => $q->where('name', 'Pricing'))->pluck('name')->implode(', ');

            $promptTemplate = file_get_contents(resource_path('prompts/category_classification_prompt.txt'));
            $prompt = str_replace(
                ['{available_categories}', '{available_best_for_tags}', '{available_pricing_models}', '{website_content}'],
                [$categories, $bestFor, $pricing, $text],
                $promptTemplate
            );

            $apiKey = config('services.google.api_key');
            if (!$apiKey) {
                return ['categories' => [], 'best_for' => [], 'pricing' => []];
            }

            $response = Http::withHeaders([
                'X-goog-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent", [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            if ($response->failed()) {
                return ['categories' => [], 'best_for' => [], 'pricing' => []];
            }

            $responseText = $response->json('candidates.0.content.parts.0.text', '');
            
            // Clean the response text
            $cleanedText = str_replace(['```json', '```'], '', $responseText);
            $jsonResponse = json_decode(trim($cleanedText), true);

            Log::info('Category Classification Response', [
                'raw_response' => $responseText,
                'cleaned_text' => $cleanedText,
                'json_decoded' => $jsonResponse
            ]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode JSON from category classifier.', ['error' => json_last_error_msg(), 'response' => $responseText]);
                return ['categories' => [], 'best_for' => [], 'pricing' => []];
            }

            return [
                'categories' => $jsonResponse['categories'] ?? [],
                'best_for' => $jsonResponse['best_for'] ?? [],
                'pricing' => $jsonResponse['pricing'] ?? [],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to classify categories.', ['error' => $e->getMessage()]);
            return ['categories' => [], 'best_for' => [], 'pricing' => []];
        }
    }
}