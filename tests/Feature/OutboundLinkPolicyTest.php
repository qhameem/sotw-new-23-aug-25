<?php

use App\Models\OutboundLinkRule;
use App\Services\OutboundLinkPolicyService;

it('defaults outbound article links to nofollow', function () {
    $service = app(OutboundLinkPolicyService::class);

    expect($service->relStringForUrl('https://example.com/docs', 'article'))
        ->toBe('nofollow noopener noreferrer');
});

it('applies dofollow exceptions from rules', function () {
    OutboundLinkRule::create([
        'name' => 'Trusted docs',
        'match_type' => OutboundLinkRule::MATCH_TYPE_DOMAIN,
        'pattern' => 'example.com',
        'source_scope' => OutboundLinkRule::SOURCE_SCOPE_ALL,
        'rel_nofollow' => false,
        'rel_ugc' => false,
        'rel_sponsored' => false,
        'rel_noopener' => true,
        'rel_noreferrer' => true,
        'priority' => 500,
        'is_active' => true,
    ]);

    $service = app(OutboundLinkPolicyService::class);
    $service->clearRuleCache();

    expect($service->relStringForUrl('https://example.com/docs', 'article'))
        ->toBe('noopener noreferrer');
});

it('sanitizes article html with the active policy', function () {
    OutboundLinkRule::create([
        'name' => 'Trusted docs',
        'match_type' => OutboundLinkRule::MATCH_TYPE_DOMAIN,
        'pattern' => 'example.com',
        'source_scope' => OutboundLinkRule::SOURCE_SCOPE_ALL,
        'rel_nofollow' => false,
        'rel_ugc' => false,
        'rel_sponsored' => false,
        'rel_noopener' => true,
        'rel_noreferrer' => true,
        'priority' => 500,
        'is_active' => true,
    ]);

    $service = app(OutboundLinkPolicyService::class);
    $service->clearRuleCache();

    $html = '<p><a href="https://example.com/docs">Docs</a> <a href="https://other-site.test/page">Other</a></p>';
    $sanitized = $service->sanitizeHtml($html, 'article');

    expect($sanitized)
        ->toContain('href="https://example.com/docs" rel="noopener noreferrer"')
        ->toContain('href="https://other-site.test/page" rel="nofollow noopener noreferrer"');
});
