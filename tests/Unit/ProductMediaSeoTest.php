<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Support\ProductMediaSeo;
use PHPUnit\Framework\TestCase;

class ProductMediaSeoTest extends TestCase
{
    public function test_screenshot_filename_for_url_is_descriptive(): void
    {
        self::assertSame(
            'openworklabs-homepage-screenshot.webp',
            ProductMediaSeo::screenshotFilenameForUrl('https://openworklabs.com/')
        );
    }

    public function test_product_media_filename_uses_product_slug_and_kind(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'slug' => 'openwork',
        ]);

        self::assertSame(
            'openwork-homepage-screenshot.webp',
            ProductMediaSeo::productMediaFilename($product, 'homepage-screenshot', 'webp')
        );
    }

    public function test_product_media_alt_text_for_screenshot_uses_product_context(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'tagline' => 'Open source Claude Cowork alternative for teams',
        ]);

        self::assertSame(
            'OpenWork homepage screenshot - Open source Claude Cowork alternative for teams',
            ProductMediaSeo::productMediaAltText($product, 'screenshot')
        );
    }
}
