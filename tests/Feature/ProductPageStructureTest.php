<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageStructureTest extends TestCase
{
    use RefreshDatabase;

    private function pageXPath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($dom);
    }

    public function test_editorial_sections_move_into_rows_without_losing_other_content(): void
    {
        $product = Product::factory()->create([
            'description' => '<p>Public introduction.</p><p>Second introduction.</p>'
                .'<h2>Problem</h2><p>Repeated manual work.</p>'
                .'<h2>Solution</h2><p>Automates the workflow.</p>'
                .'<h2>What makes it unique</h2><p>Works entirely offline.</p>'
                .'<h2>Key Features</h2><ul><li>Batch processing</li><li>Local export</li></ul>'
                .'<h2>Top Use Cases</h2><ul><li>Preparing reports</li></ul>'
                .'<h2>Integrations</h2><p>Imports CSV files.</p>',
            'best_for_text' => 'Small research teams.',
            'faq' => [['question' => 'Does it work offline?', 'answer' => 'Yes, processing stays local.']],
        ]);
        $response = $this->get(route('products.show', $product))->assertOk();
        $xpath = $this->pageXPath($response->getContent());
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertStringContainsString('Repeated manual work.', $xpath->evaluate('string(//*[@id="overview"])'));
        $this->assertStringContainsString('Small research teams.', $xpath->evaluate('string(//*[@id="overview"])'));
        $this->assertSame(1, $xpath->query('//*[@id="features"]//li[contains(., "Batch processing")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="use-cases"]//li[contains(., "Preparing reports")]')->length);
        $this->assertStringContainsString('Imports CSV files.', $xpath->evaluate('string(//*[@id="editorial-details"])'));
        $this->assertStringNotContainsString('Repeated manual work.', $xpath->evaluate('string(//*[@id="editorial-details"])'));
        $this->assertSame(1, $xpath->query('//*[@id="faq"]//details')->length);
    }

    public function test_details_keep_all_audience_categories_and_do_not_claim_zero_is_free(): void
    {
        $product = Product::factory()->create(['price' => 0, 'description' => '<p>Minimal product.</p>']);
        $type = Type::create(['name' => 'Best for']);
        foreach (['Researchers', 'Designers', 'Agencies'] as $name) {
            $category = Category::factory()->create(['name' => $name]);
            $category->types()->attach($type);
            $product->categories()->attach($category);
        }
        $response = $this->get(route('products.show', $product))->assertOk();
        $xpath = $this->pageXPath($response->getContent());
        $details = $xpath->evaluate('string(//*[@id="details"])');
        foreach (['Researchers', 'Designers', 'Agencies'] as $name) {
            $this->assertStringContainsString($name, $details);
        }
        $this->assertStringNotContainsString('USD 0.00', $details);
        $this->assertSame(0, $xpath->query('//*[@id="overview"]//h3[text()="Problem"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="use-cases"]')->length);
    }

    public function test_cover_precedes_about_and_gallery_has_enlarge_controls(): void
    {
        $product = Product::factory()->create(['description' => '<p>Media introduction.</p>']);
        foreach (['first', 'second'] as $name) {
            $product->media()->create(['path' => 'products/'.$name.'.png', 'type' => 'screenshot', 'alt_text' => $name.' screenshot']);
        }
        $html = $this->get(route('products.show', $product))->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'id="about"'), strpos($html, 'class="product-cover"'));
        $xpath = $this->pageXPath($html);
        $this->assertSame(2, $xpath->query('//*[@id="gallery"]//button[starts-with(@aria-label, "Enlarge")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="gallery"]//img[@alt="second screenshot"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="gallery"]//*[@aria-label="Product gallery"]')->length);
    }

    public function test_fact_cohort_keeps_legacy_editorial_hidden(): void
    {
        $product = Product::factory()->create([
            'description_format' => 'facts', 'product_facts' => ['Published local fact'],
            'description' => '<p>Hidden old description</p><h2>Problem</h2><p>Hidden old problem</p>',
        ]);
        $xpath = $this->pageXPath($this->get(route('products.show', $product))->assertOk()->getContent());
        $this->assertStringContainsString('Published local fact', $xpath->evaluate('string(//*[@id="about"])'));
        $this->assertSame(0, $xpath->query('//*[@id="editorial-details"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="overview"]')->length);
    }

    public function test_heading_first_description_does_not_turn_problem_into_intro(): void
    {
        $product = Product::factory()->create([
            'description' => '<div><h2>Problem</h2><p>Manual reporting is slow.</p><h2>Solution</h2><p>Automated reporting.</p><h2>Frequently asked questions</h2><dl><dt>Is it automatic?</dt><dd>Yes.</dd></dl></div>',
        ]);
        $xpath = $this->pageXPath($this->get(route('products.show', $product))->assertOk()->getContent());
        $this->assertStringContainsString('Manual reporting is slow.', $xpath->evaluate('string(//*[@id="overview"])'));
        $this->assertStringContainsString('Automated reporting.', $xpath->evaluate('string(//*[@id="overview"])'));
        $this->assertSame(0, $xpath->query('//*[@id="about"]')->length);
        $this->assertStringContainsString('Is it automatic?', $xpath->evaluate('string(//*[@id="editorial-details"])'));
    }
}
