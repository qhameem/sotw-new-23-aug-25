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

                if ($ogImage !== $this->product->logo) {
                    $this->processImage($ogImage, 'og');
                }
            } else {
                $this->fallbackToScreenshot($screenshotService);
            }
        } catch (\Exception $e) {
            $this->fallbackToScreenshot($screenshotService);
        }
    }

    protected function processImage($imageUrl, $type): void
    {
        try {
            $response = Http::timeout(20)->get($imageUrl);
            if (!$response->successful()) {
                return;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            if (!str_starts_with($contentType, 'image/')) {
                return;
            }

            $imageContents = $response->body();
            $filename = Str::slug($this->product->name) . '-' . $type . '.webp';
            $path = 'media/' . $filename;

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imageContents)->toWebp(80);
            Storage::disk('public')->put($path, (string) $image);

            $this->createMediaRecord($path, $type);
        } catch (\Exception $e) {
            // Log error, and potentially fallback
        }
    }

    protected function fallbackToScreenshot(ScreenshotService $screenshotService): void
    {
        $relativePath = $screenshotService->captureToStorage(
            $this->product->link,
            'media',
            Str::slug($this->product->name) . '-screenshot-' . $this->product->id . '.jpg'
        );

        if ($relativePath) {
            $this->createMediaRecord($relativePath, 'screenshot');
        }
    }

    protected function createMediaRecord($path, $type): void
    {
        $this->product->media()->create([
            'path' => $path,
            'alt_text' => $this->product->name . ' – ' . $this->product->tagline,
            'type' => $type,
        ]);
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
