<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductMediaSeo;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditProductImageSeo extends Command
{
    protected $signature = 'seo:audit-product-images {product? : Product slug} {--limit=20 : Number of products to inspect when no slug is provided}';

    protected $description = 'Audit product image SEO signals such as filenames, alt text, WebP usage, and sitemap image readiness.';

    public function handle(): int
    {
        $query = Product::query()
            ->with('media')
            ->where('approved', true)
            ->where('is_published', true);

        if ($slug = $this->argument('product')) {
            $query->where('slug', $slug);
        } else {
            $query->limit((int) $this->option('limit'));
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('No matching published products were found.');

            return self::FAILURE;
        }

        foreach ($products as $product) {
            $this->line('');
            $this->info($product->name . ' [' . $product->slug . ']');
            $this->line('URL: ' . route('products.show', $product->slug));

            $imageObjects = $product->seoImageObjects();
            $this->line('Sitemap/JSON-LD image candidates: ' . count($imageObjects));

            if ($imageObjects === []) {
                $this->warn(' - No image objects detected for sitemap/JSON-LD.');
            }

            foreach ($product->media as $index => $media) {
                $filename = basename((string) $media->path);
                $issues = [];

                if (!ProductMediaSeo::isSeoFriendlyFilename((string) $media->path)) {
                    $issues[] = 'filename is generic';
                }

                if (!Str::endsWith(strtolower($filename), '.webp')) {
                    $issues[] = 'not webp';
                }

                $altText = trim((string) $media->alt_text);
                if ($altText === '') {
                    $issues[] = 'missing alt text';
                } elseif (Str::endsWith(strtolower($altText), ' media')) {
                    $issues[] = 'generic alt text';
                }

                if ($issues === []) {
                    $this->line(' - PASS: ' . $filename . ' | ' . $altText);
                } else {
                    $this->warn(' - WARN: ' . $filename . ' | ' . implode(', ', $issues));
                }
            }
        }

        $this->line('');
        $this->comment('Sitewide checks to keep enabled:');
        $this->line(' - robots meta should include max-image-preview:large');
        $this->line(' - product JSON-LD should include image URLs');
        $this->line(' - sitemap product URLs should include image entries');

        return self::SUCCESS;
    }
}
