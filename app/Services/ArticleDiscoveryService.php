<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleCategory;
use Carbon\Carbon;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\FilterExpression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\OrderBy;
use Spatie\Analytics\Period;
use Throwable;

class ArticleDiscoveryService
{
    public function featuredArticles(int $limit = 3): Collection
    {
        return Article::query()
            ->published()
            ->featured()
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    public function popularArticles(int $limit = 5): Collection
    {
        return $this->resolvePopularArticles($limit);
    }

    public function popularArticlesPaginator(int $perPage = 10, int $maxResults = 100): LengthAwarePaginator
    {
        $articles = $this->resolvePopularArticles($maxResults);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $articles->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $articles->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function topicCategories(int $limit = 8): Collection
    {
        return ArticleCategory::query()
            ->whereHas('articles', fn ($query) => $query->published())
            ->withCount([
                'articles as published_articles_count' => fn ($query) => $query->published(),
            ])
            ->orderByDesc('published_articles_count')
            ->orderBy('name')
            ->take($limit)
            ->get();
    }

    private function resolvePopularArticles(int $limit): Collection
    {
        $fallbackArticles = $this->fallbackPopularArticles(max($limit, 5));

        if (!$this->analyticsConfigured()) {
            return $fallbackArticles->take($limit)->values();
        }

        $slugs = Cache::remember(
            "articles.popular.slugs.{$limit}",
            now()->addMinutes(30),
            fn () => $this->fetchPopularArticleSlugs($limit * 3),
        );

        if ($slugs->isEmpty()) {
            return $fallbackArticles->take($limit)->values();
        }

        $articles = Article::query()
            ->published()
            ->whereIn('slug', $slugs->all())
            ->with('author', 'categories', 'tags')
            ->get()
            ->sortBy(fn (Article $article) => $slugs->search($article->slug))
            ->values();

        if ($articles->isEmpty()) {
            return $fallbackArticles->take($limit)->values();
        }

        if ($articles->count() >= $limit) {
            return $articles->take($limit)->values();
        }

        $missing = $limit - $articles->count();
        $fallback = $fallbackArticles
            ->reject(fn (Article $article) => $articles->contains('id', $article->id))
            ->take($missing);

        return $articles->concat($fallback)->values();
    }

    private function fetchPopularArticleSlugs(int $limit): Collection
    {
        try {
            $dimensionFilter = new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'fullPageUrl',
                    'string_filter' => new StringFilter([
                        'match_type' => MatchType::CONTAINS,
                        'value' => '/articles/',
                    ]),
                ]),
            ]);

            $rows = Analytics::get(
                Period::create(Carbon::now()->subDays(90), Carbon::now()),
                ['screenPageViews'],
                ['fullPageUrl'],
                $limit,
                [OrderBy::metric('screenPageViews', true)],
                $dimensionFilter,
            );

            return $rows
                ->map(fn (array $row) => $this->slugFromUrl($row['fullPageUrl'] ?? null))
                ->filter()
                ->unique()
                ->values();
        } catch (Throwable) {
            return collect();
        }
    }

    private function slugFromUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '';

        if (!Str::startsWith($path, '/articles/')) {
            return null;
        }

        $slug = trim(Str::after($path, '/articles/'), '/');

        if ($slug === '' || Str::contains($slug, '/')) {
            return null;
        }

        return $slug;
    }

    private function analyticsConfigured(): bool
    {
        $credentialsPath = config('analytics.service_account_credentials_json');

        return filled(config('analytics.property_id'))
            && filled($credentialsPath)
            && file_exists($credentialsPath);
    }

    private function fallbackPopularArticles(int $limit): Collection
    {
        return Article::query()
            ->published()
            ->with('author', 'categories', 'tags')
            ->latest('published_at')
            ->take($limit)
            ->get();
    }
}
