<?php

namespace App\Http\Middleware;

use App\Support\ResponsePerformanceMetrics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MeasureResponsePerformance
{
    public const ATTRIBUTE = '_response_performance_metrics';

    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $metrics = new ResponsePerformanceMetrics;
        $request->attributes->set(self::ATTRIBUTE, $metrics);

        try {
            $response = $next($request);
            $duration = (hrtime(true) - $startedAt) / 1_000_000;

            $response->headers->set('Server-Timing', sprintf(
                'app;dur=%.1f, db;dur=%.1f;desc="%d queries"',
                $duration,
                $metrics->queryDuration,
                $metrics->queryCount
            ));

            return $response;
        } finally {
            $request->attributes->remove(self::ATTRIBUTE);
        }
    }
}
