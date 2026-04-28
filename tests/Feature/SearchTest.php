<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns only published product matches and category links from the shared api search', function () {
    $visibleProduct = Product::factory()->create([
        'name' => 'Alpha Toolkit',
        'tagline' => 'The fastest alpha workflow',
        'approved' => true,
        'is_published' => true,
        'votes_count' => 25,
    ]);

    $hiddenProduct = Product::factory()->create([
        'name' => 'Alpha Hidden',
        'approved' => false,
        'is_published' => true,
    ]);

    $category = Category::factory()->create([
        'name' => 'Alpha Category',
    ]);

    $visibleProduct->categories()->attach($category);

    $response = $this->getJson('/api/search?query=Alpha');

    $response->assertOk()
        ->assertJsonPath('query', 'Alpha');

    expect(collect($response->json('products'))->pluck('slug')->all())
        ->toContain($visibleProduct->slug)
        ->not->toContain($hiddenProduct->slug);

    expect($response->json('categories.0.url'))
        ->toBe(route('categories.show', ['category' => $category->slug]));
});

it('accepts the legacy q parameter for shared api search', function () {
    $product = Product::factory()->create([
        'name' => 'Beta Builder',
        'approved' => true,
        'is_published' => true,
    ]);

    $response = $this->getJson('/api/search?q=Beta');

    $response->assertOk()
        ->assertJsonPath('query', 'Beta');

    expect(collect($response->json('products'))->pluck('slug')->all())
        ->toContain($product->slug);
});

it('renders the article search page with current article routes and partials', function () {
    $user = User::factory()->create();
    $category = ArticleCategory::create(['name' => 'Guides', 'slug' => 'guides']);
    $tag = ArticleTag::create(['name' => 'Launch', 'slug' => 'launch']);

    $article = Article::create([
        'user_id' => $user->id,
        'title' => 'Searchable launch guide',
        'slug' => 'searchable-launch-guide',
        'content' => '<p>This launch guide covers search improvements.</p>',
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    $article->categories()->attach($category);
    $article->tags()->attach($tag);

    $response = $this->get(route('articles.search', ['query' => 'launch']));

    $response->assertOk()
        ->assertSee('Searchable launch guide')
        ->assertSee(route('articles.search'), false)
        ->assertSee(route('articles.category', $category->slug), false)
        ->assertSee(route('articles.tag', $tag->slug), false);
});
