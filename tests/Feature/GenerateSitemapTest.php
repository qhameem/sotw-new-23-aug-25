<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Type;
use App\Services\RelatedProductService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->sitemapIndexPath = public_path('sitemap.xml');
    $this->sitemapDirectory = public_path('sitemaps');
    $this->originalSitemapIndex = File::exists($this->sitemapIndexPath)
        ? File::get($this->sitemapIndexPath)
        : null;
    $this->originalChildSitemaps = [];

    if (File::isDirectory($this->sitemapDirectory)) {
        foreach (File::glob($this->sitemapDirectory.DIRECTORY_SEPARATOR.'*.xml') as $path) {
            $this->originalChildSitemaps[$path] = File::get($path);
        }
    }
});

afterEach(function () {
    if (File::exists($this->sitemapIndexPath)) {
        File::delete($this->sitemapIndexPath);
    }

    if (File::isDirectory($this->sitemapDirectory)) {
        foreach (File::glob($this->sitemapDirectory.DIRECTORY_SEPARATOR.'*.xml') as $path) {
            File::delete($path);
        }
    }

    if ($this->originalSitemapIndex !== null) {
        File::put($this->sitemapIndexPath, $this->originalSitemapIndex);
    }

    if (! empty($this->originalChildSitemaps)) {
        File::ensureDirectoryExists($this->sitemapDirectory);

        foreach ($this->originalChildSitemaps as $path => $contents) {
            File::put($path, $contents);
        }
    }
});

it('generates a recent launches sitemap and excludes unpublished products from product sitemaps', function () {
    $recentProduct = Product::factory()->create([
        'name' => 'Fresh Launch',
        'slug' => 'fresh-launch',
        'approved' => true,
        'is_published' => true,
        'published_at' => now()->subDays(2),
    ]);

    $olderProduct = Product::factory()->create([
        'name' => 'Older Launch',
        'slug' => 'older-launch',
        'approved' => true,
        'is_published' => true,
        'published_at' => now()->subDays(45),
    ]);

    $scheduledProduct = Product::factory()->create([
        'name' => 'Scheduled Launch',
        'slug' => 'scheduled-launch',
        'approved' => true,
        'is_published' => false,
        'published_at' => now()->addDay(),
    ]);

    $this->artisan('sitemap:generate')->assertExitCode(0);

    $productsSitemapPath = public_path('sitemaps/products.xml');
    $recentLaunchesSitemapPath = public_path('sitemaps/recent-launches.xml');
    $sitemapIndex = File::get(public_path('sitemap.xml'));
    $productsSitemap = File::get($productsSitemapPath);
    $recentLaunchesSitemap = File::get($recentLaunchesSitemapPath);

    expect(File::exists($productsSitemapPath))->toBeTrue();
    expect(File::exists($recentLaunchesSitemapPath))->toBeTrue();

    expect($sitemapIndex)->toContain(url('sitemaps/recent-launches.xml'));

    expect($productsSitemap)
        ->toContain(route('products.show', $recentProduct->slug))
        ->toContain(route('products.show', $olderProduct->slug))
        ->not->toContain(route('products.show', $scheduledProduct->slug));

    expect($recentLaunchesSitemap)
        ->toContain(route('products.show', $recentProduct->slug))
        ->not->toContain(route('products.show', $olderProduct->slug))
        ->not->toContain(route('products.show', $scheduledProduct->slug));
});

it('excludes the current week archive URL from the archives sitemap because it redirects home', function () {
    Product::factory()->create([
        'name' => 'Current Week Launch',
        'slug' => 'current-week-launch',
        'approved' => true,
        'is_published' => true,
        'published_at' => now()->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->addDay(),
    ]);

    $olderWeekStart = now()->copy()->subWeek()->startOfWeek(\Carbon\Carbon::MONDAY);
    Product::factory()->create([
        'name' => 'Older Week Launch',
        'slug' => 'older-week-launch',
        'approved' => true,
        'is_published' => true,
        'published_at' => $olderWeekStart->copy()->addDay(),
    ]);

    $this->artisan('sitemap:generate')->assertExitCode(0);

    $archivesSitemap = File::get(public_path('sitemaps/archives.xml'));

    expect($archivesSitemap)
        ->not->toContain(route('products.byWeek', [
            'year' => now()->year,
            'week' => now()->weekOfYear,
        ]))
        ->toContain(route('products.byWeek', [
            'year' => $olderWeekStart->year,
            'week' => $olderWeekStart->weekOfYear,
        ]));
});

