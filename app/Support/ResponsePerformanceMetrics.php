<?php

namespace App\Support;

class ResponsePerformanceMetrics
{
    private array $queryGroups = [];

    public function __construct(private readonly bool $profileQueries = false) {}

    public int $queryCount = 0;

    public float $queryDuration = 0.0;

    public function recordQuery(float $duration): void
    {
        $this->queryCount++;
        $this->queryDuration += $duration;

        if (! $this->profileQueries) {
            return;
        }

        $source = 'framework-or-view';
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $class = $frame['class'] ?? '';
            if (str_starts_with($class, 'App\\Http\\Controllers\\')
                || str_starts_with($class, 'App\\Services\\')
                || str_starts_with($class, 'App\\View\\Components\\')) {
                $source = class_basename($class).'@'.$frame['function'];
                break;
            }
        }

        $this->queryGroups[$source] ??= ['count' => 0, 'ms' => 0.0];
        $this->queryGroups[$source]['count']++;
        $this->queryGroups[$source]['ms'] += $duration;
    }

    public function queryProfile(): array
    {
        $groups = $this->queryGroups;
        uasort($groups, static fn (array $left, array $right): int => $right['ms'] <=> $left['ms']);

        return array_slice($groups, 0, 12, true);
    }
}
