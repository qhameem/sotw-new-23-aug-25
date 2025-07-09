<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function getTotalSessions()
    {
        try {
            $stats = $this->analyticsService->getStatsForCurrentYear();
            return response()->json($stats);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in AnalyticsController: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while fetching data from Google Analytics.'], 500);
        }
    }
}