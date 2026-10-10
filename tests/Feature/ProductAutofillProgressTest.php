<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Services\CategoryClassifier;
use App\Services\LogoExtractorService;
use App\Services\ProductLogoResolver;
use App\Services\ScreenshotService;
use App\Services\TechStackDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAutofillProgressTest extends TestCase
{
    public function test_initial_metadata_returns_name_for_a_reachable_site(): void
    {
        Http::fake(['*' => Http::response('<html><head><title>SaveGenie | Savings app</title><meta name="description" content="Track savings goals."></head><body><h1>SaveGenie</h1></body></html>', 200)]);
        $this->mock(ProductLogoResolver::class, fn ($mock) => $mock->shouldReceive('discoverReplacementLogoUrl')->andReturn(null));
        $this->mock(ScreenshotService::class, fn ($mock) => $mock->shouldReceive('capture')->andReturn(null));

        $response = app(ProductController::class)->fetchInitialMetadata(Request::create('/', 'POST', ['url' => 'https://8.8.8.8']));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('SaveGenie', $response->getData(true)['name']);
    }

    public function test_progress_announces_media_work_before_the_service_runs(): void
    {
        Http::fake(['*' => Http::response('<html><body>Example</body></html>')]);
        $output = '';
        $this->mock(LogoExtractorService::class, function ($mock) use (&$output) {
            $mock->shouldReceive('extract')->once()->andReturnUsing(function () use (&$output) {
                $this->assertStringContainsString('Finding additional logo options, pricing page, and socials...', $output);

                return ['https://example.com/logo.png'];
            });
        });
        $this->mock(CategoryClassifier::class, function ($mock) {
            $mock->shouldNotReceive('classify');
        });
        $this->mock(TechStackDetectorService::class, function ($mock) {
            $mock->shouldReceive('detect')->once()->andReturn([]);
        });
        $this->mock(ScreenshotService::class, function ($mock) {
            $mock->shouldNotReceive('capture');
        });

        $response = app(ProductController::class)->processUrlStream(Request::create('/', 'POST', [
            'url' => 'https://8.8.8.8',
            'name' => 'Example',
            'fetch_content' => false,
        ]));

        ob_start(function ($chunk) use (&$output) {
            $output .= $chunk;

            return '';
        }, 1);
        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }

        $events = array_map(fn ($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR), array_filter(explode("\n", trim($output))));
        $messages = array_column($events, 'message');
        $this->assertContains('Done!', $messages, $output);
        $this->assertLessThan(array_search('Classifying features and categories...', $messages), array_search('Finding additional logo options, pricing page, and socials...', $messages));
        $this->assertLessThan(array_search('Applying extracted data to the form...', $messages), array_search('Classifying features and categories...', $messages));
        $this->assertSame('Classifying features and categories', $events[array_search('Finding additional logo options, pricing page, and socials...', $messages)]['next_message']);
        $this->assertSame('Done!', end($events)['message']);
        $this->assertArrayNotHasKey('screenshot_url', end($events)['data']);
        $this->assertSame([], end($events)['data']['field_errors']);
        $this->assertNotContains('Refreshing website screenshot...', $messages);
    }
    public function test_failed_stream_reports_an_error_without_erasing_partial_fields(): void
    {
        foreach ([Http::response('Denied', 403), Http::response('<title>Just a moment...</title>', 200)] as $failure) {
            Http::fake(['*' => $failure]);
            $response = app(ProductController::class)->processUrlStream(Request::create('/', 'POST', ['url' => 'https://8.8.8.8', 'name' => 'Existing name']));
            $output = '';
            ob_start(function ($chunk) use (&$output) { $output .= $chunk; return ''; }, 1);
            try {
                $response->sendContent();
            } finally {
                ob_end_clean();
            }
            $events = array_map(fn ($line) => json_decode($line, true), array_filter(explode("\n", trim($output))));
            $data = end($events)['data'];
            $this->assertNotEmpty($data['error']);
            $this->assertArrayNotHasKey('categories', $data);
            $this->assertArrayNotHasKey('tagline', $data);
            $this->assertNotContains('Done!', array_column($events, 'message'));
        }
    }

    public function test_initial_metadata_rejects_challenge_pages_before_extracting_identity(): void
    {
        Http::fake(['*' => Http::response('<title>Just a moment...</title>')]);
        $response = app(ProductController::class)->fetchInitialMetadata(Request::create('/', 'POST', ['url' => 'https://8.8.8.8']));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('verification', $response->getData(true)['error']);
    }

}
