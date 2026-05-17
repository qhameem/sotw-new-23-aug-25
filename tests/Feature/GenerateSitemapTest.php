<?php

use App\Models\Product;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->sitemapIndexPath = public_path('sitemap.xml');
    $this->sitemapDirectory = public_path('sitemaps');
    $this->originalSitemapIndex = File::exists($this->sitemapIndexPath)
        ? File::get($this->sitemapIndexPath)
        : null;
    $this->originalChildSitemaps = [];

    if (File::isDirectory($this->sitemapDirectory)) {
        foreach (File::glob($this->sitemapDirectory . DIRECTORY_SEPARATOR . '*.xml') as $path) {
            $this->originalChildSitemaps[$path] = File::get($path);
        }
    }
});

afterEach(function () {
    if (File::exists($this->sitemapIndexPath)) {
        File::delete($this->sitemapIndexPath);
    }

    if (File::isDirectory($this->sitemapDirectory)) {
        foreach (File::glob($this->sitemapDirectory . DIRECTORY_SEPARATOR . '*.xml') as $path) {
            File::delete($path);
        }
    }

    if ($this->originalSitemapIndex !== null) {
        File::put($this->sitemapIndexPath, $this->originalSitemapIndex);
    }

    if (!empty($this->originalChildSitemaps)) {
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

    expect($sitemapIndex)->toContain('https://softwareontheweb.com/sitemaps/recent-launches.xml');

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
