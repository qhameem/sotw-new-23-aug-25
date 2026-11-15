<?php

namespace App\Jobs;

use App\Models\ToolScan;
use App\Services\LaunchReadinessAuditService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RenderReadinessScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 75;

    public function __construct(public ToolScan $scan, public bool $saveToHistory)
    {
        $this->onQueue('readiness-render');
    }

    public function handle(LaunchReadinessAuditService $audit): void
    {
        if ($this->scan->status_label !== 'Waiting to render') {
            return;
        }

        $this->finish($audit->run($this->scan->submitted_url, progress: function (string $step): void {
            $report = $this->scan->audit_payload;
            $report['progress_step'] = $step;
            $this->scan->update(['audit_payload' => $report]);
        }));
    }

    public function failed(Throwable $exception): void
    {
        $report = $this->scan->audit_payload;
        $report['status_label'] = 'Scan incomplete';
        $report['summary']['incomplete_reason'] = 'The rendering job could not finish. Please try again.';
        $this->finish($report);
    }

    private function finish(array $report): void
    {
        $this->scan->update(array_intersect_key($report, array_flip([
            'launch_score', 'seo_score', 'ai_score', 'trust_score',
            'passed_checks', 'warning_checks', 'failed_checks', 'status_label',
        ])) + [
            'normalized_url' => $report['summary']['normalized_url'],
            'final_url' => $report['summary']['final_url'],
            'final_host' => $report['summary']['final_host'] ?? null,
            'audit_payload' => $report,
            'save_to_history' => $this->saveToHistory && empty($report['summary']['incomplete_reason']),
            'scanned_at' => now(),
        ]);
    }
}
