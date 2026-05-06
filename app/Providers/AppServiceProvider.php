<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CategoryClassifier;
use App\Services\TechStackDetectorService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\Models\PageMetaTag;
use App\Models\Product;
use Illuminate\Support\Facades\Auth; // Add this line
use App\Http\View\Composers\SeoComposer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use App\Http\View\Composers\ScheduledProductsStatsComposer;
use App\Services\CategoryNavigationService;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CategoryClassifier::class, function ($app) {
            return new CategoryClassifier();
        });

        $this->app->singleton(TechStackDetectorService::class, function ($app) {
            return new TechStackDetectorService();
        });

        $this->app->singleton(CategoryNavigationService::class, function ($app) {
            return new CategoryNavigationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (class_exists('Spatie\\Permission\\PermissionServiceProvider')) {
            Blade::if('role', function ($role) {
                return Auth::check() && Auth::user()->hasRole($role);
            });
        }

        View::composer('partials._right-sidebar-usermenu', function ($view) {
            if (Auth::check() && Auth::user()->hasRole('admin')) {
                $pendingProducts = Product::where('approved', false)->get();
                $view->with('pendingProducts', $pendingProducts);
            }
        });

        View::composer('components.top-bar', function ($view) {
            $categoryNavigation = app(CategoryNavigationService::class);

            if (Auth::check() && Auth::user()->hasRole('admin')) {
                $view->with('pendingApprovalCount', Product::where('approved', false)->count());
            }

            $view->with('categoryNavigationGroups', $categoryNavigation->getMenuGroups())
                ->with('defaultCategoryNavigationGroupKey', $categoryNavigation->getDefaultGroupKey());
        });

        View::composer(['partials._mobile-footer-menu', 'components.mobile-categories-menu'], function ($view) {
            $categoryNavigation = app(CategoryNavigationService::class);

            $view->with('categoryNavigationGroups', $categoryNavigation->getMenuGroups())
                ->with('defaultCategoryNavigationGroupKey', $categoryNavigation->getDefaultGroupKey());
        });

        View::composer(
            'layouts.app',
            SeoComposer::class
        );

        View::composer(['layouts.app', 'layouts.submission'], function ($view) {
            $view->with('popularSearchProducts', $this->getPopularSearchProducts())
                ->with('popularSearchCategories', $this->getPopularSearchCategories());
        });

        View::composer(['layouts.app', 'layouts.submission', 'layouts.guest', 'layouts.todolist'], function ($view) {
            $globalMeta = PageMetaTag::query()
                ->select(['og_image_path'])
                ->where('page_id', 'global_defaults')
                ->first();

            $view->with(
                'globalDefaultOgImageUrl',
                $globalMeta?->og_image_path ? $this->absoluteUrl(\Illuminate\Support\Facades\Storage::url($globalMeta->og_image_path)) : null
            );
        });

        View::composer('partials._right-sidebar', ScheduledProductsStatsComposer::class);

        $this->loadThemeSettings();
    }

    /**
     * Load theme settings from JSON file and override config.
     */
    protected function loadThemeSettings(): void
    {
        $settingsPath = storage_path('app/theme_settings.json');

        if (File::exists($settingsPath)) {
            $settings = json_decode(File::get($settingsPath), true);

            if (is_array($settings)) {
                if (array_key_exists('font_url', $settings)) {
                    Config::set('theme.font_url', $settings['font_url']);
                }
                if (array_key_exists('font_family', $settings)) {
                    Config::set('theme.font_family', $settings['font_family']);
                }
                if (isset($settings['font_color'])) {
                    Config::set('theme.font_color', $settings['font_color']);
                }
                if (isset($settings['body_text_color'])) {
                    Config::set('theme.body_text_color', $settings['body_text_color']);
                }
                if (!empty($settings['primary_color'])) {
                    // Resolve color and set it
                    $resolvedColor = $this->resolveColor($settings['primary_color']);
                    Config::set('theme.primary_color', $resolvedColor);
                }
                // Add logo and favicon settings
                if (isset($settings['logo_url'])) { // Can be null if removed
                    Config::set('theme.logo_url', $settings['logo_url']);
                }
                if (isset($settings['logo_alt_text'])) { // Can be null
                    Config::set('theme.logo_alt_text', $settings['logo_alt_text']);
                }
                if (isset($settings['favicon_url'])) { // Can be null if removed
                    Config::set('theme.favicon_url', $settings['favicon_url']);
                }
                if (isset($settings['primary_button_text_color'])) { // Can be null
                    Config::set('theme.primary_button_text_color', $settings['primary_button_text_color']);
                }
                if (isset($settings['submission_bg_url'])) { // Can be null
                    Config::set('theme.submission_bg_url', $settings['submission_bg_url']);
                }
                if (isset($settings['navbar_bg_color'])) {
                    Config::set('theme.navbar_bg_color', $settings['navbar_bg_color']);
                }
                if (isset($settings['body_bg_color'])) {
                    Config::set('theme.body_bg_color', $settings['body_bg_color']);
                }
            }
        }

        Config::set('theme.font_css_stack', $this->normalizeFontFamilyForCss(
            Config::get('theme.font_family', 'Inter')
        ));
    }

    private function absoluteUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url($value);
    }

    private function normalizeFontFamilyForCss(?string $fontFamily): string
    {
        $fontFamily = trim((string) $fontFamily);

        if ($fontFamily === '') {
            return "'Inter', sans-serif";
        }

        $genericFamilies = [
            'serif',
            'sans-serif',
            'monospace',
            'cursive',
            'fantasy',
            'system-ui',
            'ui-serif',
            'ui-sans-serif',
            'ui-monospace',
            'ui-rounded',
            'emoji',
            'math',
            'fangsong',
            '-apple-system',
            'BlinkMacSystemFont',
        ];

        $segments = array_values(array_filter(
            array_map('trim', explode(',', $fontFamily)),
            fn ($segment) => $segment !== ''
        ));

        if (empty($segments)) {
            return "'Inter', sans-serif";
        }

        $normalizedSegments = array_map(function (string $segment) use ($genericFamilies) {
            $unquotedSegment = trim($segment, "\"'");

            if (in_array($unquotedSegment, $genericFamilies, true)) {
                return $unquotedSegment;
            }

            return "'" . str_replace("'", "\\'", $unquotedSegment) . "'";
        }, $segments);

        $hasGenericFallback = collect($segments)->contains(function (string $segment) use ($genericFamilies) {
            return in_array(trim($segment, "\"'"), $genericFamilies, true);
        });

        if (!$hasGenericFallback) {
            $normalizedSegments[] = 'sans-serif';
        }

        return implode(', ', $normalizedSegments);
    }

    private function resolveColor(string $colorValue): string
    {
        // If it's a hex or HSL color, return it directly.
        if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $colorValue) || str_starts_with($colorValue, 'hsl')) {
            return $colorValue;
        }

        // Otherwise, assume it's a Tailwind color class (e.g., "blue-500").
        return Cache::rememberForever("tailwind_color_hex_{$colorValue}", function () use ($colorValue) {
            try {
                $tailwindColors = config('tailwindcolors');
                list($colorName, $shade) = explode('-', $colorValue);

                if (isset($tailwindColors[$colorName][$shade])) {
                    return $tailwindColors[$colorName][$shade];
                }
            } catch (\Exception $e) {
                // Log error or handle gracefully
            }

            // Fallback to a default color
            return '#3b82f6';
        });
    }

    protected function getPopularSearchProducts(): array
    {
        return Cache::remember('global_search.popular_products.v2', now()->addMinutes(30), function () {
            return Product::query()
                ->select(['id', 'name', 'slug', 'tagline', 'logo', 'link', 'votes_count', 'outbound_clicks_count', 'published_at'])
                ->where('approved', true)
                ->where('is_published', true)
                ->orderByDesc('votes_count')
                ->orderByDesc('outbound_clicks_count')
                ->orderByDesc('published_at')
                ->limit(6)
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'tagline' => $product->tagline,
                    'logo_url' => $product->logo_url,
                    'votes_count' => (int) $product->votes_count,
                    'url' => route('products.show', ['product' => $product->slug]),
                ])
                ->values()
                ->all();
        });
    }

    protected function getPopularSearchCategories(): array
    {
        return Cache::remember('global_search.popular_categories', now()->addMinutes(30), function () {
            return Category::query()
                ->select(['id', 'name', 'slug'])
                ->withCount([
                    'products' => fn ($query) => $query
                        ->where('approved', true)
                        ->where('is_published', true),
                ])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products_count' => (int) $category->products_count,
                    'url' => route('categories.show', ['category' => $category->slug]),
                ])
                ->values()
                ->all();
        });
    }
}
