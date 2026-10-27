<?php

namespace App\Services\Markdown;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\TechStack;
use App\Services\Markdown\Renderers\Content;
use App\Services\Markdown\Renderers\StaticContent;
use App\Services\PublicPages\PageData;
use App\Support\CategoryPageType;
use App\Support\LaunchReadinessGuideLibrary;
use App\Support\SiteIdentity;
use App\Support\ToolSettings;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Registry
{
    public function definitions(): array
    {
        return [
            'home' => ['listing', 'Home', 'Software launches and product discoveries.', 'Core pages'],
            'about' => ['static:about', 'About', 'About the directory and its curation.', 'Core pages'],
            'faq' => ['static:faq', 'FAQ', 'Answers about discovery and product submissions.', 'Core pages'],
            'newsletter.index' => ['static:newsletter', 'Newsletter', 'Curated software discoveries by email.', 'Core pages'],
            'products.create' => ['static:add-product', 'Submit a product', 'Product submission and launch conditions.', 'Core pages'],
            'badges.index' => ['static:get-the-badge', 'Badge instructions', 'How to install and verify a listing badge.', 'Core pages'],
            'premium-spot.details' => ['static:premium-spot-details', 'Premium Spot details', 'Premium placement terms and features.', 'Core pages'],
            'software-review' => ['static:software-review', 'Software reviews', 'Information about the software review service.', 'Core pages'],
            'topics.index' => ['topics', 'Topics', 'Software topics and categories.', 'Core pages'],
            'categories.index' => ['topics', 'Categories', 'Software topics and categories.', 'Core pages'],
            'changelog.index' => ['changelog', 'Changelog', 'Site updates and releases.', 'Core pages'],
            'todolists.index' => ['static:todo', 'To do list', 'Public features and instructions for the to do list.', 'Docs / Guides'],
            'launch-readiness.index' => ['tool', 'Launch readiness checker', 'Homepage launch readiness, SEO and AI checks.', 'Docs / Guides'],
            'launch-readiness.guides.show' => ['guide', 'Launch readiness guide', 'Guidance for interpreting homepage checks.', 'Docs / Guides'],
            'launch-readiness.history' => ['history', 'Public scan history', 'Publicly saved launch readiness scans.', 'Docs / Guides'],
            'articles.index' => ['articles', 'Articles', 'Published software articles.', 'Articles'],
            'articles.category' => ['articles', 'Article category', 'Published articles in a category.', 'Articles'],
            'articles.tag' => ['articles', 'Article tag', 'Published articles with a tag.', 'Articles'],
            'articles.show' => ['article', 'Article', 'Published article.', 'Articles'],
            'alternatives.index' => ['directory', 'Alternatives', 'Software alternatives directory.', 'Software'],
            'products.show' => ['product', 'Product', 'Published product information.', 'Software'],
            'topics.category' => ['listing', 'Topic', 'Products in a software topic.', 'Software'],
            'categories.show' => ['listing', 'Category', 'Products in a software category.', 'Software'],
            'categories.show.page' => ['listing', 'Category', 'Products in a software category.', 'Software'],
            'use-cases.show' => ['listing', 'Use case', 'Software for a use case.', 'Software'],
            'use-cases.show.page' => ['listing', 'Use case', 'Software for a use case.', 'Software'],
            'best-for.show' => ['listing', 'Audience', 'Software for an audience.', 'Software'],
            'best-for.show.page' => ['listing', 'Audience', 'Software for an audience.', 'Software'],
            'platforms.show' => ['listing', 'Platform', 'Software for a platform.', 'Software'],
            'platforms.show.page' => ['listing', 'Platform', 'Software for a platform.', 'Software'],
            'software-groups.show' => ['listing', 'Software group', 'Software grouped by purpose.', 'Software'],
            'software-groups.page' => ['listing', 'Software group', 'Software grouped by purpose.', 'Software'],
            'products.byWeek' => ['listing', 'Weekly launches', 'Software launches by week.', 'Software'],
            'products.byMonth' => ['listing', 'Monthly launches', 'Software launches by month.', 'Software'],
            'products.byYear' => ['listing', 'Yearly launches', 'Software launches by year.', 'Software'],
            'pseo.best' => ['listing', 'Best software', 'Community-ranked software in a category.', 'Software'],
            'pseo.bestFor' => ['listing', 'Best software for an audience', 'Software matching a category and audience.', 'Software'],
            'pseo.alternatives' => ['listing', 'Product alternatives', 'Relevant alternatives to a product.', 'Software'],
            'pseo.builtWith' => ['listing', 'Built with', 'Products using a technology.', 'Software'],
            'pseo.compare' => ['listing', 'Product comparison', 'Side-by-side software comparison.', 'Software'],
            'pseo.pricing' => ['listing', 'Pricing model', 'Software using a pricing model.', 'Software'],
            'collections.show' => ['collection', 'Public collection', 'A publicly shared software collection.', 'Software'],
            'legal' => ['static:legal', 'Legal', 'Privacy policy and terms of use.', 'Optional'],
            'refund-policy' => ['static:refund-policy', 'Refund policy', 'Refund and cancellation conditions.', 'Optional'],
        ];
    }

    public function match(string $path): ?Route
    {
        try {
            $request = Request::create(app(SiteIdentity::class)->url($path), 'GET');
            foreach (app('router')->getRoutes()->getRoutes() as $route) {
                if ($route->matches($request)) {
                    return (clone $route)->bind($request);
                }
            }

            return null;
        } catch (HttpExceptionInterface) {
            return null;
        }
    }

    public function supports(string $path): bool
    {
        $route = $this->match($path);

        return $route && isset($this->definitions()[$route->getName()]);
    }

    public function render(string $path): array
    {
        $route = $this->match($path);
        $definition = $this->definitions()[$route?->getName()] ?? null;
        abort_unless($definition, 404);
        [$kind, $title, $summary] = $definition;
        $query = [];
        parse_str(parse_url($path, PHP_URL_QUERY) ?? '', $query);
        $allowedQuery = match ($route->getName()) {
            'articles.index' => ['page', 'view'],
            'articles.category', 'articles.tag', 'alternatives.index', 'home', 'products.byWeek', 'products.byMonth', 'products.byYear' => ['page'],
            'launch-readiness.history' => ['page', 'sort', 'per_page'],
            default => [],
        };
        abort_if(array_diff(array_keys($query), $allowedQuery) !== [], 404);
        abort_if(isset($query['view']) && ! in_array($query['view'], ['latest', 'featured', 'popular'], true), 404);
        abort_if(isset($query['sort']) && ! in_array($query['sort'], ['latest', 'score'], true), 404);
        abort_if(isset($query['per_page']) && ! in_array((string) $query['per_page'], ['10', '20', '30', '50'], true), 404);
        if (isset($query['page'])) {
            abort_unless(filter_var($query['page'], FILTER_VALIDATE_INT) && (int) $query['page'] > 0, 404);
        }
        $canonical = app(SiteIdentity::class)->url($path);
        if ($kind === 'tool') {
            $slug = $route->parameter('toolSlug');
            $settings = app(ToolSettings::class);
            abort_unless(in_array($slug, [$settings->slug('todo_list'), $settings->slug('launch_readiness')], true), 404);
            $kind = $slug === $settings->slug('todo_list') ? 'static:todo' : 'static:launch';
            if ($kind === 'static:todo') {
                $title = 'To do list';
                $summary = 'Free to do list features and instructions.';
            }
        }
        if (str_starts_with($kind, 'static:')) {
            return app(StaticContent::class)->render(substr($kind, 7), $title, $summary, $canonical);
        }
        $data = app(PageData::class)->load($path);
        foreach (['regularProducts', 'posts', 'products', 'history'] as $key) {
            $items = $data[$key] ?? null;
            if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                abort_if($items->currentPage() > max(1, $items->lastPage()), 404);
            }
        }
        foreach (['product', 'productA', 'productB'] as $key) {
            if (isset($data[$key])) {
                abort_unless(app(Content::class)->visible($data[$key]), 404);
            }
        }

        return str_contains($kind, '\\') && class_exists($kind)
            ? app($kind)->render($data, $canonical)
            : app(Content::class)->render($kind, $data, $canonical, $title);
    }

    public function markdownUrl(string $path): string
    {
        $query = parse_url($path, PHP_URL_QUERY);
        $base = parse_url($path, PHP_URL_PATH) ?: '/';

        return app(SiteIdentity::class)->url(($base === '/' ? '/index' : rtrim($base, '/')).'.md'.($query ? '?'.$query : ''));
    }

    /** Every candidate is checked with the same visibility rules as its renderer. */
    public function listMarkdownRoutes(?int $perType = null): \Generator
    {
        foreach ($this->candidateRoutes($perType) as $entry) {
            try {
                $document = $this->render($entry['path']);
                yield [...$entry, 'document' => $document];
            } catch (HttpExceptionInterface $error) {
                if ($error->getStatusCode() !== 404) {
                    throw $error;
                }
            }
        }
    }

    public function candidateRoutes(?int $perType = null): \Generator
    {
        foreach ($this->definitions() as $name => $definition) {
            if (str_ends_with($name, '.page')) {
                continue;
            }
            $count = 0;
            foreach ($this->candidates($name) as $path) {
                $entry = ['path' => $path, 'name' => $name, 'group' => $definition[3], 'title' => $definition[1], 'description' => $definition[2], 'markdown' => $this->markdownUrl($path)];
                yield $entry;
                if ($perType === null && ! str_contains($path, '/page/') && in_array($definition[0], ['listing', 'articles', 'directory', 'history'], true)) {
                    try {
                        $data = app(PageData::class)->load($path);
                        foreach (['regularProducts', 'posts', 'products', 'history'] as $key) {
                            $items = $data[$key] ?? null;
                            if ($items instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                                for ($page = 2; $page <= $items->lastPage(); $page++) {
                                    $pagePath = $name === 'software-groups.show' || isset($data['categoryPagination'])
                                        ? $path.'/page/'.$page : $path.'?page='.$page;
                                    yield [...$entry, 'path' => $pagePath, 'markdown' => $this->markdownUrl($pagePath)];
                                }
                                break;
                            }
                        }
                    } catch (HttpExceptionInterface $e) {
                        if ($e->getStatusCode() !== 404) {
                            throw $e;
                        }
                    }
                }
                if ($perType !== null && ++$count >= $perType) {
                    break;
                }
            }
        }
    }

    private function candidates(string $name): \Generator
    {
        $enumerator = $this->definitions()[$name][4] ?? null;
        if ($enumerator) {
            yield from $enumerator();

            return;
        }
        $route = app('router')->getRoutes()->getByName($name);
        if (! $route) {
            return;
        }
        $uri = $route->uri();
        if (! str_contains($uri, '{')) {
            yield '/'.ltrim($uri, '/');

            return;
        }
        $settings = app(ToolSettings::class);
        if ($name === 'launch-readiness.index' || $name === 'todolists.index') {
            yield $settings->path('todo_list');
            yield $settings->path('launch_readiness');
        } elseif ($name === 'launch-readiness.history') {
            yield $settings->path('launch_readiness').'/history';
        } elseif ($name === 'launch-readiness.guides.show') {
            foreach (app(LaunchReadinessGuideLibrary::class)->all() as $guide) {
                yield $settings->path('launch_readiness').'/'.$guide['slug'];
            }
        } elseif ($name === 'articles.show') {
            foreach (Article::published()->latest('published_at')->cursor() as $article) {
                yield '/articles/'.$article->slug;
            }
        } elseif (in_array($name, ['articles.category', 'articles.tag'], true)) {
            $model = $name === 'articles.category' ? ArticleCategory::class : ArticleTag::class;
            foreach ($model::whereHas('articles', fn ($q) => $q->published())->cursor() as $item) {
                yield '/articles/'.($name === 'articles.category' ? 'category' : 'tag').'/'.$item->slug;
            }
        } elseif (in_array($name, ['products.show', 'pseo.alternatives'], true)) {
            foreach ($this->products()->latest('published_at')->cursor() as $product) {
                yield ($name === 'products.show' ? '/product/' : '/alternatives/').$product->slug;
            }
        } elseif ($name === 'pseo.builtWith') {
            foreach (TechStack::whereHas('products', fn ($q) => $q->approvedAndPublished())->cursor() as $stack) {
                yield '/built-with/'.$stack->slug;
            }
        } elseif ($name === 'pseo.compare') {
            foreach ($this->products()->with('categories.types')->latest('published_at')->cursor() as $a) {
                $softwareIds = $a->categories->filter(fn ($category) => $category->types->pluck('name')->map(fn ($name) => strtolower($name))->intersect(['software', 'software categories', 'category'])->isNotEmpty())->modelKeys();
                $candidates = $this->products()->where('slug', '>', $a->slug)
                    ->where(function ($query) use ($a, $softwareIds) {
                        $query->whereHas('categories', fn ($categories) => $categories->whereIn('categories.id', $softwareIds))
                            ->orWhereIn('id', $a->comparison_product_ids ?? [])
                            ->orWhereJsonContains('comparison_product_ids', (int) $a->id)
                            ->orWhereJsonContains('comparison_product_ids', (string) $a->id);
                    })->orderBy('slug');
                foreach ($candidates->cursor() as $b) {
                    yield '/compare/'.$a->slug.'-vs-'.$b->slug;
                    yield '/compare/'.$b->slug.'-vs-'.$a->slug;
                }
            }
        } elseif ($name === 'collections.show') {
            foreach (ProductCollection::where('visibility', ProductCollection::VISIBILITY_PUBLIC)->with('user')->cursor() as $collection) {
                yield '/collections/'.$collection->user->public_handle.'/'.$collection->slug;
            }
        } elseif (str_starts_with($name, 'software-groups.')) {
            foreach (app(\App\Services\CategoryNavigationService::class)->getBroadGroups() as $group) {
                $path = parse_url($group['url'], PHP_URL_PATH);
                if ($name === 'software-groups.show') {
                    yield $path;
                }
                // Group pagination is discovered through the paginator on its first page.
            }
        } elseif (str_starts_with($name, 'products.by')) {
            $seen = [];
            foreach ($this->products()->orderByDesc('published_at')->cursor() as $product) {
                $date = $product->published_at ?? $product->created_at;
                $path = match ($name) {
                    'products.byDate' => '/date/'.$date->toDateString(),
                    'products.byWeek' => '/week/'.$date->year.'/'.$date->weekOfYear,
                    'products.byMonth' => '/monthly/'.$date->year.'/'.$date->month,
                    'products.byYear' => '/yearly/'.$date->year,
                };
                if (! isset($seen[$path])) {
                    $seen[$path] = true;
                    yield $path;
                }
            }
        } else {
            foreach (Category::with('types')->withCount(['products' => fn ($q) => $q->approvedAndPublished()])->cursor() as $category) {
                $base = CategoryPageType::routeName($category->pageType());
                if ($name === 'topics.category') {
                    yield '/topics/'.$category->slug;
                } elseif ($name === $base) {
                    yield '/'.$category->pageType().'/'.$category->slug;
                } elseif ($name === $base.'.page') {
                    for ($page = 2; $page <= (int) ceil($category->products_count / 50); $page++) {
                        yield '/'.$category->pageType().'/'.$category->slug.'/page/'.$page;
                    }
                } elseif ($name === 'pseo.pricing' && $category->types->contains('name', 'Pricing')) {
                    yield '/software/'.$category->slug;
                } elseif (in_array($name, ['pseo.best', 'pseo.bestFor'], true) && $category->types->pluck('name')->intersect(['Software', 'Software Categories', 'Category'])->isNotEmpty()) {
                    if ($name === 'pseo.best') {
                        yield '/best/'.$category->slug;
                    } else {
                        foreach (Category::whereHas('types', fn ($q) => $q->where('name', 'Best for'))->cursor() as $audience) {
                            yield '/best/'.$category->slug.'/for/'.$audience->slug;
                        }
                    }
                }
            }
        }
    }

    private function products()
    {
        return Product::approvedAndPublished()->where(fn ($q) => $q->whereNull('generation_noindex')->orWhere('generation_noindex', false))->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function skipReason(Route $route): ?string
    {
        $name = $route->getName();
        $uri = $route->uri();
        if (isset($this->definitions()[$name])) {
            return null;
        }
        if (isset(config('agent_access.skip_routes')[$name])) {
            return config('agent_access.skip_routes')[$name];
        }
        if (isset(config('agent_access.skip_uris')[$uri])) {
            return config('agent_access.skip_uris')[$uri];
        }
        if (str_starts_with($uri, 'api/') || str_starts_with($uri, 'admin/')) {
            return 'API or administration endpoint.';
        }
        foreach ($route->gatherMiddleware() as $middleware) {
            if (preg_match('/^auth(?::|$)/', $middleware)) {
                return 'Authenticated content.';
            }
        }
        if (str_starts_with(ltrim($route->getActionName(), '\\'), 'Illuminate\\Routing\\RedirectController') || in_array($name, ['products.weekly.redirect', 'products.monthly.redirect', 'products.yearly.redirect'], true) || in_array($uri, ['{product_name}', 'free-todo-list-tool/{path?}'], true)) {
            return 'Legacy or current-period redirect.';
        }
        if (in_array($name, ['agent.index', 'agent.full', 'agent.policy', 'agent.robots', 'agent.markdown', 'agent.catalog'], true) || $name === 'products.document') {
            return 'Machine-readable endpoint.';
        }

        return null;
    }
}
