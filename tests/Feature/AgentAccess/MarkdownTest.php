<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Services\Markdown\Registry;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

it('negotiates markdown only when explicitly preferred', function (string $accept, bool $markdown) {
    $response = $this->get('/about', ['Accept' => $accept])->assertOk();
    expect($response->headers->get('Content-Type'))->toContain($markdown ? 'text/markdown' : 'text/html');
    expect($response->getVary())->toContain('Accept');
})->with([
    ['text/markdown', true], ['*/*', false], ['', false], ['text/html', false],
    ['text/markdown;q=0.8,text/html;q=0.9', false],
    ['text/html;q=0.7,text/markdown;q=0.8', true],
    ['text/markdown;q=1,text/html;q=1', false],
    ['text/markdown;q=0', false], ['text/*', false],
    ['text/markdown;q=0.8,*/*;q=0.9', false],
    ['text/markdown;q=0.9,text/*;q=0.8', true],
]);

it('serves suffix backend head and preflight consistently', function () {
    $get = $this->get('/about.md')->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=utf-8')
        ->assertHeader('X-Robots-Tag', 'noindex, follow')->assertHeader('Access-Control-Allow-Origin', '*')
        ->assertSee('# About', false)->assertDontSee('<html', false);
    expect($get->headers->get('Cache-Control'))->toContain('public', 's-maxage=3600');
    expect($get->headers->get('Link'))->toContain('rel="canonical"', '/llms.txt', '/llms-full.txt', '/ai.txt');
    expect($get->headers->get('X-Markdown-Tokens'))->toBe((string) ceil(mb_strlen($get->getContent()) / 4));
    expect($this->get('/api/md/about')->getContent())->toBe($get->getContent());
    $head = $this->head('/about.md')->assertOk();
    expect($head->getContent())->toBe('');
    foreach (['Content-Type', 'X-Markdown-Tokens', 'Content-Location', 'Cache-Control'] as $header) {
        expect($head->headers->get($header))->toBe($get->headers->get($header));
    }
    $this->options('/about.md')->assertNoContent()->assertHeader('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
});

it('returns only markdown for unknown or excluded paths including authenticated owners', function () {
    $this->actingAs(User::factory()->create());
    foreach (['/does-not-exist.md', '/admin.md', '/api/md/admin/products', '/api/md/dashboard', '/api/md/login', '/api/md/articles/create', '/api/md/tools/launch-readiness-checker/results/secret'] as $path) {
        $this->get($path)->assertNotFound()->assertHeader('Content-Type', 'text/markdown; charset=utf-8')->assertDontSee('<html', false);
    }
});

it('renders published products without personal data or mutations', function () {
    $p = Product::factory()->create(['name' => 'Visible Tool', 'slug' => 'visible-tool', 'description' => '<h2>Features</h2><p>Useful content.</p><script>secret()</script>', 'proposed_description' => 'Private proposed edit']);
    $before = $p->fresh()->getRawOriginal();
    $guest = $this->get('/product/visible-tool.md')->assertOk()->assertSee('# Visible Tool', false)->assertSee('Useful content.')->assertDontSee('Private proposed edit')->assertDontSee('secret()');
    $this->actingAs($p->user);
    expect($this->get('/product/visible-tool.md')->getContent())->toBe($guest->getContent());
    expect($p->fresh()->getRawOriginal())->toBe($before);
    foreach ([['approved' => false], ['is_published' => false], ['generation_noindex' => true], ['published_at' => now()->addDays(2)], ['generation_status' => 'draft']] as $attributes) {
        $p->update(['approved' => true, 'is_published' => true, 'generation_noindex' => false, 'published_at' => now(), 'generation_status' => 'published', ...$attributes]);
        $this->get('/product/visible-tool.md')->assertNotFound();
    }
});

it('renders published articles and category data', function () {
    $author = User::factory()->create();
    $article = Article::create(['user_id' => $author->id, 'title' => 'Actual article', 'slug' => 'actual-article', 'content' => '<h2>Details</h2><p>Actual article body <a href="/about">about</a>.</p>', 'status' => 'published', 'published_at' => now()->subDay()]);
    $this->get('/articles/actual-article.md')->assertOk()->assertSee('# Actual article', false)->assertSee('Actual article body')->assertSee(config('app.url').'/about', false);
    $this->get('/articles.md')->assertOk()->assertSee('Actual article');
    $article->update(['status' => 'draft']);
    $this->get('/articles/actual-article.md')->assertNotFound();
    $type = Type::firstOrCreate(['name' => 'Software Categories']);
    $category = Category::factory()->create(['name' => 'Actual Category', 'slug' => 'actual-category']);
    $category->types()->sync([$type->id]);
    Product::factory()->count(3)->create()->each(fn ($p) => $p->categories()->attach($category));
    $this->get('/category/actual-category.md')->assertOk()->assertSee('Actual Category');
    $this->get('/topics.md')->assertOk()->assertSee('Actual Category');
});

it('publishes consistent discovery identity and preserves training choices', function () {
    $this->get('/llms.txt')->assertOk()->assertSee('/about.md', false)->assertSee('keep in sync with robots.txt and ai.txt', false);
    $full = $this->get('/llms-full.txt')->assertOk()->assertSee('<!-- source:', false);
    expect(mb_strlen($full->getContent()))->toBeLessThan(500000);
    $this->get('/ai.txt')->assertOk()->assertSee('[identity]', false)->assertSee(config('agent_access.contact'))->assertDontSee('\\n');
    $robots = $this->get('/robots.txt')->assertOk()->getContent();
    foreach (config('agent_access.search_bots') as $bot) {
        expect($robots)->toContain('User-agent: '.$bot."\nDisallow: /admin");
    }
    expect($robots)->toContain("User-agent: Bytespider\nDisallow: /", 'Sitemap:', 'Allow: /api/md/');
    expect($robots)->not->toContain('User-agent: GPTBot');
    $this->get('/api/md/_catalog?limit=3')->assertOk()->assertJsonCount(3, 'items')->assertJsonStructure(['items' => [['canonical', 'markdown']], 'next']);
});

it('advertises public markdown and homepage identity without changing html negotiation', function () {
    $this->get('/about')->assertOk()->assertSee('<meta name="ai-summary"', false)->assertSee('/about.md', false)->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    $this->get('/')->assertOk()->assertSee('"@type":"Organization"', false)->assertSee('"@type":"WebSite"', false)->assertSee('/index.md', false);
});

it('requires every application get route to have coverage or an explicit skip reason', function () {
    $registry = app(Registry::class);
    $missing = [];
    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods()) || str_contains($route->getActionName(), 'vendor/') || str_starts_with($route->getActionName(), 'Laravel\\') || str_starts_with($route->uri(), 'livewire/')) {
            continue;
        }
        if (! isset($registry->definitions()[$route->getName()]) && ! $registry->skipReason($route)) {
            $missing[] = $route->uri();
        }
    }
    expect($missing)->toBe([]);
});

