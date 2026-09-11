<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiCompatibleProviderService
{
    public function request(
        string $provider,
        string $apiKey,
        string $prompt,
        float $temperature,
        int $maxTokens,
        ?int $timeout = null
    ): Response {
        $configuration = $this->configuration($provider);

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($timeout ?? $configuration['timeout'])
            ->post($configuration['url'].'/chat/completions', [
                'model' => $configuration['model'],
                'messages' => [[
                    'role' => 'user',
                    'content' => $prompt,
                ]],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);
    }

    private function configuration(string $provider): array
    {
        if ($provider === 'cloudflare') {
            $baseUrl = rtrim((string) config('services.cloudflare_ai.base_url'), '/');
            $accountId = rawurlencode((string) config('services.cloudflare_ai.account_id'));

            return [
                'url' => $baseUrl.'/'.$accountId.'/ai/v1',
                'model' => (string) config('services.cloudflare_ai.model', '@cf/qwen/qwen3-30b-a3b-fp8'),
                'timeout' => max(1, (int) config('services.cloudflare_ai.timeout', 45)),
            ];
        }

        return [
            'url' => rtrim((string) config('services.cerebras.base_url', 'https://api.cerebras.ai/v1'), '/'),
            'model' => (string) config('services.cerebras.model', 'gpt-oss-120b'),
            'timeout' => max(1, (int) config('services.cerebras.timeout', 30)),
        ];
    }
}
