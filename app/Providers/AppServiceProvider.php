<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CategoryClassifier;
use App\Services\TechStackDetectorService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use App\Models\Product;
use Illuminate\Support\Facades\Auth; // Add this line
use App\Http\View\Composers\SeoComposer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use App\Http\View\Composers\ScheduledProductsStatsComposer;

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

        View::composer(
            'layouts.app',
            SeoComposer::class
        );

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
                if (!empty($settings['font_url'])) {
                    Config::set('theme.font_url', $settings['font_url']);
                }
                if (!empty($settings['font_family'])) {
                    Config::set('theme.font_family', $settings['font_family']);
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
            }
        }
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
}
