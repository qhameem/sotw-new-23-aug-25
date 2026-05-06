<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap; // Changed from SitemapGenerator
use Spatie\Sitemap\Tags\Url;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\Product; // Added
use App\Models\Category; // Added
use App\Services\RelatedProductService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
// It's good practice to also include your main app URL and other important static pages
// use Carbon\Carbon; // Already imported in models, but good to be explicit if used directly here

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');
        $relatedProductService = app(RelatedProductService::class);
        $sitemapPath = public_path('sitemap.xml');
        $sitemapDirectory = public_path('sitemaps');
        $generatedAt = now();
        $sitemapEntries = [];

        File::ensureDirectoryExists($sitemapDirectory);
        foreach (File::glob($sitemapDirectory . DIRECTORY_SEPARATOR . '*.xml') as $existingSitemap) {
            File::delete($existingSitemap);
        }

        $staticPagesSitemap = Sitemap::create();
        $staticPagesSitemap->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $staticPagesSitemap->add(Url::create(route('articles.index'))->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $staticPagesSitemap->add(Url::create(route('about'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $staticPagesSitemap->add(Url::create(route('faq'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $staticPagesSitemap->add(Url::create(route('fast-track.index'))->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $staticPagesSitemap->add(Url::create(route('legal'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $staticPagesSitemap->add(Url::create(route('premium-spot.index'))->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $staticPagesSitemap->add(Url::create(route('premium-spot.details'))->setPriority(0.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_NEVER));
        $staticPagesSitemap->add(Url::create(route('software-review'))->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $staticPagesSitemap->add(Url::create(route('subscribe'))->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $staticPagesSitemap->add(Url::create(route('topics.index'))->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $this->writeChildSitemap($staticPagesSitemap, $sitemapDirectory . '/static.xml', $sitemapEntries, $generatedAt);

        $contentSitemap = Sitemap::create();
        $contentSitemap->add(Article::where('status', 'published')->where('published_at', '<=', now())->get());
        $contentSitemap->add(ArticleCategory::all()->filter(function ($category) {
            return $category->articles()->where('status', 'published')->where('published_at', '<=', now())->exists();
        }));
        $contentSitemap->add(ArticleTag::all()->filter(function ($tag) {
            return $tag->articles()->where('status', 'published')->where('published_at', '<=', now())->exists();
        }));
        $this->writeChildSitemap($contentSitemap, $sitemapDirectory . '/content.xml', $sitemapEntries, $generatedAt);

        $productsSitemap = Sitemap::create();
        $productsSitemap->add(Product::where('approved', true)->with('media')->get());
        $this->writeChildSitemap($productsSitemap, $sitemapDirectory . '/products.xml', $sitemapEntries, $generatedAt);

        $taxonomySitemap = Sitemap::create();
        $taxonomySitemap->add(Category::all()->filter(function ($category) {
            return $category->products()->where('approved', true)->exists();
        }));
        $this->writeChildSitemap($taxonomySitemap, $sitemapDirectory . '/taxonomy.xml', $sitemapEntries, $generatedAt);

        $archiveSitemap = Sitemap::create();

        // Add Archive URLs (Weeks)
        $activeWeeks = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('YEAR(COALESCE(published_at, created_at)) as year, WEEK(COALESCE(published_at, created_at), 3) as week')
            ->groupBy('year', 'week')
            ->get();

        foreach ($activeWeeks as $activeWeek) {
            $archiveSitemap->add(Url::create(route('products.byWeek', ['year' => $activeWeek->year, 'week' => $activeWeek->week]))
                ->setPriority(0.4)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // Add Archive URLs (Months)
        $activeMonths = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('YEAR(COALESCE(published_at, created_at)) as year, MONTH(COALESCE(published_at, created_at)) as month')
            ->groupBy('year', 'month')
            ->get();

        foreach ($activeMonths as $activeMonth) {
            $archiveSitemap->add(Url::create(route('products.byMonth', ['year' => $activeMonth->year, 'month' => $activeMonth->month]))
                ->setPriority(0.4)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        }

        // Add Archive URLs (Years)
        $activeYears = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('YEAR(COALESCE(published_at, created_at)) as year')
            ->groupBy('year')
            ->get();

        foreach ($activeYears as $activeYear) {
            $archiveSitemap->add(Url::create(route('products.byYear', ['year' => $activeYear->year]))
                ->setPriority(0.3)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));
        }
        $this->writeChildSitemap($archiveSitemap, $sitemapDirectory . '/archives.xml', $sitemapEntries, $generatedAt);


        // --- pSEO Pages --- //
        $this->info('Adding pSEO routes...');
        $pseoSitemap = Sitemap::create();

        // 1. Alternatives Pages
        foreach (Product::where('approved', true)->where('is_published', true)->with(['categories.types', 'techStacks'])->get() as $product) {
            $alternatives = $relatedProductService->getAlternatives($product, 15);

            if ($relatedProductService->shouldNoindexAlternatives($product, $alternatives)) {
                continue;
            }

            $pseoSitemap->add(Url::create(route('pseo.alternatives', $product->slug))
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // 2. Best-Of Category Pages
        $softwareCats = Category::whereHas('products', function($q) { $q->where('approved', true); })
            ->whereHas('types', function($q) { $q->whereIn('name', ['Software', 'Software Categories']); })->get();
            
        foreach ($softwareCats as $category) {
            $pseoSitemap->add(Url::create(route('pseo.best', $category->slug))
                ->setPriority(0.8)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }

        // 3. Built-With (Tech Stack) Pages
        if (class_exists(\App\Models\TechStack::class)) {
            foreach (\App\Models\TechStack::whereHas('products', function($q) { $q->where('approved', true); })->get() as $stack) {
                $pseoSitemap->add(Url::create(route('pseo.builtWith', $stack->slug))
                    ->setPriority(0.7)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            }
        }

        // 4. Pricing Pages
        $pricingCategories = Category::whereHas('types', function($q) { $q->where('name', 'Pricing'); })
            ->whereHas('products', function($q) { $q->where('approved', true); })->get();
        foreach ($pricingCategories as $pricing) {
            $pseoSitemap->add(Url::create(route('pseo.pricing', $pricing->slug))
                ->setPriority(0.7)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        }
        $this->writeChildSitemap($pseoSitemap, $sitemapDirectory . '/pseo.xml', $sitemapEntries, $generatedAt);

        // 5. Compare Pages (Top Comparisons to prevent millions of URLs)
        $this->info('Generating comparison URLs...');
        $compareSitemap = Sitemap::create();
        $products = Product::where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'techStacks'])
            ->get();
        $addedComparisons = [];
        
        foreach ($products as $product) {
            $similarProducts = $relatedProductService->getComparisons($product, 3);
            
            foreach ($similarProducts as $similar) {
                // Ensure alphabetical order so A-vs-B is the same as B-vs-A
                $slugs = [$product->slug, $similar->slug];
                sort($slugs);
                $compareKey = $slugs[0] . '-vs-' . $slugs[1];
                
                if (!isset($addedComparisons[$compareKey])) {
                    $compareSitemap->add(Url::create(route('pseo.compare', ['params' => $compareKey]))
                        ->setPriority(0.6)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
                    $addedComparisons[$compareKey] = true;
                }
            }
        }

        $this->writeChildSitemap($compareSitemap, $sitemapDirectory . '/compare.xml', $sitemapEntries, $generatedAt);
        $this->writeSitemapIndex($sitemapPath, $sitemapEntries);

        $this->info("Sitemap generated successfully at {$sitemapPath}");
        return Command::SUCCESS;
    }

    protected function writeChildSitemap(Sitemap $sitemap, string $path, array &$indexEntries, $generatedAt): void
    {
        $sitemap->writeToFile($path);

        $relativePath = Str::of($path)
            ->after(public_path() . DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '/')
            ->toString();

        $indexEntries[] = [
            'loc' => url($relativePath),
            'lastmod' => $generatedAt->toAtomString(),
        ];
    }

    protected function writeSitemapIndex(string $path, array $entries): void
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . htmlspecialchars($entry['loc'], ENT_XML1) . '</loc>';
            $lines[] = '    <lastmod>' . htmlspecialchars($entry['lastmod'], ENT_XML1) . '</lastmod>';
            $lines[] = '  </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        File::put($path, implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
