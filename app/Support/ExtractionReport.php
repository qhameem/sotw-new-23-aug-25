<?php

namespace App\Support;

use Illuminate\Http\Request;

class ExtractionReport
{
    private array $entries = [];

    private $listener = null;

    public function __construct(private Request $request) {}

    public static function current(): self
    {
        return self::forRequest(request());
    }

    public static function forRequest(Request $request): self
    {
        if (! $request->attributes->has(self::class)) {
            $request->attributes->set(self::class, new self($request));
        }

        return $request->attributes->get(self::class);
    }

    public function enabled(): bool
    {
        return (bool) $this->request->user()?->hasRole('admin');
    }

    public function listen(callable $listener): void
    {
        $this->listener = $listener;
    }

    public function record(string $stage, string $message, string $level = 'info'): void
    {
        if (! $this->enabled()) {
            return;
        }
        $entry = ['id' => count($this->entries) + 1, 'time' => (new \DateTimeImmutable)->format(DATE_ATOM), 'stage' => $stage, 'level' => $level, 'message' => $message];
        $this->entries[] = $entry;
        if ($this->listener) {
            ($this->listener)($entry);
        }
    }

    public function measure(string $stage, callable $work): mixed
    {
        $start = hrtime(true);
        $this->record($stage, 'Started');
        try {
            $result = $work();
            $this->record($stage, 'Completed in '.round((hrtime(true) - $start) / 1e9, 3).'s', 'success');

            return $result;
        } catch (\Throwable $error) {
            $this->record($stage, 'Failed ('.class_basename($error).'). See server logs for details.', 'error');
            throw $error;
        }
    }

    public function payload(Request $request): array
    {
        return $request->user()?->hasRole('admin') ? ['extraction_report' => $this->entries] : [];
    }
}
