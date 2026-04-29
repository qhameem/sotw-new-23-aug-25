<?php

namespace Tests\Unit;

use App\Http\Controllers\ProductController;
use App\Services\BadgeService;
use App\Services\CategoryClassifier;
use App\Services\FaviconExtractorService;
use App\Services\LogoExtractorService;
use App\Services\NameExtractorService;
use App\Services\RelatedProductService;
use App\Services\ScreenshotService;
use App\Services\SlugService;
use App\Services\TechStackDetectorService;
use DOMDocument;
use ReflectionMethod;
use Tests\TestCase;

class ProductAutofillLinkExtractionTest extends TestCase
{
    public function test_it_extracts_footer_social_and_resource_links_for_autofill(): void
    {
        $controller = $this->makeController();
        $document = new DOMDocument();
        @$document->loadHTML(<<<'HTML'
            <html>
                <body>
                    <a href="/blog">Blog</a>
                    <footer>
                        <a href="/pricing">Pricing</a>
                        <a href="https://x.com/examplehq">X</a>
                        <a href="https://github.com/examplehq/product">GitHub</a>
                        <a href="/docs">Docs</a>
                        <a href="https://www.linkedin.com/company/examplehq/">LinkedIn</a>
                    </footer>
                </body>
            </html>
        HTML);

        $links = $this->invokeExtractAutofillLinks($controller, $document, 'https://example.com');

        $this->assertSame('https://example.com/pricing', $links['pricing_page_url']);
        $this->assertSame('https://x.com/examplehq', $links['x_account']);
        $this->assertSame([
            'https://github.com/examplehq/product',
            'https://linkedin.com/company/examplehq',
        ], $links['maker_links']);
    }

    public function test_it_excludes_same_site_resource_subdomains_from_other_links(): void
    {
        $controller = $this->makeController();
        $document = new DOMDocument();
        @$document->loadHTML(<<<'HTML'
            <html>
                <body>
                    <a href="https://docs.example.com/getting-started">Documentation</a>
                    <a href="https://community.example.com">Community</a>
                    <a href="https://example.com">Home</a>
                </body>
            </html>
        HTML);

        $links = $this->invokeExtractAutofillLinks($controller, $document, 'https://example.com');

        $this->assertNull($links['pricing_page_url']);
        $this->assertNull($links['x_account']);
        $this->assertSame([], $links['maker_links']);
    }

    protected function invokeExtractAutofillLinks(ProductController $controller, DOMDocument $document, string $url): array
    {
        $method = new ReflectionMethod($controller, 'extractAutofillLinksFromDocument');
        $method->setAccessible(true);

        return $method->invoke($controller, $document, $url);
    }

    protected function makeController(): ProductController
    {
        return new ProductController(
            $this->createMock(FaviconExtractorService::class),
            $this->createMock(SlugService::class),
            $this->createMock(TechStackDetectorService::class),
            $this->createMock(NameExtractorService::class),
            $this->createMock(LogoExtractorService::class),
            $this->createMock(CategoryClassifier::class),
            $this->createMock(ScreenshotService::class),
            $this->createMock(BadgeService::class),
            $this->createMock(RelatedProductService::class),
        );
    }
}
