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
        $profileQueries = $request->isMethod('GET')
            && $request->routeIs('home')
            && in_array($request->server->get('REMOTE_ADDR'), ['127.0.0.1', '::1'], true)
            && $request->headers->get('X-Homepage-Query-Profile') === '1';
        $metrics = new ResponsePerformanceMetrics($profileQueries);
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

            if ($profileQueries && $response->isSuccessful()) {
                $response->headers->set('X-Homepage-Query-Profile', json_encode($metrics->queryProfile(), JSON_THROW_ON_ERROR));
            }

            return $response;
        } finally {
            $request->attributes->remove(self::ATTRIBUTE);
        }
    }
}
