<?php

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleRevision;
use App\Models\ArticleTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function articlePayload(ArticleCategory $category, ArticleTag $tag, array $overrides = []): array
{
    return array_merge([
        'title' => 'My first article',
        'slug' => 'my-first-article',
        'content' => '<p>Hello from the editor.</p>',
        'status' => 'draft',
        'meta_title' => 'My first article',
        'meta_description' => 'A short summary for search results.',
        'meta_keywords' => 'articles, testing',
        'og_title' => 'OG title',
        'og_description' => 'OG description',
        'og_image' => 'https://example.com/image.jpg',
        'og_url' => 'https://example.com/articles/my-first-article',
        'twitter_card' => 'summary_large_image',
        'twitter_title' => 'Twitter title',
        'twitter_description' => 'Twitter description',
        'featured_image_path' => 'articles/example.webp',
        'categories' => [$category->id],
        'tags' => [$tag->id],
    ], $overrides);
}

it('autosaves a new author draft and returns editor urls', function () {
    $user = User::factory()->create();
    $category = ArticleCategory::create(['name' => 'Guides', 'slug' => 'guides']);

    $response = $this
        ->actingAs($user)
        ->post(route('articles.editor.autosave'), [
            'context' => 'author',
            'title' => 'Autosaved draft',
            'content' => '<p>Draft body</p>',
            'categories' => [$category->id],
        ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'article_id',
            'edit_url',
            'update_url',
            'preview_url',
            'current_status',
        ]);

    $article = Article::firstOrFail();

    expect($article->title)->toBe('Autosaved draft');
    expect($article->status)->toBe('draft');
    expect($article->revisions()->count())->toBe(0);
});

it('creates revisions on save and can restore an older revision', function () {
    $user = User::factory()->create();
    $category = ArticleCategory::create(['name' => 'Guides', 'slug' => 'guides']);
    $tag = ArticleTag::create(['name' => 'Testing', 'slug' => 'testing']);

    $this->actingAs($user)->post(route('articles.store'), articlePayload($category, $tag))
        ->assertRedirect();

    $article = Article::firstOrFail();

    $this->actingAs($user)->put(
        route('articles.update', ['article' => $article->id]),
        articlePayload($category, $tag, [
            'title' => 'Updated article title',
            'slug' => 'updated-article-title',
            'content' => '<p>Updated body.</p>',
        ])
    )->assertRedirect();

    expect(ArticleRevision::count())->toBe(2);

    $originalRevision = ArticleRevision::orderBy('id')->firstOrFail();

    $this->actingAs($user)
        ->post(route('articles.revisions.restore', ['revision' => $originalRevision->id]))
        ->assertRedirect();

    $article->refresh();

    expect($article->title)->toBe('My first article');
    expect($article->slug)->toBe('my-first-article');
    expect($article->content)->toContain('Hello from the editor.');
    expect($article->revisions()->count())->toBe(4);
});
