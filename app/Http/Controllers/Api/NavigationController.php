<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CategoryNavigationService;

class NavigationController extends Controller
{
    public function categories(CategoryNavigationService $categoryNavigation)
    {
        return response()->json([
            'default_group_key' => $categoryNavigation->getDefaultGroupKey(),
            'groups' => $categoryNavigation->getMenuGroups(),
        ]);
    }
}
