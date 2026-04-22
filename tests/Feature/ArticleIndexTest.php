<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publishedArticle(array $overrides = []): Article
{
    $author = $overrides['user'] ?? User::factory()->create();
    unset($overrides['user']);

    return Article::create(array_merge([
        'user_id' => $author->id,
        'title' => 'Article '.fake()->unique()->words(3, true),
        'slug' => fake()->unique()->slug(4),
        'content' => '<p>'.str_repeat('Thoughtful writing ', 60).'</p>',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'staff_pick' => false,
    ], $overrides));
}

it('renders the redesigned article index with sidebar collections', function () {
    config()->set('analytics.property_id', null);
    config()->set('analytics.service_account_credentials_json', null);

    $category = ArticleCategory::create(['name' => 'Guides', 'slug' => 'guides']);
    $featured = publishedArticle([
        'title' => 'Featured deep dive',
        'slug' => 'featured-deep-dive',
        'staff_pick' => true,
        'published_at' => now()->subDays(2),
    ]);
    $featured->categories()->attach($category);

    $latest = publishedArticle([
        'title' => 'Latest release notes',
        'slug' => 'latest-release-notes',
        'published_at' => now()->subHour(),
    ]);
    $latest->categories()->attach($category);

    $response = $this->get(route('articles.index'));

    $response->assertOk();
    $response->assertSee('Featured');
    $response->assertSee('Most Popular');
    $response->assertSee('Explore Topics');

    expect($response->viewData('feed'))->toBe('latest');
    expect($response->viewData('featuredPosts')->pluck('id')->all())->toBe([$featured->id]);
    expect($response->viewData('popularPosts')->pluck('id')->all())->toContain($latest->id);
    expect($response->viewData('topicCategories')->pluck('slug')->all())->toContain('guides');
});

it('can switch the main feed to featured articles', function () {
    $featured = publishedArticle([
        'title' => 'Featured article',
        'slug' => 'featured-article',
        'staff_pick' => true,
        'published_at' => now()->subDay(),
    ]);
    publishedArticle([
        'title' => 'Regular article',
        'slug' => 'regular-article',
        'published_at' => now()->subHours(2),
    ]);

    $response = $this->get(route('articles.index', ['view' => 'featured']));

    $posts = $response->viewData('posts');

    $response->assertOk();
    expect($response->viewData('feed'))->toBe('featured');
    expect($posts->pluck('id')->all())->toBe([$featured->id]);
});

it('falls back to recently published articles for the popular feed when analytics is unavailable', function () {
    config()->set('analytics.property_id', null);
    config()->set('analytics.service_account_credentials_json', null);

    $older = publishedArticle([
        'title' => 'Older article',
        'slug' => 'older-article',
        'published_at' => now()->subDays(3),
    ]);
    $newer = publishedArticle([
        'title' => 'Newer article',
        'slug' => 'newer-article',
        'published_at' => now()->subHour(),
    ]);

    $response = $this->get(route('articles.index', ['view' => 'popular']));

    $posts = $response->viewData('posts');

    $response->assertOk();
    expect($response->viewData('feed'))->toBe('popular');
    expect($posts->items())->toHaveCount(2);
    expect($posts->items()[0]->id)->toBe($newer->id);
    expect($posts->items()[1]->id)->toBe($older->id);
});
