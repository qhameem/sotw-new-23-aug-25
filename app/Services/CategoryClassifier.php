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

    private const OPENROUTER_API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const GROQ_MODEL = 'openai/gpt-oss-120b';

    private const MAX_CONTENT_LENGTH = 8000;

    private const REQUEST_TIMEOUT_SECONDS = 25;

    public function classify(string $text): array
    {
        try {
            $categories = Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE)))->pluck('name')->implode(', ');
            $useCases = Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE)))->pluck('name')->implode(', ');
            $bestFor = Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR)))->pluck('name')->implode(', ');
            $pricing = Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING)))->pluck('name')->implode(', ');
            $platforms = Category::whereHas('types', fn ($q) => $q->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM)))->pluck('name')->implode(', ');
            $preparedContent = $this->prepareWebsiteContent($text);

            $promptTemplate = file_get_contents(resource_path('prompts/category_classification_prompt.txt'));
            $prompt = str_replace(
                ['{available_categories}', '{available_use_cases}', '{available_best_for_tags}', '{available_pricing_models}', '{available_platforms}', '{website_content}'],
                [$categories, $useCases, $bestFor, $pricing, $platforms, $preparedContent],
                $promptTemplate
            );

            $providerRouter = app(AiProviderRoutingService::class);
            foreach ($providerRouter->orderedConfiguredProviders(['openrouter', 'cloudflare', 'groq', 'gemini']) as $candidate) {
                try {
                    $responseText = match ($candidate['provider']) {
                        'groq' => $this->classifyWithGroq($candidate['key'], $prompt),
                        'openrouter' => $this->classifyWithOpenRouter($candidate['key'], $prompt),
                        'cerebras', 'cloudflare' => $this->classifyWithCompatibleProvider(
                            $candidate['provider'],
                            $candidate['key'],
                            $prompt
                        ),
                        default => $this->classifyWithGemini($candidate['key'], $prompt),
                    };
                } catch (\Throwable $e) {
                    $providerRouter->recordTransportFailure($candidate['provider']);
                    Log::warning('Category classifier provider request failed.', [
                        'provider' => $candidate['provider'],
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }

                $jsonResponse = is_string($responseText) && trim($responseText) !== ''
                    ? $this->decodeJsonResponse($responseText)
                    : null;

                if ($jsonResponse === null || ! $this->hasValidClassification($jsonResponse)) {
                    Log::warning('Category classifier returned an unusable response.', [
                        'provider' => $candidate['provider'],
                        'response_length' => is_string($responseText) ? strlen($responseText) : 0,
                    ]);
                    continue;
                }

                return [
                    'categories' => $jsonResponse['categories'],
                    'use_cases' => $jsonResponse['use_cases'],
                    'best_for' => $jsonResponse['best_for'],
                    'pricing' => $jsonResponse['pricing'],
                    'platforms' => $jsonResponse['platforms'],
                ];
            }

            return $this->failureResult();
        } catch (\Exception $e) {
            Log::error('Failed to classify categories.', ['error' => $e->getMessage()]);

            return $this->failureResult();
        }
    }

    private function hasValidClassification(array $response): bool
    {
        foreach (['categories', 'use_cases', 'best_for', 'pricing', 'platforms'] as $field) {
            if (! isset($response[$field]) || ! is_array($response[$field])) {
                return false;
            }
            foreach ($response[$field] as $value) {
                if (! is_string($value)) {
                    return false;
                }
            }
        }

        return $response['categories'] !== [] || $response['use_cases'] !== [] || $response['pricing'] !== [];
    }

    private function failureResult(): array
    {
        return ['categories' => [], 'use_cases' => [], 'best_for' => [], 'pricing' => [], 'platforms' => [], 'error' => 'Classification could not be completed. Select categories, use cases, and pricing manually.'];
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
        ])->timeout(self::REQUEST_TIMEOUT_SECONDS)->post(self::GEMINI_API_URL, [
            'contents' => [['parts' => [['text' => $prompt]]]],
        ]);

        if ($response->failed()) {
            Log::warning('Category classifier Gemini request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            app(AiProviderRoutingService::class)->recordHttpFailure('gemini', $response);

            return null;
        }

        app(AiProviderRoutingService::class)->recordHttpSuccess('gemini', $response);

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    private function classifyWithGroq(string $apiKey, string $prompt): ?string
    {
        $response = Http::withToken($apiKey)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
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
            app(AiProviderRoutingService::class)->recordHttpFailure('groq', $response);

            return null;
        }

        app(AiProviderRoutingService::class)->recordHttpSuccess('groq', $response);

        return data_get($response->json(), 'choices.0.message.content');
    }

    private function classifyWithOpenRouter(string $apiKey, string $prompt): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'HTTP-Referer' => config('app.url'),
            'X-OpenRouter-Title' => config('app.name'),
        ])->timeout(self::REQUEST_TIMEOUT_SECONDS)->post(self::OPENROUTER_API_URL, [
            'model' => (string) config('services.openrouter.model', 'openrouter/auto'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
        ]);

        if ($response->failed()) {
            Log::warning('Category classifier OpenRouter request failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            app(AiProviderRoutingService::class)->recordHttpFailure('openrouter', $response);

            return null;
        }

        app(AiProviderRoutingService::class)->recordHttpSuccess('openrouter', $response);

        return data_get($response->json(), 'choices.0.message.content');
    }

    private function classifyWithCompatibleProvider(string $provider, string $apiKey, string $prompt): ?string
    {
        $response = app(OpenAiCompatibleProviderService::class)->request(
            $provider,
            $apiKey,
            $prompt,
            0.2,
            1000,
            self::REQUEST_TIMEOUT_SECONDS
        );

        if ($response->failed()) {
            Log::warning('Category classifier compatible provider request failed.', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            app(AiProviderRoutingService::class)->recordHttpFailure($provider, $response);

            return null;
        }

        app(AiProviderRoutingService::class)->recordHttpSuccess($provider, $response);

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
