<?php

use App\Services\OpenAiCompatibleProviderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('cerebras uses its openai compatible endpoint', function () {
    config([
        'services.cerebras.base_url' => 'https://api.cerebras.test/v1',
        'services.cerebras.model' => 'gpt-oss-120b',
    ]);
    Http::fake(['*' => Http::response(['choices' => []])]);

    app(OpenAiCompatibleProviderService::class)->request('cerebras', 'secret', 'Prompt', 0.2, 120);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cerebras.test/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request['model'] === 'gpt-oss-120b');
});

test('cloudflare uses the account scoped openai compatible endpoint', function () {
    config([
        'services.cloudflare_ai.base_url' => 'https://api.cloudflare.test/client/v4/accounts',
        'services.cloudflare_ai.account_id' => 'account-id',
        'services.cloudflare_ai.model' => '@cf/meta/llama-3.1-8b-instruct-fp8-fast',
    ]);
    Http::fake(['*' => Http::response(['choices' => []])]);

    app(OpenAiCompatibleProviderService::class)->request('cloudflare', 'secret', 'Prompt', 0.2, 120);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.cloudflare.test/client/v4/accounts/account-id/ai/v1/chat/completions'
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request['model'] === '@cf/meta/llama-3.1-8b-instruct-fp8-fast');
});
