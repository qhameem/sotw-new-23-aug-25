<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductMedia;
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

    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function handle()
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
                if ($ogImage !== $this->product->logo) {
                    $this->processImage($ogImage, 'og');
                }
            } else {
                $this->fallbackToScreenshot();
            }
        } catch (\Exception $e) {
            $this->fallbackToScreenshot();
        }
    }

    protected function processImage($imageUrl, $type)
    {
        try {
            $imageContents = Http::get($imageUrl)->body();
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

    protected function fallbackToScreenshot()
    {
        // In a real application, you would use a service like screenshotapi.net or screenshotlayer.com
        // For this example, we'll just use a placeholder.
        $placeholderUrl = 'https://via.placeholder.com/1200x630.png?text=Screenshot+Not+Available';
        $this->processImage($placeholderUrl, 'screenshot');
    }

    protected function createMediaRecord($path, $type)
    {
        $this->product->media()->create([
            'path' => $path,
            'alt_text' => $this->product->name . ' – ' . $this->product->tagline,
            'type' => $type,
        ]);
    }
}
