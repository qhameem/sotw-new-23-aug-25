<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Services\BadgeService;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badgeService = app(BadgeService::class);
        $currentBadge = Badge::first();
        $embedData = $badgeService->getEmbedData();

        return view('site.badges.index', compact('currentBadge', 'embedData'));
    }
}
