<?php

namespace Tests\Unit;

use App\Http\Controllers\ProductController;
use App\Services\ListingGenerationService;
use App\Services\LogoExtractorService;
use App\Services\TechStackDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAdditionalResourcesAutofillTest extends TestCase
{
    public function test_process_url_passes_labeled_pages_and_additional_resources_to_facts_pipeline(): void
    {
        $this->fakePages();
        $this->mock(ListingGenerationService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->withArgs(function ($name, $source) {
                $this->assertSame('Acme', $name);
                $this->assertStringContainsString('Source: https://1.1.1.1', $source);
                $this->assertStringContainsString('Source: https://1.1.1.1/pricing', $source);
                $this->assertStringContainsString('Acme pricing and enterprise docs', $source);
                $this->assertStringContainsString('Focus on SOC 2 workflows', $source);
                return true;
            })->andReturn(['generation_status' => 'draft', 'generation_noindex' => true, 'generation_review_required' => true]);
        });
        $this->mock(LogoExtractorService::class, fn ($mock) => $mock->shouldReceive('extract')->andReturn([]));
        $this->mock(TechStackDetectorService::class, fn ($mock) => $mock->shouldReceive('detect')->andReturn([]));

        $response = app(ProductController::class)->processUrl(Request::create('/api/process-url', 'POST', [
            'url' => 'https://1.1.1.1', 'name' => 'Acme', 'fetch_content' => true,
            'additional_resources' => 'Focus on SOC 2 workflows',
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('draft', $response->getData(true)['generation_status']);
    }

    public function test_stream_passes_the_same_sources_to_facts_pipeline(): void
    {
        $this->fakePages();
        $this->mock(ListingGenerationService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->withArgs(function ($name, $source) {
                $this->assertStringContainsString('Source: https://1.1.1.1/pricing', $source);
                return true;
            })->andReturn(['generation_status' => 'draft']);
        });
        $this->mock(LogoExtractorService::class, fn ($mock) => $mock->shouldReceive('extract')->andReturn([]));
        $this->mock(TechStackDetectorService::class, fn ($mock) => $mock->shouldReceive('detect')->andReturn([]));

        $response = app(ProductController::class)->processUrlStream(Request::create('/api/process-url-stream', 'POST', [
            'url' => 'https://1.1.1.1', 'name' => 'Acme', 'fetch_content' => true,
        ]));
        ob_start();
        ob_start();
        $response->sendContent();
        ob_end_flush();
        $output = ob_get_clean();
        $events = array_values(array_filter(array_map(fn ($line) => json_decode($line, true), explode("\n", $output))));
        $this->assertSame('draft', end($events)['data']['generation_status']);
    }

    private function fakePages(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://1.1.1.1') {
                return Http::response('<html><head><title>Acme</title></head><body><a href="/pricing">Pricing</a><p>Vendor reviews</p></body></html>', 200, ['Content-Type' => 'text/html']);
            }
            if ($request->url() === 'https://1.1.1.1/pricing') {
                return Http::response('<html><head><title>Acme pricing and enterprise docs</title></head><body>Plans for teams</body></html>', 200, ['Content-Type' => 'text/html']);
            }
            return Http::response('', 404);
        });
    }
}
