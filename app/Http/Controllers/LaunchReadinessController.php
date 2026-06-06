<?php

namespace App\Http\Controllers;

use App\Models\ToolScan;
use App\Models\ToolUser;
use App\Services\LaunchReadinessAuditService;
use App\Support\LaunchReadinessGuestSession;
use App\Support\LaunchReadinessPageData;
use App\Support\ToolSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LaunchReadinessController extends Controller
{
    public function __construct(
        private readonly LaunchReadinessAuditService $auditService,
        private readonly ToolSettings $toolSettings,
        private readonly LaunchReadinessPageData $pageData,
        private readonly LaunchReadinessGuestSession $guestSession,
    ) {}

    public function index(): View
    {
        return view('tools.launch-readiness.index', $this->pageData->merge([
            'report' => $this->auditService->pendingReport(),
            'scan' => null,
            'recentHistory' => $this->recentHistory(),
            'toolTablesReady' => $this->toolTablesReady(),
        ]));
    }

    public function analyze(Request $request): RedirectResponse|JsonResponse
    {
        return $this->handleAnalyze($request);
    }

    private function handleAnalyze(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->toolTablesReady()) {
            throw ValidationException::withMessages([
                'url' => 'The launch readiness tool tables are not migrated yet. Run the new migration first.',
            ]);
        }

        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048'],
            'save_to_history' => ['nullable', 'boolean'],
        ]);

        $toolUser = Auth::guard('tool_user')->user();
        $guestHash = $toolUser ? null : $this->guestSession->hash($request);

        $this->ensureDailyLimit($toolUser, $guestHash);

        $report = $this->auditService->run($validated['url']);
        $scan = ToolScan::create([
            'tool_user_id' => $toolUser?->id,
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => (string) Str::uuid(),
            'submitted_url' => $validated['url'],
            'normalized_url' => $report['summary']['normalized_url'],
            'final_url' => $report['summary']['final_url'],
            'final_host' => $report['summary']['final_host'],
            'guest_hash' => $guestHash,
            'launch_score' => $report['launch_score'],
            'seo_score' => $report['seo_score'],
            'ai_score' => $report['ai_score'],
            'trust_score' => $report['trust_score'],
            'passed_checks' => $report['passed_checks'],
            'warning_checks' => $report['warning_checks'],
            'failed_checks' => $report['failed_checks'],
            'status_label' => $report['status_label'],
            'save_to_history' => $request->boolean('save_to_history'),
            'audit_payload' => $report,
            'scanned_at' => now(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'result_url' => route('launch-readiness.results.show', [
                    'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
                    'toolScan' => $scan,
                ]),
                'share_target' => $report['summary']['final_host']
                    ?: (parse_url($report['summary']['final_url'] ?? '', PHP_URL_HOST) ?: parse_url($validated['url'], PHP_URL_HOST) ?: 'your site'),
                'share_summary' => [
                    'launch_score' => $report['launch_score'],
                    'passed_checks' => $report['passed_checks'],
                    'warning_checks' => $report['warning_checks'],
                    'failed_checks' => $report['failed_checks'],
                ],
                'page_title' => (($report['summary']['page_title'] ?? null) ?: 'Launch Readiness Result').' - Software on the Web',
                'notice_message' => $scan->save_to_history
                    ? ''
                    : 'This result was not saved to the public history feed. Anyone with this result URL can still open it.',
                'report_html' => view('tools.launch-readiness.partials.report', [
                    'report' => $report,
                ])->render(),
            ]);
        }

        return redirect()->route('launch-readiness.results.show', [
            'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
            'toolScan' => $scan,
        ]);
    }

    public function show(string $toolSlug, ToolScan $toolScan): View
    {
        return view('tools.launch-readiness.show', $this->pageData->merge([
            'report' => $toolScan->audit_payload,
            'scan' => $toolScan,
            'recentHistory' => $this->recentHistory(),
            'toolTablesReady' => $this->toolTablesReady(),
        ]));
    }

    public function history(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $perPage = (int) $request->query('per_page', 10);
        $allowedPerPage = [10, 20, 30, 50];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $history = $this->toolTablesReady()
            ? $this->latestHistoryQuery($query)
                ->paginate($perPage)
                ->withQueryString()
            : new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: $perPage,
                currentPage: LengthAwarePaginator::resolveCurrentPage(),
                options: [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ],
            );

        return view('tools.launch-readiness.history', $this->pageData->merge([
            'history' => $history,
            'query' => $query,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
            'toolTablesReady' => $this->toolTablesReady(),
        ]));
    }

    private function ensureDailyLimit(?ToolUser $toolUser, ?string $guestHash): void
    {
        if ($toolUser?->isAdmin()) {
            return;
        }

        $query = ToolScan::query()
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);

        if ($toolUser) {
            $query->where('tool_user_id', $toolUser->id);
        } else {
            $query->where('guest_hash', $guestHash);
        }

        if ($query->count() >= config('launch_readiness.daily_limit', 20)) {
            throw ValidationException::withMessages([
                'url' => 'Daily scan limit reached. Please come back tomorrow.',
            ]);
        }
    }

    private function recentHistory()
    {
        if (! $this->toolTablesReady()) {
            return collect();
        }

        return ToolScan::query()
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->where('save_to_history', true)
            ->latest('scanned_at')
            ->limit(8)
            ->get();
    }

    private function latestHistoryQuery(string $query)
    {
        $latestScanIds = ToolScan::query()
            ->selectRaw('MAX(id) as id')
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->where('save_to_history', true)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('submitted_url', 'like', '%'.$query.'%')
                        ->orWhere('normalized_url', 'like', '%'.$query.'%')
                        ->orWhere('final_url', 'like', '%'.$query.'%');
                });
            })
            ->groupBy(DB::raw('LOWER(normalized_url)'));

        return ToolScan::query()
            ->whereIn('id', $latestScanIds)
            ->latest('scanned_at')
            ->latest('id');
    }

    private function toolTablesReady(): bool
    {
        return Schema::hasTable('tool_scans')
            && Schema::hasTable('tool_users')
            && Schema::hasTable('tool_auth_magic_links');
    }
}
