<?php

use App\Services\ListingAiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::clear();
    config([
        'services.google.api_key' => null, 'services.cloudflare_ai.api_token' => null,
        'services.cerebras.key' => null, 'services.openrouter.key' => null,
        'services.groq.key' => 'test-key', 'services.groq.model' => 'openai/gpt-oss-120b',
    ]);
});

it('sends strict schemas for description and verification requests', function (string $file, string $field) {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);
    $client = new ListingAiClient;
    $client->json($client->prompt($file, []));
    Http::assertSent(fn ($request) => $request['response_format']['type'] === 'json_schema'
        && $request['response_format']['json_schema']['strict'] === true
        && isset($request['response_format']['json_schema']['schema']['properties'][$field]));
})->with([
    ['description_listing_prompt.txt', 'summary'],
    ['listing_verification_prompt.txt', 'issues'],
]);

it('retries empty truncated messages with a bounded larger budget', function () {
    Http::fakeSequence()
        ->push(['choices' => [['finish_reason' => 'length', 'message' => ['content' => null]]]])
        ->push(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => '{"issues":[]}']]]]);
    expect((new ListingAiClient)->json('You check generated listing fields', 1800))->toBe(['issues' => []]);
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request['max_tokens'] === 3600);
});

it('reports missing messages separately from HTTP failures', function () {
    Log::spy();
    Http::fake(['*' => Http::response(['model' => 'actual-model', 'choices' => [['finish_reason' => 'stop', 'message' => ['content' => null]]]])]);
    $client = new ListingAiClient;
    expect($client->json('Return JSON only.'))->toBeNull();
    expect(implode(' ', $client->errors()))->toContain('empty or missing message content')->not->toBe('groq returned HTTP 200.');
    Log::shouldHaveReceived('warning')->with('Listing AI response diagnostic.', Mockery::on(fn ($context) => $context['model'] === 'actual-model' && $context['content_type'] === 'null'));
});

it('lets the successful provider correct a validation failure', function () {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"ok":true}']]]])]);
    $client = new ListingAiClient;
    expect($client->json('Return JSON only.'))->toBe(['ok' => true]);
    expect($client->json("Return JSON only.\nValidation errors to fix:\nRewrite one sentence."))->toBe(['ok' => true]);
    Http::assertSentCount(2);
});

it('requires parameter support from OpenRouter endpoints', function () {
    config(['services.groq.key' => null, 'services.openrouter.key' => 'test-key']);
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"issues":[]}']]]])]);
    (new ListingAiClient)->json('You check generated listing fields');
    Http::assertSent(fn ($request) => $request['provider']['require_parameters'] === true && $request['response_format']['type'] === 'json_schema');
});

it('sends a native JSON schema to Gemini', function () {
    config(['services.groq.key' => null, 'services.google.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '{"issues":[]}']]]]]])]);
    expect((new ListingAiClient)->json('You check generated listing fields'))->toBe(['issues' => []]);
    Http::assertSent(fn ($request) => $request['generationConfig']['responseMimeType'] === 'application/json'
        && isset($request['generationConfig']['responseJsonSchema']['properties']['issues']));
});

it('keeps JSON mode for Groq models without configured strict support', function () {
    config(['services.groq.model' => 'custom-model']);
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => '{"issues":[]}']]]])]);
    (new ListingAiClient)->json('You check generated listing fields');
    Http::assertSent(fn ($request) => $request['response_format'] === ['type' => 'json_object']);
});

it('reads text blocks without mixing reasoning into JSON', function () {
    Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => [
        ['type' => 'reasoning', 'text' => 'Internal reasoning'],
        ['type' => 'text', 'text' => '{"issues":[]}'],
    ]]]]])]);
    expect((new ListingAiClient)->json('You check generated listing fields'))->toBe(['issues' => []]);
});
