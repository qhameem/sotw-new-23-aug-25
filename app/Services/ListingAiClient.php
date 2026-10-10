<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListingAiClient
{
    private array $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    public function json(string $prompt, int $maxTokens = 1800): ?array
    {
        $this->errors = [];
        $router = app(AiProviderRoutingService::class);
        $candidates = $router->orderedConfiguredProviders(['openrouter', 'cerebras', 'cloudflare', 'groq', 'gemini']);
        if ($candidates === []) $this->errors[] = 'No AI provider is currently available. Check provider keys, limits, and retry times.';
        foreach ($candidates as $candidate) {
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
                    $headers = $provider === 'openrouter'
                        ? ['HTTP-Referer' => config('app.url'), 'X-OpenRouter-Title' => config('app.name')]
                        : [];
                    $response = Http::withToken($key)->withHeaders($headers)->timeout(30)->post(rtrim($base, '/').'/chat/completions', [
                        'model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.2, 'max_tokens' => $maxTokens,
                        ...($provider === 'groq' ? ['response_format' => ['type' => 'json_object']] : []),
                    ]);
                    $content = data_get($response->json(), 'choices.0.message.content');
                }
                if ($response->successful() && is_string($content)) {
                    $router->recordHttpSuccess($provider, $response);
                    $clean = preg_replace('/\A```(?:json)?\s*|\s*```\z/iu', '', trim($content));
                    $decoded = json_decode(trim($clean), true);
                    if (! is_array($decoded) && preg_match('/\{.*\}/s', $clean, $match)) {
                        $decoded = json_decode($match[0], true);
                    }
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                    $this->errors[] = "$provider returned invalid JSON: ".json_last_error_msg();
                } else {
                    if (! $response->successful()) $router->recordHttpFailure($provider, $response);
                    $this->errors[] = "$provider returned HTTP ".$response->status().'.';
                }
            } catch (\Throwable $error) {
                $this->errors[] = "$provider request failed.";
                if (isset($provider)) $router->recordTransportFailure($provider);
                Log::warning('Listing AI provider exception.', ['provider' => $provider, 'error' => $error->getMessage()]);
                continue;
            }
        }

        Log::warning('Listing AI request failed.', ['errors' => $this->errors]);

        return null;
    }

    public function prompt(string $file, array $bindings): string
    {
        return strtr(file_get_contents(resource_path('prompts/'.$file)), $bindings);
    }
}