it('detects stale static markdown source copies', function () {
    foreach (json_decode(file_get_contents(resource_path('markdown/sources.json')), true) as $path => $hash) {
        expect(hash_file('sha256', base_path($path)), $path)->toBe($hash);
    }
});

it('never publishes private collection notes and invalidates public documents after withdrawal', function () {
    $owner = User::factory()->create();
    $product = Product::factory()->create(['slug' => 'saved-public-tool']);
    $collection = $owner->productCollections()->create(['name' => 'Public selection', 'visibility' => 'public']);
    $collection->items()->create(['product_id' => $product->id, 'comment' => 'Visible public note']);
    $path = '/collections/'.$owner->fresh()->public_handle.'/'.$collection->slug;
    $this->get($path.'.md')->assertOk()->assertSee('Visible public note');
    $collection->update(['visibility' => 'private']);
    $this->actingAs($owner);
    $this->get($path.'.md')->assertNotFound()->assertDontSee('Visible public note');
    $this->get('/api/md/_catalog?limit=100')->assertDontSee('Visible public note');
});

it('keeps the configured to do tool public and omits session lists', function () {
    $path = app(\App\Support\ToolSettings::class)->path('todo_list');
    $this->withSession(['todo_lists' => [['title' => 'PRIVATE SESSION TASK']]])->get($path.'.md')
        ->assertOk()->assertSee('Email Notifications')->assertDontSee('PRIVATE SESSION TASK');
    $this->get($path)->assertOk();
});

it('preserves canonical category pagination in documents and the catalog', function () {
    $type = Type::firstOrCreate(['name' => 'Software Categories']);
    $category = Category::factory()->create(['name' => 'Paged tools', 'slug' => 'paged-tools']);
    $category->types()->attach($type);
    Product::factory()->count(51)->create()->each(fn ($p) => $p->categories()->attach($category));
    $this->get('/category/paged-tools.md')->assertOk()->assertSee('/category/paged-tools/page/2', false);
    $this->get('/category/paged-tools/page/2.md')->assertOk()->assertSee('Page 2');
    $this->get('/category/paged-tools/page/3.md')->assertNotFound();
    $found = false;
    foreach (app(Registry::class)->listMarkdownRoutes() as $entry) {
        if ($entry['path'] === '/category/paged-tools/page/2') {
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();
});

it('keeps real markdown routes and unrelated endpoints untouched', function () {
    Route::get('/docs/SKILL.md', fn () => response('Existing markdown file', 200, ['Content-Type' => 'text/plain']));
    $this->get('/docs/SKILL.md')->assertOk()->assertSee('Existing markdown file');
    expect(app(Registry::class)->supports('/articles/feed'))->toBeFalse();
});
