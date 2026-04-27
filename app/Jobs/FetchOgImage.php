<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ScreenshotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FetchOgImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle(ScreenshotService $screenshotService): void
    {
        $this->logEvent('info', 'FetchOgImage job started.', [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_link' => $this->product->link,
        ]);

        try {
            $response = Http::get($this->product->link);
            $html = $response->body();

            $doc = new \DOMDocument();
            @$doc->loadHTML($html);

            $ogImage = null;
            foreach ($doc->getElementsByTagName('meta') as $meta) {
                if ($meta->getAttribute('property') === 'og:image') {
                    $ogImage = $meta->getAttribute('content');
                    break;
                }
            }

            if ($ogImage) {
                $ogImage = $this->resolveUrl($this->product->link, $ogImage);
                $this->logEvent('info', 'FetchOgImage found og:image meta tag.', [
                    'product_id' => $this->product->id,
                    'og_image_url' => $ogImage,
                ]);

                if ($ogImage !== $this->product->logo) {
                    $this->processImage($ogImage, 'og');
                } else {
                    $this->logEvent('info', 'FetchOgImage skipped og:image because it matches the product logo.', [
                        'product_id' => $this->product->id,
                        'og_image_url' => $ogImage,
                    ]);
                }
            } else {
                $this->logEvent('warning', 'FetchOgImage did not find og:image, falling back to screenshot capture.', [
                    'product_id' => $this->product->id,
                    'product_link' => $this->product->link,
                ]);
                $this->fallbackToScreenshot($screenshotService);
            }
        } catch (\Exception $e) {
            $this->logEvent('error', 'FetchOgImage failed while reading the product page, falling back to screenshot capture.', [
                'product_id' => $this->product->id,
                'product_link' => $this->product->link,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $this->fallbackToScreenshot($screenshotService);
        }
    }

    protected function processImage($imageUrl, $type): void
    {
        try {
            $response = Http::timeout(20)->get($imageUrl);
            if (!$response->successful()) {
                $this->logEvent('warning', 'FetchOgImage image download was unsuccessful.', [
                    'product_id' => $this->product->id,
                    'image_type' => $type,
                    'image_url' => $imageUrl,
                    'status' => $response->status(),
                ]);
                return;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (!str_starts_with($contentType, 'image/')) {
                $this->logEvent('warning', 'FetchOgImage image download did not return an image content type.', [
                    'product_id' => $this->product->id,
                    'image_type' => $type,
                    'image_url' => $imageUrl,
                    'content_type' => $contentType,
                ]);
                return;
            }

            $imageContents = $response->body();
            $filename = Str::slug($this->product->name) . '-' . $type . '.webp';
            $path = 'media/' . $filename;

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContents)->toWebp(80);
            Storage::disk('public')->put($path, (string) $image);

            $this->logEvent('info', 'FetchOgImage stored processed image.', [
                'product_id' => $this->product->id,
                'image_type' => $type,
                'source_url' => $imageUrl,
                'stored_path' => $path,
            ]);

            $this->createMediaRecord($path, $type);
        } catch (\Exception $e) {
            $this->logEvent('error', 'FetchOgImage failed while processing image.', [
                'product_id' => $this->product->id,
                'image_type' => $type,
                'image_url' => $imageUrl,
                'exception_class' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function fallbackToScreenshot(ScreenshotService $screenshotService): void
    {
        $this->logEvent('info', 'FetchOgImage screenshot fallback started.', [
            'product_id' => $this->product->id,
            'product_link' => $this->product->link,
        ]);

        $relativePath = $screenshotService->captureToStorage(
            $this->product->link,
            'media',
            Str::slug($this->product->name) . '-screenshot-' . $this->product->id . '.jpg'
        );

        if ($relativePath) {
            $this->logEvent('info', 'FetchOgImage screenshot fallback stored image.', [
                'product_id' => $this->product->id,
                'stored_path' => $relativePath,
            ]);
            $this->createMediaRecord($relativePath, 'screenshot');
        } else {
            $this->logEvent('error', 'FetchOgImage screenshot fallback did not produce an image.', [
                'product_id' => $this->product->id,
                'product_link' => $this->product->link,
            ]);
        }
    }

    protected function createMediaRecord($path, $type): void
    {
        $this->logEvent('info', 'FetchOgImage creating product media record.', [
            'product_id' => $this->product->id,
            'path' => $path,
            'type' => $type,
        ]);

        $this->product->media()->create([
            'path' => $path,
            'alt_text' => $this->product->name . ' – ' . $this->product->tagline,
            'type' => $type,
        ]);
    }

    protected function logEvent(string $level, string $message, array $context = []): void
    {
        match ($level) {
            'error' => Log::error($message, $context),
            'warning' => Log::warning($message, $context),
            default => Log::info($message, $context),
        };

        $payload = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        @file_put_contents(
            storage_path('logs/screenshot-debug.log'),
            json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    protected function resolveUrl(string $baseUrl, string $imageUrl): string
    {
        if (Str::startsWith($imageUrl, ['http://', 'https://'])) {
            return $imageUrl;
        }

        if (Str::startsWith($imageUrl, '//')) {
            return 'https:' . $imageUrl;
        }

        $base = parse_url($baseUrl);
        if (!$base || empty($base['scheme']) || empty($base['host'])) {
            return $imageUrl;
        }

        if (Str::startsWith($imageUrl, '/')) {
            return $base['scheme'] . '://' . $base['host'] . $imageUrl;
        }

        $basePath = $base['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($basePath)), '/');

        return $base['scheme'] . '://' . $base['host'] . ($directory ? $directory . '/' : '/') . ltrim($imageUrl, '/');
    }
}
