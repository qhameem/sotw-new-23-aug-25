<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductStructuredData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_graph_connects_canonical_page_application_breadcrumbs_and_publisher(): void
    {
        $product = Product::factory()->create(['logo' => 'logos/example.png']);
        $category = Category::factory()->create();
        $graph = collect(app(ProductStructuredData::class)->graph($product, [], $category)['@graph'])->keyBy('@type');
        $canonical = route('products.show', $product->slug);

        $this->assertSame($canonical, $graph['WebPage']['url']);
        $this->assertSame($graph['SoftwareApplication']['@id'], $graph['WebPage']['mainEntity']['@id']);
        $this->assertSame($graph['WebPage']['@id'], $graph['SoftwareApplication']['mainEntityOfPage']['@id']);
        $this->assertSame($graph['WebSite']['@id'], $graph['WebPage']['isPartOf']['@id']);
        $this->assertSame($graph['Organization']['@id'], $graph['WebPage']['publisher']['@id']);
        $this->assertSame($graph['BreadcrumbList']['@id'], $graph['WebPage']['breadcrumb']['@id']);
        $this->assertSame($category->publicUrl(), $graph['BreadcrumbList']['itemListElement'][1]['item']);
        $this->assertSame($canonical, $graph['BreadcrumbList']['itemListElement'][2]['item']);
        $this->assertSame($product->seoImageUrls()[0], $graph['WebPage']['primaryImageOfPage']['url']);
        $this->assertArrayNotHasKey('width', $graph['WebPage']['primaryImageOfPage']);
        $this->assertFileExists(public_path('favicon/android-chrome-512x512.png'));
    }

    public function test_only_supplied_visible_faq_appears_and_empty_faq_is_omitted(): void
    {
        $product = Product::factory()->create(['faq' => [['question' => 'Hidden raw question', 'answer' => 'Hidden raw answer']]]);
        $service = app(ProductStructuredData::class);
        $this->assertNotContains('FAQPage', array_column($service->graph($product)['@graph'], '@type'));
        $faq = [['question' => 'Visible question?', 'answer' => 'Visible answer.']];
        $graph = collect($service->graph($product, $faq)['@graph'])->keyBy('@type');
        $this->assertSame('Visible question?', $graph['FAQPage']['mainEntity'][0]['name']);
        $this->assertSame('Visible answer.', $graph['FAQPage']['mainEntity'][0]['acceptedAnswer']['text']);
        $this->assertSame($graph['FAQPage']['@id'], $graph['WebPage']['hasPart']['@id']);
    }

    public function test_default_zero_price_does_not_claim_free_or_invent_ratings_or_creator(): void
    {
        $product = Product::factory()->create(['price' => 0]);
        $application = collect(app(ProductStructuredData::class)->graph($product)['@graph'])->firstWhere('@type', 'SoftwareApplication');
        foreach (['offers', 'aggregateRating', 'review', 'author', 'creator'] as $key) {
            $this->assertArrayNotHasKey($key, $application);
        }
        $product->update(['price' => 19.99, 'currency' => 'usd']);
        $application = collect(app(ProductStructuredData::class)->graph($product)['@graph'])->firstWhere('@type', 'SoftwareApplication');
        $this->assertSame('19.99', $application['offers']['price']);
        $this->assertSame('USD', $application['offers']['priceCurrency']);
    }

    public function test_json_ld_cannot_be_terminated_by_record_or_faq_text(): void
    {
        $hostile = '</script><script>alert("schema")</script>';
        $product = Product::factory()->create(['name' => $hostile, 'description' => '<p>Public description</p>']);
        $html = view('products.partials._json-ld-product', [
            'product' => $product,
            'productFaqItems' => [['question' => $hostile, 'answer' => 'Answer & details']],
        ])->render();

        $this->assertSame(1, substr_count($html, '</script>'));
        $this->assertStringNotContainsString('<script>alert', $html);
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);
        $graph = collect(json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR)['@graph'])->keyBy('@type');
        $this->assertSame($hostile, $graph['SoftwareApplication']['name']);
        $this->assertSame($hostile, $graph['FAQPage']['mainEntity'][0]['name']);
        $this->assertSame('Answer & details', $graph['FAQPage']['mainEntity'][0]['acceptedAnswer']['text']);
    }
}
