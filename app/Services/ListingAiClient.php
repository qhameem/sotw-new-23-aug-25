<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ListingAiClient
{
    public function json(string $prompt, int $maxTokens = 1800): ?array
    {
        $router = app(AiProviderRoutingService::class);
        foreach ($router->orderedConfiguredProviders(['openrouter', 'cloudflare', 'groq', 'gemini']) as $candidate) {
            try {
                $provider = $candidate['provider'];
                $key = $candidate['key'];
                if (in_array($provider, ['cloudflare', 'cerebras'], true)) {
                    $response = app(OpenAiCompatibleProviderService::class)->request($provider, $key, $prompt, 0.2, $maxTokens, 30);
                    $content = data_get($response->json(), 'choices.0.message.content');
                } elseif ($provider === 'gemini') {
                    $base = rtrim((string) config('services.google.gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
                    $model = config('services.google.gemini_model', 'gemini-2.5-flash');
                    $response = Http::withHeaders(['X-goog-api-key' => $key])->timeout(30)->post($base.'/models/'.$model.':generateContent', [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => $maxTokens, 'responseMimeType' => 'application/json'],
                    ]);
                    $content = $response->json('candidates.0.content.parts.0.text');
                } else {
                    $base = $provider === 'groq'
                        ? config('services.groq.base_url', 'https://api.groq.com/openai/v1')
                        : config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
                    $model = $provider === 'groq' ? config('services.groq.model', 'openai/gpt-oss-120b') : config('services.openrouter.model', 'openrouter/auto');
                    $response = Http::withToken($key)->timeout(30)->post(rtrim($base, '/').'/chat/completions', [
                        'model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.2, 'max_tokens' => $maxTokens,
                    ]);
                    $content = data_get($response->json(), 'choices.0.message.content');
                }
                if ($response->successful() && is_string($content)) {
                    $decoded = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', trim($content))), true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    public function prompt(string $file, array $bindings): string
    {
        return strtr(file_get_contents(resource_path('prompts/'.$file)), $bindings);
    }
}
