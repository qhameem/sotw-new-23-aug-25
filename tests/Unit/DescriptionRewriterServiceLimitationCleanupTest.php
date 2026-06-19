<?php

use App\Services\DescriptionRewriterService;
use Illuminate\Support\Facades\Http;

test('description rewriter removes placeholder limitations from html responses', function () {
    config([
        'services.google.api_key' => null,
        'services.groq.key' => 'test-groq-key',
    ]);

    Http::fake([
        'https://api.groq.com/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => implode("\n", [
                            '<p><strong>Acme helps teams manage security reviews with approvals and audit trails.</strong></p>',
                            '<p>It keeps review work and documentation in one workflow.</p>',
                            '<h2><strong>What is Acme?</strong></h2>',
                            '<p>Acme is security workflow software for vendor reviews. It organizes approvals and evidence collection.</p>',
                            '<h2><strong>What are the key features of Acme?</strong></h2>',
                            '<ul><li>Approval routing for security reviews.</li><li>Audit logs for documentation.</li><li>Shared workflow visibility.</li><li>Evidence collection support.</li><li>Review tracking across teams.</li></ul>',
                            '<h2><strong>Who is Acme best for?</strong></h2>',
                            '<ul><li>Security teams reviewing vendors.</li><li>Procurement teams managing approvals.</li><li>Operators documenting compliance work.</li></ul>',
                            '<h2><strong>What can you use Acme for?</strong></h2>',
                            '<ul><li>Running vendor reviews.</li><li>Documenting approval steps.</li><li>Tracking audit evidence.</li></ul>',
                            '<h2><strong>How does Acme compare to alternatives?</strong></h2>',
                            '<ul><li>Compared with spreadsheets, it keeps approvals and evidence together.</li><li>Compared with ad hoc docs, it provides a more structured review workflow.</li></ul>',
                            '<h2><strong>What integrations and ecosystem support does Acme offer?</strong></h2>',
                            '<ul><li>The source material does not clearly specify integrations.</li></ul>',
                            '<h2><strong>What are the pros and limitations of Acme?</strong></h2>',
                            '<ul><li><strong>Pros:</strong> Keeps approval steps and evidence in one place.</li><li><strong>Limitations:</strong> Not clearly stated in the available source material.</li></ul>',
                            '<h2><strong>Frequently asked questions about Acme</strong></h2>',
                            '<dl><dt><strong>What does Acme help with?</strong></dt><dd>It helps teams manage vendor reviews and compliance approvals.</dd><dt><strong>Who is Acme for?</strong></dt><dd>It is best for security and procurement teams.</dd></dl>',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = new DescriptionRewriterService();

    $result = $service->rewrite(
        'Acme',
        'Security workflow software for vendor reviews.',
        'Title: Acme'
    );

    expect($result)->toContain('<h2><strong>What are the pros of Acme?</strong></h2>');
    expect($result)->not->toContain('Not clearly stated in the available source material.');
    expect($result)->not->toContain('<strong>Limitations:</strong>');
});
