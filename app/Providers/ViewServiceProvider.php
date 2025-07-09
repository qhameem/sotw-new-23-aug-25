<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Category;
use App\Http\View\Composers\RightSidebarComposer;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Using a view composer to share the top categories with the navigation view
        View::composer('layouts.navigation', function ($view) {
            // Fetch top 5 "Software" categories (those not of type 'Pricing')
            $softwareCategories = Category::whereDoesntHave('types', function ($query) {
                $query->where('name', 'Pricing');
            })
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->take(5)
            ->get();

            // Fetch top 2 "Pricing" categories
            $pricingCategories = Category::whereHas('types', function ($query) {
                $query->where('name', 'Pricing');
            })
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->take(2)
            ->get();

            $view->with('softwareCategories', $softwareCategories)
                 ->with('pricingCategories', $pricingCategories);
        });

        View::composer('partials._right-sidebar', RightSidebarComposer::class);
    }
}
