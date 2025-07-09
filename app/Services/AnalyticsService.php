<?php

namespace App\Services;

use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Carbon\Carbon;

class AnalyticsService
{
    public function getStatsForCurrentYear()
    {
        $startDate = Carbon::now()->startOfYear();
        $endDate = Carbon::now();

        $period = Period::create($startDate, $endDate);

        $analyticsData = Analytics::get($period, ['sessions', 'screenPageViews']);

        $stats = [
            'sessions' => 0,
            'screenPageViews' => 0,
        ];

        if ($analyticsData->count() > 0) {
            $row = $analyticsData->first();
            $stats['sessions'] = $row['sessions'];
            $stats['screenPageViews'] = $row['screenPageViews'];
        }

        return $stats;
    }
}