<?php

namespace App\Services;

use App\Jobs\RenderReadinessScan;
use App\Models\Product;
use App\Models\ToolScan;
use App\Support\ToolSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProductSubmissionReadiness
{
    public function __construct(private LaunchReadinessAuditService $audit) {}

    public function schedule(Product $product): void
    {
        try {
            $url = $this->audit->normalizeUrl(Str::before($product->link, '#'));
            $website = Str::before($url, '?');

            Cache::lock('product-readiness:'.hash('sha256', $website), 30)->block(3, function () use ($product, $url) {
                DB::transaction(function () use ($product, $url) {
                    $this->attachOrQueue($product, $url);
                });
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function attachOrQueue(Product $product, string $url): void
    {
        $urls = Product::equivalentLinkCandidates($url);

        $scans = ToolScan::query()
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->where(function ($query) use ($urls) {
                foreach (['normalized_url', 'final_url'] as $column) {
                    $query->orWhereIn($column, $urls);
                    foreach ($urls as $candidate) {
                        $escaped = strtr($candidate, ['!' => '!!', '%' => '!%', '_' => '!_']);
                        $query->orWhereRaw("$column LIKE ? ESCAPE '!'", [$escaped.'?%'])
                            ->orWhereRaw("$column LIKE ? ESCAPE '!'", [$escaped.'#%']);
                    }
                }
            });

        $public = (clone $scans)->where('save_to_history', true)->latest('scanned_at')->latest('id')->first();
        $scan = $public ?? (clone $scans)->where('status_label', 'Waiting to render')
            ->whereIn('id', Product::query()->select('launch_readiness_scan_id')->whereNotNull('launch_readiness_scan_id'))
            ->first();

        if ($scan) {
            $product->forceFill(['launch_readiness_scan_id' => $scan->id])->saveQuietly();

            return;
        }

        if ($scans->whereNotIn('status_label', ['Scan incomplete', 'Partial report'])->exists()) {
            return;
        }

        $report = $this->audit->pendingReport();
        $report['status_label'] = 'Waiting to render';
        $report['summary'] = array_replace($report['summary'], [
            'submitted_url' => $url,
            'normalized_url' => $url,
            'final_url' => $url,
            'final_host' => parse_url($url, PHP_URL_HOST),
        ]);

        $scan = ToolScan::create([
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => (string) Str::uuid(),
            'submitted_url' => $url,
            'normalized_url' => $url,
            'status_label' => 'Waiting to render',
            'save_to_history' => false,
            'audit_payload' => $report,
        ]);

        $product->forceFill(['launch_readiness_scan_id' => $scan->id])->saveQuietly();
        RenderReadinessScan::dispatch($scan, true)->onConnection('database');
    }

    public function summary(Product $product): ?array
    {
        if (! $product->launch_readiness_scan_id) {
            return null;
        }

        $scan = ToolScan::find($product->launch_readiness_scan_id);

        if ($scan?->status_label === 'Waiting to render') {
            return ['state' => 'pending'];
        }

        if (! $scan?->save_to_history) {
            return ['state' => 'unavailable'];
        }

        return [
            'state' => 'complete',
            'score' => $scan->launch_score,
            'passed' => $scan->passed_checks,
            'warnings' => $scan->warning_checks,
            'errors' => $scan->failed_checks,
            'url' => route('launch-readiness.results.show', [
                'toolSlug' => app(ToolSettings::class)->slug(ToolSettings::LAUNCH_READINESS_KEY),
                'toolScan' => $scan,
            ]),
        ];
    }
}
