<?php

namespace App\Http\Controllers;

use App\Services\PlatformStatsService;
use App\Services\ProductFilterNavigationService;
use Illuminate\View\View;

class PlatformStatsController extends Controller
{
    public function __invoke(
        PlatformStatsService $stats,
        ProductFilterNavigationService $navigation,
    ): View {
        return view('site.stats', $stats->get() + ['types' => $navigation->getTypes()]);
    }
}
