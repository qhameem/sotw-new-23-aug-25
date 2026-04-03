<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Route;

class SeoComposer
{
    public function compose(View $view)
    {
        $routeName = Route::currentRouteName();
        // Try to fetch specific page SEO
        $meta = PageMetaTag::where('page_id', $routeName)->first();

        // If not found, fallback to global SEO
        if (!$meta) {
            $meta = PageMetaTag::where('page_id', 'global_defaults')->first();
        }

        $viewData = $view->getData();

        if ($meta) {
            $category = $viewData['category'] ?? null;
            if ($category && $routeName === 'categories.show') {
                if (!isset($viewData['meta_title'])) {
                    $view->with('meta_title', $category->name . ' - ' . config('app.name'));
                }
            } else {
                if (!isset($viewData['meta_title'])) {
                    $view->with('meta_title', $meta->meta_title ?? config('app.name'));
                }
            }
            
            if (!isset($viewData['meta_description'])) {
                $view->with('meta_description', $meta->meta_description ?? '');
            }
            
            if (!isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', $meta->og_image_path ? \Illuminate\Support\Facades\Storage::url($meta->og_image_path) : null);
            }
        } else {
            // Absolute default if nothing is configured
            if (!isset($viewData['meta_title'])) {
                $view->with('meta_title', config('app.name'));
            }
            if (!isset($viewData['meta_description'])) {
                $view->with('meta_description', '');
            }
            if (!isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', null);
            }
        }
    }
}