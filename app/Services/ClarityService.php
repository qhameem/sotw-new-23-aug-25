<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClarityService
{
    protected $apiKey;
    protected $projectId;
    protected $insightsUrl;

    public function __construct()
    {
        $this->apiKey = config('clarity.api_key');
        $this->projectId = config('clarity.project_id');
        $this->insightsUrl = 'https://www.clarity.ms/export-data/api/v1/project-live-insights';
    }

    public function getTotalSessionsForCurrentYear()
    {
        $cacheKey = 'clarity_total_sessions_current_year_v3';
        $cacheDuration = 60; // Cache for 60 minutes

        return Cache::remember($cacheKey, $cacheDuration, function () {
            $totalSessions = 0;
            $startDate = Carbon::now()->startOfYear();
            $endDate = Carbon::now();
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $days = $currentDate->diffInDays($endDate);
                $numOfDays = $days >= 3 ? 3 : ($days + 1);

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->get($this->insightsUrl, [
                    'projectId' => $this->projectId,
                    'numOfDays' => $numOfDays,
                    'dimension1' => 'day',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Clarity API Response:', is_array($data) ? $data : [$data]);
                    if (is_array($data)) {
                        $totalSessions += array_sum(array_column($data, 'sessions'));
                    }
                } else {
                    Log::error('Clarity API call failed.', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }

                $currentDate->addDays($numOfDays);
            }

            return $totalSessions;
        });
    }
}