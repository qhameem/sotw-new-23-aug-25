<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Services\CategoryClassifier;
use App\Services\LogoExtractorService;
use App\Services\ScreenshotService;
use App\Services\TechStackDetectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAutofillProgressTest extends TestCase
{
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
            $mock->shouldReceive('classify')->once()->andReturn([]);
        });
        $this->mock(TechStackDetectorService::class, function ($mock) {
            $mock->shouldReceive('detect')->once()->andReturn([]);
        });
        $this->mock(ScreenshotService::class, function ($mock) use (&$output) {
            $mock->shouldReceive('capture')->once()->andReturnUsing(function () use (&$output) {
                $this->assertStringContainsString('Refreshing website screenshot...', $output);

                return 'https://example.com/screenshot.png';
            });
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
        $this->assertLessThan(array_search('Refreshing website screenshot...', $messages), array_search('Classifying features and categories...', $messages));
        $this->assertSame('Classifying features and categories', $events[array_search('Finding additional logo options, pricing page, and socials...', $messages)]['next_message']);
        $this->assertSame('Done!', end($events)['message']);
        $this->assertSame('https://example.com/screenshot.png', end($events)['data']['screenshot_url']);
    }
}
