<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ListingAiClient
{
    private array $errors = [];

    private ?string $lastProvider = null;

    public function errors(): array
    {
        return $this->errors;
    }

    public function lastProvider(): ?string
    {
        return $this->lastProvider;
    }

    public function json(string $prompt, int $maxTokens = 1800, string $task = 'default', array $excludeProviders = []): ?array
    {
        $this->errors = [];
        $task = $task === 'default' ? $this->inferTask($prompt) : $task;
        if ($excludeProviders === [] && $this->lastProvider !== null && str_contains($prompt, 'Validation errors to fix:')) {
            $excludeProviders = [$this->lastProvider];
        }
        $router = app(AiProviderRoutingService::class);
        $candidates = $router->providersForTask($task, $excludeProviders);
        if ($candidates === []) {
            $this->errors[] = 'No AI provider is currently available. Check provider keys, limits, and retry times.';
        }
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
                        'generationConfig' => [
                            'temperature' => 0.2, 'maxOutputTokens' => $maxTokens, 'responseMimeType' => 'application/json',
                            ...($task === 'seo' ? ['responseSchema' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'seo_title' => ['type' => 'STRING'],
                                    'meta_descriptions' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING'], 'minItems' => 3, 'maxItems' => 3],
                                ],
                                'required' => ['seo_title', 'meta_descriptions'],
                            ], 'thinkingConfig' => ['thinkingBudget' => 0]] : []),
                        ],
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
                    $finishReason = $provider === 'gemini'
                        ? $response->json('candidates.0.finishReason')
                        : $response->json('choices.0.finish_reason');
                    if (in_array($finishReason, ['MAX_TOKENS', 'length'], true)) {
                        Log::warning('Listing AI output truncated.', ['provider' => $provider, 'task' => $task, 'finish_reason' => $finishReason, 'output_budget' => $maxTokens]);
                        if ($maxTokens < 3600) {
                            $retried = $this->jsonWithProvider($prompt, $provider, 3600, $task);
                            if ($retried !== null) {
                                return $retried;
                            }
                        }
                        $this->errors[] = "$provider output was truncated.";

                        continue;
                    }
                    $clean = preg_replace('/\A```(?:json)?\s*|\s*```\z/iu', '', trim($content));
                    $decoded = json_decode(trim($clean), true);
                    if (json_last_error() === JSON_ERROR_CTRL_CHAR) {
                        $clean = $this->escapeStringControls($clean);
                        $decoded = json_decode($clean, true);
                    }
                    if (! is_array($decoded) && preg_match('/\{.*\}/s', $clean, $match)) {
                        $decoded = json_decode($match[0], true);
                    }
                    if (is_array($decoded)) {
                        $this->lastProvider = $provider;
                        Log::info('Listing AI response accepted.', [
                            'provider' => $provider,
                            'model' => $router->modelFor($provider),
                            'task' => $task,
                            'prompt_characters' => mb_strlen($prompt),
                            'response_characters' => mb_strlen($content),
                            'usage' => $response->json('usage'),
                        ]);

                        return $decoded;
                    }
                    $this->errors[] = "$provider returned invalid JSON: ".json_last_error_msg();
                    Log::warning('Listing AI JSON parsing failed.', ['provider' => $provider, 'task' => $task, 'finish_reason' => $finishReason, 'parse_error' => json_last_error_msg(), 'response_characters' => mb_strlen($content)]);
                } else {
                    if (! $response->successful()) {
                        $router->recordHttpFailure($provider, $response);
                    }
                    $this->errors[] = "$provider returned HTTP ".$response->status().'.';
                }
            } catch (\Throwable $error) {
                $this->errors[] = "$provider request failed.";
                if (isset($provider)) {
                    $router->recordTransportFailure($provider);
                }
                Log::warning('Listing AI provider exception.', ['provider' => $provider, 'error' => $error->getMessage()]);

                continue;
            }
        }

        Log::warning('Listing AI request failed.', ['errors' => $this->errors]);

        return null;
    }

    public function jsonWithProvider(string $prompt, string $provider, int $maxTokens = 1800, string $task = 'copy'): ?array
    {
        $allowed = ['gemini', 'groq', 'cloudflare', 'openrouter', 'cerebras'];
        if (! in_array($provider, $allowed, true)) {
            return null;
        }

        return $this->json($prompt, $maxTokens, $task, array_values(array_diff($allowed, [$provider])));
    }

    private function inferTask(string $prompt): string
    {
        return match (true) {
            str_contains($prompt, 'You extract facts from a software product website') => 'facts',
            str_contains($prompt, 'Write SEO fields') => 'seo',
            str_contains($prompt, 'You check generated listing fields') => 'verification',
            str_contains($prompt, 'Choose the most relevant categories') => 'classification',
            default => 'copy',
        };
    }

    private function escapeStringControls(string $json): string
    {
        $inside = false;
        $escaped = false;
        $result = '';
        for ($i = 0; $i < strlen($json); $i++) {
            $character = $json[$i];
            if ($inside && ord($character) < 32) {
                $result .= sprintf('\\u%04x', ord($character));
                $escaped = false;

                continue;
            }
            if ($character === '"' && ! $escaped) {
                $inside = ! $inside;
            }
            $result .= $character;
            $escaped = $inside && $character === '\\' && ! $escaped;
        }

        return $result;
    }

    public function prompt(string $file, array $bindings): string
    {
        return strtr(file_get_contents(resource_path('prompts/'.$file)), $bindings);
    }
}
