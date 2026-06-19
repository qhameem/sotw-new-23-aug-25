<?php

namespace Tests\Unit;

use App\Http\Controllers\ProductController;
use App\Services\BadgeService;
use App\Services\CategoryClassifier;
use App\Services\FaviconExtractorService;
use App\Services\LogoExtractorService;
use App\Services\NameExtractorService;
use App\Services\ProductLogoResolver;
use App\Services\RelatedProductService;
use App\Services\ScreenshotService;
use App\Services\SlugService;
use App\Services\TechStackDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAdditionalResourcesAutofillTest extends TestCase
{
    public function test_process_url_includes_additional_resources_in_ai_prompts_and_classification(): void
    {
        $capturedPrompts = [];
        $this->fakeAutofillRequests($capturedPrompts);
        $controller = $this->makeControllerWithClassifierExpectation();

        $response = $controller->processUrl(Request::create('/api/process-url', 'POST', [
            'url' => 'https://1.1.1.1',
            'name' => 'Acme',
            'fetch_content' => true,
            'additional_resources' => "https://8.8.8.8/pricing\nFocus on SOC 2 workflows and the audit trail.",
        ]));

        $this->assertSame(200, $response->getStatusCode(), $response->getContent());
        $this->assertCount(2, $capturedPrompts);

        foreach ($capturedPrompts as $prompt) {
            $this->assertStringContainsString('Focus on SOC 2 workflows and the audit trail.', $prompt);
            $this->assertStringContainsString('Additional resource URL: https://8.8.8.8/pricing', $prompt);
            $this->assertStringContainsString('Acme pricing and enterprise docs', $prompt);
        }

        $this->assertTrue(collect($capturedPrompts)->contains(function ($prompt) {
            return str_contains($prompt, 'LIMITATION RESEARCH:')
                && str_contains($prompt, 'Search-based limitation research:')
                && str_contains($prompt, 'steep learning curve')
                && str_contains($prompt, 'limited native integrations');
        }));
    }

    public function test_process_url_stream_includes_additional_resources_in_ai_prompts_and_classification(): void
    {
        $capturedPrompts = [];
        $this->fakeAutofillRequests($capturedPrompts);
        $controller = $this->makeControllerWithClassifierExpectation();

        $response = $controller->processUrlStream(Request::create('/api/process-url-stream', 'POST', [
            'url' => 'https://1.1.1.1',
            'name' => 'Acme',
            'fetch_content' => true,
            'additional_resources' => "https://8.8.8.8/pricing\nFocus on SOC 2 workflows and the audit trail.",
        ]));

        ob_start();
        $response->sendContent();
        ob_end_clean();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(2, $capturedPrompts);

        foreach ($capturedPrompts as $prompt) {
            $this->assertStringContainsString('Focus on SOC 2 workflows and the audit trail.', $prompt);
            $this->assertStringContainsString('Additional resource URL: https://8.8.8.8/pricing', $prompt);
            $this->assertStringContainsString('Acme pricing and enterprise docs', $prompt);
        }

        $this->assertTrue(collect($capturedPrompts)->contains(function ($prompt) {
            return str_contains($prompt, 'LIMITATION RESEARCH:')
                && str_contains($prompt, 'Search-based limitation research:')
                && str_contains($prompt, 'steep learning curve')
                && str_contains($prompt, 'limited native integrations');
        }));
    }

    private function fakeAutofillRequests(array &$capturedPrompts): void
    {
        config([
            'services.google.api_key' => null,
            'services.groq.key' => 'test-groq-key',
        ]);

        Http::fake(function ($request) use (&$capturedPrompts) {
            $url = $request->url();

            if ($url === 'https://1.1.1.1') {
                return Http::response(<<<'HTML'
                    <html>
                        <head>
                            <title>Acme</title>
                            <meta name="description" content="Security workflow software for vendor reviews.">
                        </head>
                        <body>
                            <h1>Security reviews with approvals and audit trails</h1>
                            <p>Acme helps teams review vendors and document compliance work.</p>
                        </body>
                    </html>
                HTML, 200);
            }

            if ($url === 'https://8.8.8.8/pricing') {
                return Http::response(<<<'HTML'
                    <html>
                        <head>
                            <title>Acme pricing and enterprise docs</title>
                            <meta name="description" content="Plans, SOC 2 support, and procurement workflows.">
                        </head>
                        <body>
                            <h1>Enterprise pricing</h1>
                            <h2>SOC 2 support</h2>
                            <p>Includes approval routing, audit logs, and procurement workflows for security teams.</p>
                        </body>
                    </html>
                HTML, 200);
            }

            if ($url === 'https://api.groq.com/openai/v1/chat/completions') {
                $prompt = (string) ($request['messages'][0]['content'] ?? '');
                $capturedPrompts[] = $prompt;

                if (str_contains($prompt, 'write two distinct taglines')) {
                    return Http::response([
                        'choices' => [
                            [
                                'message' => [
                                    'content' => json_encode([
                                        'tagline' => 'Security workflow software for vendor reviews',
                                        'product_page_tagline' => 'Manage vendor reviews with approvals, audit logs, and compliance workflows',
                                    ], JSON_UNESCAPED_SLASHES),
                                ],
                            ],
                        ],
                    ], 200);
                }

                return Http::response([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'summary' => 'Acme helps security teams manage vendor reviews with approval routing, audit logs, and compliance workflows.',
                                    'supporting_sentence' => 'It keeps procurement and security review work in one place instead of scattered docs and spreadsheets.',
                                    'what_it_is' => 'Acme is security workflow software for vendor reviews. It helps teams manage approvals, documentation, and audit trails.',
                                    'key_features' => [
                                        'Approval routing for vendor review workflows.',
                                        'Audit logs for compliance and security reviews.',
                                        'Shared procurement and documentation workflows.',
                                    ],
                                    'best_for' => [
                                        'Security teams reviewing vendors and documenting approvals.',
                                        'Procurement workflows that need clearer audit trails.',
                                    ],
                                    'pros' => [
                                        'Keeps vendor review work and approvals together.',
                                        'Makes audit trails easier to track.',
                                    ],
                                    'limitations' => [
                                        'Not clearly stated in the available source material.',
                                    ],
                                    'alternatives' => [],
                                    'integrations' => [],
                                    'faq' => [],
                                ], JSON_UNESCAPED_SLASHES),
                            ],
                        ],
                    ],
                ], 200);
            }

            if (str_starts_with($url, 'https://html.duckduckgo.com/html')) {
                return Http::response(<<<'HTML'
                    <html>
                        <body>
                            <div class="result">
                                <a class="result__a" href="https://reviews.example.com/acme-review">Acme review</a>
                                <div class="result__snippet">Reviewers mention a steep learning curve and limited native integrations for smaller teams.</div>
                            </div>
                            <div class="result">
                                <a class="result__a" href="https://docs.example.com/acme-faq">Acme FAQ</a>
                                <div class="result__snippet">FAQ and setup guide.</div>
                            </div>
                        </body>
                    </html>
                HTML, 200);
            }

            return Http::response('', 404);
        });
    }

    private function makeControllerWithClassifierExpectation(): ProductController
    {
        $categoryClassifier = $this->createMock(CategoryClassifier::class);
        $categoryClassifier->expects($this->once())
            ->method('classify')
            ->with($this->callback(function ($source) {
                $this->assertStringContainsString('ADDITIONAL RESOURCES:', $source);
                $this->assertStringContainsString('Admin notes:', $source);
                $this->assertStringContainsString('Focus on SOC 2 workflows and the audit trail.', $source);
                $this->assertStringContainsString('Additional resource URL: https://8.8.8.8/pricing', $source);
                $this->assertStringContainsString('Acme pricing and enterprise docs', $source);

                return true;
            }))
            ->willReturn([
                'categories' => [],
                'use_cases' => [],
                'best_for' => [],
                'pricing' => [],
                'platforms' => [],
            ]);

        $techStackDetector = $this->createMock(TechStackDetectorService::class);
        $techStackDetector->method('detect')->willReturn([]);

        $nameExtractor = $this->createMock(NameExtractorService::class);
        $nameExtractor->method('extract')->willReturn('Acme');

        $logoExtractor = $this->createMock(LogoExtractorService::class);
        $logoExtractor->method('extract')->willReturn(['https://1.1.1.1/logo.png']);

        $screenshotService = $this->createMock(ScreenshotService::class);
        $screenshotService->method('capture')->willReturn('https://1.1.1.1/screenshot.png');

        return new ProductController(
            $this->createMock(FaviconExtractorService::class),
            $this->createMock(SlugService::class),
            $techStackDetector,
            $nameExtractor,
            $logoExtractor,
            $categoryClassifier,
            $screenshotService,
            $this->createMock(BadgeService::class),
            $this->createMock(RelatedProductService::class),
            $this->createMock(ProductLogoResolver::class),
        );
    }
}
