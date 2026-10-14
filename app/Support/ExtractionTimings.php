<?php

namespace App\Support;

use Illuminate\Http\Request;

class ExtractionTimings
{
    private array $started = [];

    private array $elapsed = [];

    public function __construct(private Request $request) {}

    public static function forRequest(Request $request): self
    {
        if (! $request->attributes->has(self::class)) {
            $request->attributes->set(self::class, new self($request));
        }

        return $request->attributes->get(self::class);
    }

    public function start(string $phase): void
    {
        $this->started[$phase] = hrtime(true);
    }

    public function stop(string $phase): void
    {
        if (isset($this->started[$phase])) {
            $this->elapsed[$phase] = ($this->elapsed[$phase] ?? 0) + (hrtime(true) - $this->started[$phase]) / 1e9;
            unset($this->started[$phase]);
        }
    }

    public function measure(string $phase, callable $work): mixed
    {
        $this->start($phase);
        try {
            return ExtractionReport::forRequest($this->request)->measure($phase, $work);
        } finally {
            $this->stop($phase);
        }
    }

    public function payload(Request $request): array
    {
        if (! $request->user()?->hasRole('admin')) {
            return [];
        }
        $phases = $this->elapsed;
        foreach ($this->started as $phase => $start) {
            $phases[$phase] = ($phases[$phase] ?? 0) + (hrtime(true) - $start) / 1e9;
        }

        return ['phase_timings' => array_map(fn ($seconds) => round($seconds, 3), $phases), ...ExtractionReport::forRequest($this->request)->payload($request)];
    }
}
