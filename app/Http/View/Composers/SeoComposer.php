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

        $view->with('meta_title', $meta->meta_title ?? config('app.name'));
        $view->with('meta_description', $meta->meta_description ?? '');
    }
}