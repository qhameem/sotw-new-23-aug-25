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

    public function test_uploaded_first_image_uses_homepage_screenshot_filename(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'slug' => 'openwork',
        ]);

        self::assertSame(
            'openwork-homepage-screenshot.webp',
            ProductMediaSeo::productMediaFilename($product, 'image', 'webp', 1)
        );
    }

    public function test_uploaded_later_image_uses_product_screenshot_filename(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'slug' => 'openwork',
        ]);

        self::assertSame(
            'openwork-product-screenshot-2.webp',
            ProductMediaSeo::productMediaFilename($product, 'image', 'webp', 2)
        );
    }

    public function test_product_media_alt_text_for_homepage_screenshot_uses_product_context(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'product_page_tagline' => 'Open source Claude cowork alternative for teams',
        ]);

        self::assertSame(
            'OpenWork homepage showing Open source Claude cowork alternative for teams',
            ProductMediaSeo::productMediaAltText($product, 'image', 1)
        );
    }

    public function test_product_media_alt_text_for_additional_screenshot_uses_product_context(): void
    {
        $product = new Product([
            'name' => 'OpenWork',
            'product_page_tagline' => 'Open source Claude cowork alternative for teams',
        ]);

        self::assertSame(
            'OpenWork interface screenshot 2 showing Open source Claude cowork alternative for teams',
            ProductMediaSeo::productMediaAltText($product, 'image', 2)
        );
    }

    public function test_product_media_alt_text_is_limited_for_long_taglines(): void
    {
        $product = new Product([
            'name' => 'creafico - Content Analysis & Growth Insights',
            'product_page_tagline' => 'creafico is a content analysis and idea engine for short-form creators on TikTok, Instagram Reels and YouTube Shorts. Connect your account, add competitors, and Creafico scans real performance data to find the patterns behind what works.',
        ]);

        $altText = ProductMediaSeo::productMediaAltText($product, 'image', 1);

        self::assertLessThanOrEqual(180, strlen($altText));
        self::assertStringStartsWith('creafico - Content Analysis & Growth Insights homepage showing', $altText);
        self::assertStringNotContainsString('...', $altText);
    }

    public function test_product_media_alt_text_converts_long_action_tagline_into_visual_descriptor(): void
    {
        $product = new Product([
            'name' => 'SizzleAir',
            'product_page_tagline' => 'SizzleAir helps you identify and resolve thermal pressure issues before they slow down your MacBook Air, ensuring optimal performance and productivity.',
        ]);

        self::assertSame(
            'SizzleAir homepage showing MacBook Air thermal pressure monitoring',
            ProductMediaSeo::productMediaAltText($product, 'image', 1)
        );
    }

    public function test_product_media_alt_text_removes_generic_helpful_marketing_lead_ins(): void
    {
        $product = new Product([
            'name' => 'Linear',
            'product_page_tagline' => 'Linear helps teams build and launch products faster by automating workflows and streamlining collaboration.',
        ]);

        self::assertSame(
            'Linear homepage showing product planning and workflow automation',
            ProductMediaSeo::productMediaAltText($product, 'image', 1)
        );
    }
}
