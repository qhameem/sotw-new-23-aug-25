<?php

namespace Tests\Unit;

use App\Jobs\FetchOgImage;
use App\Models\Product;
use App\Services\ScreenshotService;
use Mockery;
use PHPUnit\Framework\TestCase;

class FetchOgImageTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_fallback_to_screenshot_creates_media_record_when_capture_succeeds(): void
    {
        $product = $this->makeProduct();
        $service = Mockery::mock(ScreenshotService::class);

        $service->shouldReceive('captureToStorage')
            ->once()
            ->with(
                'https://example.com',
                'media',
                'test-product-screenshot-42.jpg'
            )
            ->andReturn('media/test-product-screenshot-42.jpg');

        $job = new TestableFetchOgImage($product);
        $job->runFallback($service);

        self::assertSame([
            [
                'path' => 'media/test-product-screenshot-42.jpg',
                'type' => 'screenshot',
            ],
        ], $job->createdMedia);
    }

    public function test_fallback_to_screenshot_skips_media_record_when_capture_fails(): void
    {
        $product = $this->makeProduct();
        $service = Mockery::mock(ScreenshotService::class);

        $service->shouldReceive('captureToStorage')
            ->once()
            ->andReturnNull();

        $job = new TestableFetchOgImage($product);
        $job->runFallback($service);

        self::assertSame([], $job->createdMedia);
    }

    private function makeProduct(): Product
    {
        $product = new Product();
        $product->id = 42;
        $product->name = 'Test Product';
        $product->link = 'https://example.com';
        $product->tagline = 'Reliable screenshots';

        return $product;
    }
}

class TestableFetchOgImage extends FetchOgImage
{
    public array $createdMedia = [];

    public function runFallback(ScreenshotService $screenshotService): void
    {
        $this->fallbackToScreenshot($screenshotService);
    }

    protected function createMediaRecord($path, $type): void
    {
        $this->createdMedia[] = [
            'path' => $path,
            'type' => $type,
        ];
    }
}
