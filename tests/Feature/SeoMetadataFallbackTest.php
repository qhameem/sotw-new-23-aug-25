<?php

use App\Models\ArticleCategory;
use App\Models\PageMetaTag;

it('provides canonicals for public pages while removing tracking parameters', function () {
    ArticleCategory::create(['name' => 'Alternative to', 'slug' => 'alternative-to']);
    foreach (['/about', '/categories', '/articles/category/alternative-to'] as $path) {
        $this->get($path.'?utm_source=audit')->assertOk()
            ->assertSee('<link rel="canonical" href="'.url($path).'" />', false);
    }
    $this->get('/about')->assertOk()->assertSee('Learn how Software on the Web curates software');
});

it('retains numbered pages in the canonical fallback', function () {
    ArticleCategory::create(['name' => 'Guides', 'slug' => 'guides']);
    $this->get('/articles/category/guides?page=2&utm_source=audit')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/articles/category/guides').'?page=2" />', false);
});

it('reads updated configured homepage titles on subsequent requests', function () {
    $meta = PageMetaTag::create(['page_id' => 'home', 'meta_title' => 'First configured homepage title']);
    $this->get('/')->assertOk()->assertSee('<title>First configured homepage title</title>', false);
    $meta->update(['meta_title' => 'Updated configured homepage title']);
    $this->get('/')->assertOk()->assertSee('<title>Updated configured homepage title</title>', false);
});
