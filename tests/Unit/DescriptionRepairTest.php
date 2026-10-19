<?php

use App\Services\DescriptionRewriterService;
use App\Services\ListingAiClient;

it('repairs only failed sections and supplies source even with populated evidence', function () {
    $source = 'Only pay for the emails you send not dormant subscribers.';
    $body = [
        'summary' => 'Example sends newsletters.', 'features' => ['Schedule newsletter delivery.'],
        'best_for' => '', 'not_for' => '', 'faq' => [],
        'comparisons' => [$source],
    ];
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('Write the listing body');
    $client->shouldReceive('json')->once()->withArgs(fn ($prompt, $tokens) => str_contains($prompt, $source) && $tokens === 3000)->andReturn($body);
    $client->shouldReceive('json')->once()->withArgs(fn ($prompt, $tokens, $task, $excluded) => $task === 'copy' && $excluded === ['groq'] && str_contains($prompt, 'Rewrite only these sections: comparisons') && str_contains($prompt, 'Previous body:'))->andReturn(array_replace($body, [
        'summary' => 'Changed summary should not replace valid content.',
        'comparisons' => ['Charges depend on sending volume rather than inactive contacts.'],
    ]));
    $client->shouldReceive('lastProvider')->andReturn('groq');
    app()->instance(ListingAiClient::class, $client);
    $result = app(DescriptionRewriterService::class)->generateFromFacts('Example', ['evidence' => ['product_name' => 'Example']], $source);
    expect($result['summary'])->toBe($body['summary']);
    expect($result['features'])->toBe($body['features']);
    expect($result['comparisons'])->toBe(['Charges depend on sending volume rather than inactive contacts.']);
});

it('cleans formatting locally across nested sections without another LLM call', function () {
    $body = [
        'summary' => '<p>Example sends **newsletters**—with templates; scheduling is included.</p>',
        'features' => ['# Campaign tools', '* Delivery reports'], 'best_for' => '',
        'not_for' => 'No <b>offline</b> access.',
        'editorial_qa' => [['question' => 'What does it do? 🚀', 'answer' => 'See [the guide](https://example.com/docs).']],
        'faq' => [['question' => 'Can I schedule?', 'answer' => 'Yes; select a date.']],
    ];
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('Write the listing body');
    $client->shouldReceive('json')->once()->andReturn($body);
    $client->shouldReceive('lastProvider')->andReturn('groq');
    app()->instance(ListingAiClient::class, $client);
    $result = app(DescriptionRewriterService::class)->generateFromFacts('Example', []);
    expect($result['summary'])->toBe('Example sends newsletters, with templates, scheduling is included.');
    expect($result['features'])->toBe(['Campaign tools', 'Delivery reports']);
    expect($result['not_for'])->toBe('No offline access.');
    expect($result['faq'][0]['answer'])->toBe('Yes, select a date.');
    expect($result['editorial_qa'][0]['answer'])->toBe('See the guide (https://example.com/docs).');
    expect($result)->not->toHaveKey('_generation_warnings');
});

it('retains a schema valid draft when the other provider cannot repair copied text', function () {
    $source = 'Calendar reminders contacts photos microphone accessibility and screen recordings are supported.';
    $body = ['summary' => 'Example displays desktop widgets.', 'features' => [], 'best_for' => '', 'not_for' => '', 'faq' => [['question' => 'Which permissions are supported?', 'answer' => $source]]];
    $client = Mockery::mock(ListingAiClient::class);
    $client->shouldReceive('prompt')->once()->andReturn('Write the listing body');
    $client->shouldReceive('json')->once()->with(Mockery::type('string'), 3000)->andReturn($body);
    $client->shouldReceive('json')->once()->with(Mockery::type('string'), 3000, 'copy', ['groq'])->andReturn(null);
    $client->shouldReceive('lastProvider')->andReturn('groq');
    $client->shouldReceive('errors')->andReturn(['Repair provider unavailable.']);
    app()->instance(ListingAiClient::class, $client);
    $result = app(DescriptionRewriterService::class)->generateFromFacts('Example', [], $source);
    expect($result['summary'])->toBe($body['summary']);
    expect($result['faq'])->toBe($body['faq']);
    expect(implode(' ', $result['_generation_warnings']))->toContain('eight consecutive source words', 'Repair provider unavailable');
});

it('does not remove meaningful programming punctuation while cleaning formatting', function () {
    $body = (new \App\Services\ListingTextCleaner)->clean(['features' => ['Supports C# and pointers like char*.', '<script>bad()</script><b>Safe label</b>']]);
    expect($body['features'])->toBe(['Supports C# and pointers like char*.', 'Safe label']);
});
