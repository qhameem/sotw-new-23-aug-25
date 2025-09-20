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
        $meta = PageMetaTag::where('page_id', $routeName)->first();

        if ($meta) {
            $category = $view->getData()['category'] ?? null;
            if ($category && $routeName === 'categories.show') {
                $view->with('meta_title', $category->name . ' - ' . config('app.name'));
            } else {
                $view->with('meta_title', $meta->meta_title ?? config('app.name'));
            }
            $view->with('meta_description', $meta->meta_description ?? '');
            $view->with('meta_og_image', $meta->og_image_path ? \Illuminate\Support\Facades\Storage::url($meta->og_image_path) : null);
        } else {
            $view->with('meta_title', config('app.name'));
            $view->with('meta_description', '');
            $view->with('meta_og_image', null);
        }
    }
}