<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MeasureResponsePerformance
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryDuration = 0.0;

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryDuration): void {
            $queryCount++;
            $queryDuration += $query->time;
        });

        $response = $next($request);
        $duration = (hrtime(true) - $startedAt) / 1_000_000;

        $response->headers->set('Server-Timing', sprintf(
            'app;dur=%.1f, db;dur=%.1f;desc="%d queries"',
            $duration,
            $queryDuration,
            $queryCount
        ));

        return $response;
    }
}
