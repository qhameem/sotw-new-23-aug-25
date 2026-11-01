<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Services\ProductResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductResearchLinksTest extends TestCase
{
    use RefreshDatabase;

    private function researchProducts(): array
    {
        $products = Product::factory()->count(3)->create([
            'features' => ['Batch exports', 'Shared projects'], 'best_for_text' => 'Editing teams',
        ]);
        $source = $products[0];
        $source->update([
            'name' => 'Research Alpha',
            'slug' => 'research-alpha',
            'alternative_product_ids' => [$products[1]->id, $products[2]->id],
            'comparison_product_ids' => [$products[1]->id],
        ]);

        return [$source, $products[1], $products[2]];
    }

    public function test_hub_links_to_qualified_guides_and_has_a_canonical_and_footer_link(): void
    {
        [$source, $comparison] = $this->researchProducts();

        $this->get('/alternatives')->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Software alternatives and comparisons')
            ->assertSee('href="'.route('pseo.alternatives', $source->slug).'"', false)
            ->assertSee('href="'.\App\Support\ComparisonUrl::forSlugs($source->slug, $comparison->slug).'"', false)
            ->assertSee('<link rel="canonical" href="'.route('alternatives.index').'">', false)
            ->assertSee('Software alternatives</a>', false);
    }

    public function test_directory_pagination_preserves_canonical_urls_and_search(): void
    {
        $alternatives = Product::factory()->count(2)->create();
        Product::factory()->count(20)->create([
            'name' => 'Searchable guide',
            'alternative_product_ids' => $alternatives->pluck('id')->all(),
        ]);

        $response = $this->get('/alternatives?page=2&q=Searchable')->assertOk();
        $this->assertCount(2, $response->viewData('entries'));
        $response->assertSee('<link rel="canonical" href="'.route('alternatives.index', ['page' => 2]).'">', false)
            ->assertSee('noindex, follow')
            ->assertSee('Page 2')
            ->assertDontSee('Showing 1');
    }

    public function test_hub_omits_unpublished_products_and_weak_matches(): void
    {
        [$source] = $this->researchProducts();
        $source->update(['is_published' => false]);
        Product::factory()->create(['name' => 'Unrelated solitary product']);

        $response = $this->get('/alternatives')->assertOk();

        $this->assertCount(0, $response->viewData('entries'));
        $response->assertSee('noindex, follow');
    }

    public function test_search_filters_guides_and_does_not_create_indexable_search_pages(): void
    {
        [$source] = $this->researchProducts();

        $this->get('/alternatives?q=Research')->assertOk()
            ->assertSee($source->name)
            ->assertSee('noindex, follow');
        $this->get('/alternatives?q=unmatched-name')->assertOk()->assertSee('No matching guides');
        $this->get('/alternatives?page=999')->assertNotFound();
    }

    public function test_cached_recommendations_do_not_link_to_products_that_are_now_unpublished(): void
    {
        [$source, $comparison] = $this->researchProducts();
        $research = app(ProductResearchService::class);
        $this->assertNotEmpty($research->forProducts([$source]));
        $comparison->update(['is_published' => false]);

        $this->assertEmpty($research->forProducts([$source]));
    }

    public function test_article_links_are_based_on_explicit_local_product_references(): void
    {
        [$source] = $this->researchProducts();
        $article = new Article(['content' => '<a href="https://unrelated.example/product/'.$source->slug.'">Other site</a>']);
        $research = app(ProductResearchService::class);
        $this->assertEmpty($research->articleProducts($article));

        $article->content = '<a href="/product/'.$source->slug.'">Read about Alpha</a>';
        $this->assertSame([$source->id], $research->articleProducts($article)->pluck('id')->all());
        $html = Blade::render('<x-research-links :article="$article" />', compact('article'));
        $this->assertStringContainsString(route('pseo.alternatives', $source->slug), $html);
    }

    public function test_category_page_links_to_relevant_guides(): void
    {
        [$source, $comparison, $other] = $this->researchProducts();
        $category = Category::factory()->create(['name' => 'Research Tools', 'slug' => 'research-tools']);
        $type = Type::create(['name' => 'Software Categories']);
        $category->types()->attach($type);

        foreach ([$source, $comparison, $other] as $product) {
            $product->categories()->attach($category);
        }

        $this->get(route('categories.show', $category->slug))->assertOk()
            ->assertSee('Research Tools alternatives and comparisons')
            ->assertSee('href="'.route('pseo.alternatives', $source->slug).'"', false);
    }

    public function test_product_page_exposes_comparison_links(): void
    {
        [$source, $comparison] = $this->researchProducts();

        $this->get(route('products.show', $source->slug))->assertOk()
            ->assertSee('href="'.\App\Support\ComparisonUrl::forSlugs($source->slug, $comparison->slug).'"', false);
    }

    public function test_article_page_renders_contextual_research_links(): void
    {
        [$source] = $this->researchProducts();
        $article = Article::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'A software workflow',
            'slug' => 'a-software-workflow',
            'content' => '<p>Try <a href="/product/'.$source->slug.'">Research Alpha</a>.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('articles.show', $article))->assertOk()
            ->assertSee('Research software in this article')
            ->assertSee('href="'.route('pseo.alternatives', $source->slug).'"', false);
    }
}
