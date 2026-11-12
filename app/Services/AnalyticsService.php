<?php

namespace App\Services;

use Spatie\Analytics\Facades\Analytics;
use Spatie\Analytics\Period;
use Carbon\Carbon;

class AnalyticsService
{
    public const MONTHLY_VISITORS_CACHE_KEY = 'site.pricing.monthly_visitors';

    public function fetchMonthlyVisitors(): ?int
    {
        $credentialsPath = config('analytics.service_account_credentials_json');
        if (!config('analytics.property_id') || !is_string($credentialsPath) || !is_file($credentialsPath)) {
            return null;
        }

        $end = Carbon::yesterday();
        $period = Period::create($end->copy()->subDays(29), $end);
        $row = Analytics::get($period, ['activeUsers'])->first();

        return isset($row['activeUsers']) ? (int) $row['activeUsers'] : null;
    }

    public function getStatsForCurrentYear()
    {
        $credentialsPath = config('analytics.service_account_credentials_json');
        if (empty(config('analytics.property_id')) || empty($credentialsPath) || !file_exists($credentialsPath)) {
            return ['error' => 'Google Analytics is not configured correctly.'];
        }

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