<?php

use App\Services\ProductPageMarkdown;
use App\Services\ProductSourceCollector;
use Illuminate\Support\Facades\Http;

it('preserves headings pricing tables lists and resolved links without boilerplate', function () {
    $html = '<html><head><meta charset="utf-8"><title>Example</title><style>noise</style></head><body>
        <nav>Navigation noise</nav><script>tracking noise</script><aside>Sidebar noise</aside>
        <main><h1>Email service</h1><p>Send newsletters for café owners.</p>
        <h2>Pricing</h2><table><tr><th>Plan</th><th>Price</th><th>Limit</th></tr>
        <tr><td>Starter</td><td>$47/mo</td><td>5,000 emails</td></tr></table>
        <h2>Features</h2><ul><li>Automatic follow-ups</li><li>Custom domains</li></ul>
        <p><a href="../docs/setup">Setup guide</a> <a href="/login">Open dashboard</a></p>
        <div hidden>Hidden noise</div><a href="javascript:alert(1)">Unsafe link</a></main><footer>Footer noise</footer></body></html>';
    $markdown = (new ProductPageMarkdown)->convert($html, 'https://example.com/product/overview');
    expect($markdown)->toContain('# Email service', '## Pricing', '| Plan | Price | Limit |', '| --- | --- | --- |', '| Starter | $47/mo | 5,000 emails |', '- Automatic follow-ups', 'café', '[Setup guide](https://example.com/docs/setup)', '[Open dashboard](https://example.com/login)');
    expect($markdown)->not->toContain('Navigation noise', 'tracking noise', 'Sidebar noise', 'Hidden noise', 'Footer noise', 'javascript:');
});

it('retains late pricing sections when introductory content exceeds the budget', function () {
    $converter = new ProductPageMarkdown;
    $markdown = $converter->convert('<h1>Product</h1><p>'.str_repeat('General introductory copy. ', 1500).'</p><h2>Pricing</h2><table><tr><th>Plan</th><th>Price</th></tr><tr><td>Pro</td><td>$97/mo</td></tr></table><h2>Features</h2><ul><li>Campaign scheduling</li></ul>', 'https://example.com');
    $limited = $converter->limit($markdown, 2500);
    expect(mb_strlen($limited))->toBeLessThanOrEqual(2500);
    expect($limited)->toContain('## Pricing', '| Pro | $97/mo |', 'Campaign scheduling');
});

it('allocates source context across pages and retains source URLs', function () {
    Http::fake([
        'https://8.8.8.8/pricing' => Http::response('<h1>Pricing</h1><table><tr><th>Plan</th><th>Cost</th></tr><tr><td>Paid</td><td>$47/mo</td></tr></table>', 200, ['Content-Type' => 'text/html']),
        'https://8.8.8.8/docs' => Http::response('<h1>Documentation</h1><p>Use the API to send campaigns.</p>', 200, ['Content-Type' => 'text/html']),
        '*' => Http::response('', 404),
    ]);
    $source = (new ProductSourceCollector)->collect('https://8.8.8.8', '<h1>Example</h1><p>'.str_repeat('Introduction. ', 4000).'</p><h2>Features</h2><p>Personalize campaigns.</p>');
    expect(mb_strlen($source))->toBeLessThanOrEqual(24000);
    expect($source)->toContain('Source: https://8.8.8.8', 'Source: https://8.8.8.8/pricing', '| Paid | $47/mo |', 'Source: https://8.8.8.8/docs', 'Use the API to send campaigns.', 'Personalize campaigns.');
});

it('handles empty pages malformed HTML and plain additional resources', function () {
    $converter = new ProductPageMarkdown;
    expect($converter->convert('', 'https://8.8.8.8'))->toBe('');
    expect($converter->convert('<h2>Features</h2><p>Broken <b>markup', 'https://8.8.8.8'))->toContain('## Features', 'Broken markup');
    Http::fake(['*' => Http::response('', 404)]);
    $source = (new ProductSourceCollector)->collect('https://8.8.8.8', '<h1>Example</h1>', 'Plain resource details.');
    expect($source)->toContain('Source: Additional resources', 'Plain resource details.');
});
