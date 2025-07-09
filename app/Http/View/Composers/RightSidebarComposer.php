<?php

namespace App\Http\View\Composers;

use App\Models\Article;
use Illuminate\View\View;

class RightSidebarComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $staffPicks = Article::where('staff_pick', true)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->with('author', 'categories')
            ->latest('published_at')
            ->take(5)
            ->get();

        $view->with('staffPicks', $staffPicks);
    }
}