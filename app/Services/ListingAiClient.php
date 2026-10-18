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
        $report = \App\Support\ExtractionReport::current();
        $this->errors = [];
        $task = $task === 'default' ? $this->inferTask($prompt) : $task;
        $router = app(AiProviderRoutingService::class);
        $candidates = $router->providersForTask($task, $excludeProviders);
        if ($this->lastProvider !== null && str_contains($prompt, 'Validation errors to fix:') && $excludeProviders === []) {
            $preferred = $this->lastProvider;
            usort($candidates, static fn (array $a, array $b): int => ($b['provider'] === $preferred) <=> ($a['provider'] === $preferred));
        }
        if ($candidates === []) {
            $this->errors[] = 'No AI provider is currently available. Check provider keys, limits, and retry times.';
        }
        $schema = app(ListingOutputSchema::class)->forPrompt($prompt);
        foreach ($candidates as $candidate) {
            try {
                $provider = $candidate['provider'];
                $key = $candidate['key'];
                $format = $this->responseFormat($provider, $router->modelFor($provider), $schema);
                $report->record($task, 'Requesting '.$provider.' / '.$router->modelFor($provider));
                if (in_array($provider, ['cloudflare', 'cerebras'], true)) {
                    $response = app(OpenAiCompatibleProviderService::class)->request($provider, $key, $prompt, 0.2, $maxTokens, 30, ['response_format' => $format]);
                    $content = data_get($response->json(), 'choices.0.message.content');
                } elseif ($provider === 'gemini') {
                    $base = rtrim((string) config('services.google.gemini_base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
                    $model = config('services.google.gemini_model', 'gemini-2.5-flash');
                    $response = Http::withHeaders(['X-goog-api-key' => $key])->timeout(30)->post($base.'/models/'.$model.':generateContent', [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature' => 0.2, 'maxOutputTokens' => $maxTokens, 'responseMimeType' => 'application/json',
                            ...($schema !== null ? ['responseJsonSchema' => $schema] : []),
                            ...($task === 'seo' ? ['thinkingConfig' => ['thinkingBudget' => 0]] : []),
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
                        'response_format' => $format,
                        ...($provider === 'openrouter' && $schema !== null ? ['provider' => ['require_parameters' => true]] : []),
                    ]);
                    $content = data_get($response->json(), 'choices.0.message.content');
                }
                $content = $this->textContent($provider === 'gemini' ? $response->json('candidates.0.content.parts') : $content);
                $finishReason = $provider === 'gemini'
                    ? $response->json('candidates.0.finishReason')
                    : $response->json('choices.0.finish_reason');
                if ($response->successful()) {
                    $router->recordHttpSuccess($provider, $response);
                    if (in_array($finishReason, ['MAX_TOKENS', 'length'], true)) {
                        $this->diagnose($provider, $task, $response, $content, $finishReason);
                        Log::warning('Listing AI output truncated.', ['provider' => $provider, 'task' => $task, 'finish_reason' => $finishReason, 'output_budget' => $maxTokens]);
                        $report->record($task, $provider.' output truncated; retrying with larger budget or next provider.', 'warning');
                        if ($maxTokens < 3600) {
                            $retried = $this->jsonWithProvider($prompt, $provider, 3600, $task);
                            if ($retried !== null) {
                                return $retried;
                            }
                        }
                        $this->errors[] = "$provider output was truncated.";

                        continue;
                    }
                    if (! is_string($content) || trim($content) === '') {
                        $this->diagnose($provider, $task, $response, $content, $finishReason);
                        $message = "$provider returned empty or missing message content (HTTP ".$response->status().'; finish reason: '.($finishReason ?? 'unknown').').';
                        $report->record($task, $message, 'warning');
                        $this->errors[] = $message;

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
                        $report->record($task, $provider.' returned usable JSON.', 'success');
                        $this->lastProvider = $provider;
                        Log::info('Listing AI response accepted.', [
                            'provider' => $provider,
                            'model' => $response->json('model') ?? $router->modelFor($provider),
                            'task' => $task,
                            'prompt_characters' => mb_strlen($prompt),
                            'response_characters' => mb_strlen($content),
                            'usage' => $response->json('usage'),
                        ]);

                        return $decoded;
                    }
                    $parseError = json_last_error() === JSON_ERROR_NONE ? 'Expected a JSON object or array.' : json_last_error_msg();
                    $this->diagnose($provider, $task, $response, $content, $finishReason);
                    $report->record($task, $provider.' returned invalid JSON: '.$parseError.'; trying next provider.', 'warning');
                    $this->errors[] = "$provider returned invalid JSON: ".$parseError;
                    Log::warning('Listing AI JSON parsing failed.', ['provider' => $provider, 'task' => $task, 'finish_reason' => $finishReason, 'parse_error' => $parseError, 'response_characters' => mb_strlen($content)]);
                } else {
                    $this->diagnose($provider, $task, $response, $content, $finishReason);
                    if (! $response->successful()) {
                        $router->recordHttpFailure($provider, $response);
                    }
                    $report->record($task, $provider.' returned HTTP '.$response->status().'; trying next provider.', 'warning');
                    $this->errors[] = "$provider returned HTTP ".$response->status().'.';
                }
            } catch (\Throwable $error) {
                $report->record($task, $provider.' request failed ('.class_basename($error).'); trying next provider.', 'warning');
                $this->errors[] = "$provider request failed.";
                if (isset($provider)) {
                    $router->recordTransportFailure($provider);
                }
                Log::warning('Listing AI provider exception.', ['provider' => $provider, 'error' => $error->getMessage()]);

                continue;
            }
        }

        foreach ($this->errors as $error) {
            $report->record($task, $error, 'error');
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

    private function textContent(mixed $content): mixed
    {
        if (! is_array($content)) {
            return $content;
        }
        $parts = [];
        foreach ($content as $part) {
            if (is_array($part) && empty($part['thought']) && in_array($part['type'] ?? 'text', ['text', 'output_text'], true) && is_string($part['text'] ?? null)) {
                $parts[] = $part['text'];
            }
        }

        return $parts === [] ? $content : implode('', $parts);
    }

    private function responseFormat(string $provider, string $model, ?array $schema): array
    {
        $strict = $provider === 'openrouter'
            || ($provider === 'groq' && in_array($model, config('listing_generation.structured_models.groq', []), true))
            || ($provider === 'cerebras' && in_array($model, config('listing_generation.structured_models.cerebras', []), true));
        if ($schema !== null && $strict) {
            return ['type' => 'json_schema', 'json_schema' => ['name' => 'listing_output', 'strict' => true, 'schema' => $schema]];
        }

        return ['type' => 'json_object'];
    }

    private function diagnose(string $provider, string $task, \Illuminate\Http\Client\Response $response, mixed $content, ?string $finishReason): void
    {
        // Excerpts exclude the envelope, credentials, and request headers.
        $excerpt = is_string($content) ? mb_substr($content, 0, 500) : null;
        Log::warning('Listing AI response diagnostic.', [
            'provider' => $provider, 'task' => $task, 'status' => $response->status(),
            'model' => $response->json('model') ?? app(AiProviderRoutingService::class)->modelFor($provider),
            'finish_reason' => $finishReason, 'content_type' => get_debug_type($content),
            'usage' => $response->json('usage') ?? $response->json('usageMetadata'),
            'error_code' => $response->json('error.code'),
            'error_message' => mb_substr((string) $response->json('error.message', ''), 0, 500),
            'refusal' => mb_substr((string) $response->json('choices.0.message.refusal', ''), 0, 500),
            'response_excerpt' => $excerpt,
        ]);
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
