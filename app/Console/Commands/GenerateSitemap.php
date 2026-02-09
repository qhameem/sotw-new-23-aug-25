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

        $sitemapPath = public_path('sitemap.xml');

        $sitemap = Sitemap::create();

        // Add static pages if any (e.g., home page)
        $sitemap->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $sitemap->add(Url::create(route('articles.index'))->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));
        $sitemap->add(Url::create(route('about'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create(route('faq'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create(route('fast-track.index'))->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create(route('legal'))->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create(route('premium-spot.index'))->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create(route('premium-spot.details'))->setPriority(0.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_NEVER));
        $sitemap->add(Url::create(route('promote'))->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
        $sitemap->add(Url::create(route('software-review'))->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create(route('subscribe'))->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY));
        $sitemap->add(Url::create(route('topics.index'))->setPriority(0.8)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

        // Add Article models
        // The toSitemapTag method in Article will be called for each instance
        $sitemap->add(Article::where('status', 'published')->where('published_at', '<=', now())->get());

        // Add BlogCategory models
        $sitemap->add(ArticleCategory::all()->filter(function ($category) {
            return $category->articles()->where('status', 'published')->where('published_at', '<=', now())->exists();
        }));

        // Add BlogTag models
        $sitemap->add(ArticleTag::all()->filter(function ($tag) {
            return $tag->articles()->where('status', 'published')->where('published_at', '<=', now())->exists();
        }));

        // Add Product models
        $sitemap->add(Product::where('approved', true)->get());

        // Add Product Category models (only if they have approved products)
        $sitemap->add(Category::all()->filter(function ($category) {
            return $category->products()->where('approved', true)->exists();
        }));

        // Add Archive URLs (Weeks)
        $activeWeeks = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('YEAR(COALESCE(published_at, created_at)) as year, WEEK(COALESCE(published_at, created_at), 3) as week')
            ->groupBy('year', 'week')
            ->get();

        foreach ($activeWeeks as $activeWeek) {
            $sitemap->add(Url::create(route('products.byWeek', ['year' => $activeWeek->year, 'week' => $activeWeek->week]))
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
            $sitemap->add(Url::create(route('products.byMonth', ['year' => $activeMonth->year, 'month' => $activeMonth->month]))
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
            $sitemap->add(Url::create(route('products.byYear', ['year' => $activeYear->year]))
                ->setPriority(0.3)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));
        }


        $sitemap->writeToFile($sitemapPath);

        $this->info("Sitemap generated successfully at {$sitemapPath}");
        return Command::SUCCESS;
    }
}