it('generates alternatives separately without building comparisons', function () {
    $includedProduct = Product::factory()->create([
        'name' => 'Included Product',
        'slug' => 'included-product',
        'approved' => true,
        'is_published' => true,
    ]);

    $excludedProduct = Product::factory()->create([
        'name' => 'Excluded Product',
        'slug' => 'excluded-product',
        'approved' => true,
        'is_published' => true,
    ]);

    Product::factory()->create([
        'name' => 'Unpublished Product',
        'slug' => 'unpublished-product',
        'approved' => true,
        'is_published' => false,
    ]);

    $relatedProducts = Mockery::mock(RelatedProductService::class);
    $relatedProducts->shouldNotReceive('getComparisons');
    $relatedProducts->shouldReceive('getAlternatives')->twice()->andReturn(collect());
    $relatedProducts->shouldReceive('shouldNoindexAlternatives')
        ->twice()
        ->andReturnUsing(fn (Product $product) => $product->is($excludedProduct));
    $this->app->instance(RelatedProductService::class, $relatedProducts);

    $this->artisan('sitemap:generate-alternatives')->assertExitCode(0);

    $alternativesSitemap = File::get(public_path('sitemaps/alternatives.xml'));
    $sitemapIndex = File::get(public_path('sitemap.xml'));

    expect($alternativesSitemap)
        ->toContain(route('pseo.alternatives', $includedProduct->slug))
        ->not->toContain(route('pseo.alternatives', $excludedProduct->slug))
        ->not->toContain('unpublished-product');

    expect($sitemapIndex)
        ->toContain(url('sitemaps/alternatives.xml'))
        ->not->toContain('compare.xml');
});

it('preserves independently generated alternatives when rebuilding core sitemaps', function () {
    File::ensureDirectoryExists(public_path('sitemaps'));
    File::put(public_path('sitemaps/alternatives.xml'), '<urlset>alternatives</urlset>');

    $this->artisan('sitemap:generate')->assertExitCode(0);

    expect(File::get(public_path('sitemaps/alternatives.xml')))->toBe('<urlset>alternatives</urlset>');
    expect(File::get(public_path('sitemap.xml')))
        ->toContain(url('sitemaps/alternatives.xml'));
});

it('generates separate taxonomy sitemaps and excludes thin pages', function () {
    $softwareType = Type::firstOrCreate(['name' => 'Software']);
    $useCaseType = Type::firstOrCreate(['name' => 'Use Case']);
    $bestForType = Type::firstOrCreate(['name' => 'Best for']);
    $platformType = Type::firstOrCreate(['name' => 'Platform']);

    $category = Category::factory()->create(['slug' => 'reporting']);
    $useCase = Category::factory()->create(['slug' => 'report-generation']);
    $thinBestFor = Category::factory()->create(['slug' => 'small-teams']);
    $platform = Category::factory()->create(['slug' => 'linux']);

    $category->types()->attach($softwareType);
    $useCase->types()->attach($useCaseType);
    $thinBestFor->types()->attach($bestForType);
    $platform->types()->attach($platformType);

    foreach ([[$category, 3], [$useCase, 3], [$thinBestFor, 2], [$platform, 3]] as [$taxonomy, $count]) {
        Product::factory()->count($count)->create([
            'approved' => true,
            'is_published' => true,
        ])->each(fn (Product $product) => $product->categories()->attach($taxonomy));
    }

    $this->artisan('sitemap:generate')->assertExitCode(0);

    $index = File::get(public_path('sitemap.xml'));
    expect($index)
        ->toContain(url('sitemaps/categories.xml'))
        ->toContain(url('sitemaps/use-cases.xml'))
        ->toContain(url('sitemaps/best-for.xml'))
        ->toContain(url('sitemaps/platforms.xml'));

    expect(File::get(public_path('sitemaps/categories.xml')))
        ->toContain(route('categories.show', $category->slug));
    expect(File::get(public_path('sitemaps/use-cases.xml')))
        ->toContain(route('use-cases.show', $useCase->slug));
    expect(File::get(public_path('sitemaps/best-for.xml')))
        ->not->toContain($thinBestFor->slug);
    expect(File::get(public_path('sitemaps/platforms.xml')))
        ->toContain(route('platforms.show', $platform->slug));
});
