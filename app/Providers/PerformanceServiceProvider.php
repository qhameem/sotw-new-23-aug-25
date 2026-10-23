<?php

namespace App\Providers;

use App\Http\Middleware\MeasureResponsePerformance;
use App\Support\ResponsePerformanceMetrics;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class PerformanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DB::listen(static function (QueryExecuted $query): void {
            if (! app()->bound('request')) {
                return;
            }

            $metrics = app('request')->attributes->get(MeasureResponsePerformance::ATTRIBUTE);

            if ($metrics instanceof ResponsePerformanceMetrics) {
                $metrics->queryCount++;
                $metrics->queryDuration += $query->time;
            }
        });
    }
}
