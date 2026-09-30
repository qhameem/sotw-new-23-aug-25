<?php

namespace App\Services;

use App\Models\SystemErrorReport;
use App\Models\User;
use App\Notifications\SystemErrorReported;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SystemErrorReporter
{
    public function report(
        string $source,
        string $summary,
        ?string $details = null,
        array $context = [],
        string $severity = 'error',
        bool $notifyAdmins = true,
    ): ?SystemErrorReport {
        try {
            $safeDetails = $this->redact($details);
            $safeContext = $this->redactContext($context);
            $fingerprint = hash('sha256', $source.'|'.$summary);

            $report = SystemErrorReport::query()->where('fingerprint', $fingerprint)->first();
            $isNew = $report === null;

            if ($report) {
                $report->forceFill([
                    'severity' => $severity,
                    'details' => $safeDetails,
                    'context' => $safeContext,
                    'user_id' => auth()->id(),
                    'occurrence_count' => $report->occurrence_count + 1,
                    'last_occurred_at' => now(),
                    'resolved_at' => null,
                ])->save();
            } else {
                $report = SystemErrorReport::query()->create([
                    'fingerprint' => $fingerprint,
                    'severity' => $severity,
                    'source' => $source,
                    'summary' => Str::limit($summary, 255, ''),
                    'details' => $safeDetails,
                    'context' => $safeContext,
                    'user_id' => auth()->id(),
                    'first_occurred_at' => now(),
                    'last_occurred_at' => now(),
                ]);
            }

            if ($isNew && $notifyAdmins && in_array($severity, ['error', 'critical'], true)) {
                User::role('admin')->each(fn (User $admin) => $admin->notify(new SystemErrorReported($report)));
            }

            return $report;
        } catch (Throwable $exception) {
            Log::error('Failed to persist system error report.', [
                'source' => $source,
                'summary' => $summary,
                'reporting_exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function redact(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $patterns = [
            '/(authorization\s*:\s*bearer\s+)[^\s,]+/i' => '$1[REDACTED]',
            '/([?&](?:api[_-]?key|token|secret)=)[^&\s]+/i' => '$1[REDACTED]',
            '/("(?:api[_-]?key|access[_-]?token|secret)"\s*:\s*")[^"]+("?)/i' => '$1[REDACTED]$2',
        ];

        return Str::limit((string) preg_replace(array_keys($patterns), array_values($patterns), $value), 10000, '...');
    }

    private function redactContext(array $context): array
    {
        array_walk_recursive($context, function (&$value, $key): void {
            if (preg_match('/password|secret|token|authorization|api.?key/i', (string) $key)) {
                $value = '[REDACTED]';
            } elseif (is_string($value)) {
                $value = $this->redact($value);
            }
        });

        return $context;
    }
}
