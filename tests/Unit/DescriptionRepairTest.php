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
    $client->shouldReceive('json')->once()->withArgs(fn ($prompt, $tokens) => str_contains($prompt, 'Rewrite only these sections: comparisons') && str_contains($prompt, 'Previous body:'))->andReturn(array_replace($body, [
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
