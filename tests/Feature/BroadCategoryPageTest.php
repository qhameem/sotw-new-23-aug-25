<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Services\CategoryNavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadCategoryPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $type = Type::create([
            'name' => 'Software Categories',
            'description' => 'Software taxonomy',
        ]);

        $category = Category::create([
            'name' => 'AI Tools',
            'slug' => 'ai-tools',
            'description' => 'Artificial intelligence tools.',
            'meta_description' => 'Browse artificial intelligence tools.',
        ]);

        $category->types()->attach($type);

        $product = Product::factory()->create([
            'name' => 'Broad Category Product',
            'slug' => 'broad-category-product',
            'approved' => true,
            'is_published' => true,
            'published_at' => now(),
            'votes_count' => 1,
        ]);

        $product->categories()->attach($category);
    }

    public function test_header_renders_crawlable_broad_category_links(): void
    {
        $html = view('components.category-navigation-row', [
            'categoryNavigationSummaries' => app(CategoryNavigationService::class)->getMenuGroupSummaries(),
        ])->render();

        $this->assertStringContainsString(route('software-groups.show', ['group' => 'ai-automation']), $html);
        $this->assertStringContainsString('AI &amp; Automation', $html);
    }

    public function test_each_broad_category_page_is_indexable_and_links_to_subcategories(): void
    {
        $group = app(CategoryNavigationService::class)->getBroadGroup('ai-automation');

        $response = $this->get(route('software-groups.show', ['group' => 'ai-automation']));

        $response->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('software-groups.show', ['group' => 'ai-automation']).'">', false)
            ->assertSee('<h1', false)
            ->assertSee('aria-label="Browse software"', false)
            ->assertDontSee('x-data="upvote(', false)
            ->assertDontSee('>New</span>', false)
            ->assertDontSee('>Rising</span>', false)
            ->assertDontSee('>Popular</span>', false)
            ->assertSee($group['items'][0]['url'], false);
    }

    public function test_unknown_broad_category_returns_not_found(): void
    {
        $this->get('/software/not-a-real-group')->assertNotFound();
    }

    public function test_other_group_has_a_stable_landing_page(): void
    {
        $this->get(route('software-groups.show', ['group' => 'other']))
            ->assertOk()
            ->assertSee('Other Software &amp; Tools', false);
    }
}
