<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Request;

class SeoComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $routeName = Request::route()->getName();
        $metaTags = PageMetaTag::where('page_id', $routeName)->first();

        if ($metaTags) {
            $view->with('meta_title', $metaTags->meta_title);
            $view->with('meta_description', $metaTags->meta_description);
        }
    }
}