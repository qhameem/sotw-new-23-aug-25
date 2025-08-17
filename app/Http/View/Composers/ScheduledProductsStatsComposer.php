<?php

namespace App\Http\View\Composers;

use App\Models\Product;
use Illuminate\View\View;

class ScheduledProductsStatsComposer
{
    public function compose(View $view)
    {
        $scheduledProductsStats = Product::where('approved', true)
            ->where('is_published', false)
            ->where('published_at', '>', now())
            ->whereNotNull('published_at')
            ->selectRaw('DATE(published_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $view->with('scheduledProductsStats', $scheduledProductsStats);
    }
}