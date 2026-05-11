<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutboundLinkOccurrence;
use App\Models\OutboundLinkRule;
use App\Services\OutboundLinkPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OutboundLinkOccurrenceController extends Controller
{
    public function index(Request $request): View
    {
        $query = OutboundLinkOccurrence::query()->orderBy('domain')->orderByDesc('last_seen_at');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('domain', 'like', '%' . $search . '%')
                    ->orWhere('normalized_url', 'like', '%' . $search . '%')
                    ->orWhere('source_title', 'like', '%' . $search . '%');
            });
        }

        if ($sourceType = trim((string) $request->query('source_type'))) {
            $query->where('source_type', $sourceType);
        }

        $occurrences = $query->paginate(50)->withQueryString();

        return view('admin.outbound_links.occurrences.index', [
            'occurrences' => $occurrences,
            'sourceTypes' => OutboundLinkOccurrence::query()
                ->select('source_type')
                ->distinct()
                ->orderBy('source_type')
                ->pluck('source_type'),
        ]);
    }

    public function rescan(OutboundLinkPolicyService $policyService): RedirectResponse
    {
        $policyService->rescanOccurrences();

        return redirect()
            ->route('admin.outbound-links.occurrences.index')
            ->with('success', 'Outbound links rescanned successfully.');
    }

    public function quickAllow(Request $request, OutboundLinkPolicyService $policyService): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|string|max:2048',
            'mode' => 'required|string|in:exact_url,domain,domain_path_prefix',
            'source_scope' => 'nullable|string|max:64',
        ]);

        $url = trim($validated['url']);
        $mode = $validated['mode'];
        $pattern = $this->patternForMode($url, $mode);

        if ($pattern === null) {
            return back()->with('error', 'Could not build a rule from that URL.');
        }

        OutboundLinkRule::create([
            'name' => $this->buildRuleName($pattern, $mode),
            'match_type' => $mode,
            'pattern' => $pattern,
            'source_scope' => $validated['source_scope'] ?: OutboundLinkRule::SOURCE_SCOPE_ALL,
            'rel_nofollow' => false,
            'rel_ugc' => false,
            'rel_sponsored' => false,
            'rel_noopener' => true,
            'rel_noreferrer' => true,
            'priority' => 500,
            'is_active' => true,
            'notes' => 'Created from discovered links.',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $policyService->clearRuleCache();

        return redirect()
            ->route('admin.outbound-links.rules.index')
            ->with('success', 'Dofollow exception created successfully.');
    }

    private function patternForMode(string $url, string $mode): ?string
    {
        if ($mode === OutboundLinkRule::MATCH_TYPE_EXACT_URL) {
            return app(OutboundLinkPolicyService::class)->normalizeUrl($url);
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = preg_replace('/^www\./i', '', strtolower($host)) ?? '';

        if ($mode === OutboundLinkRule::MATCH_TYPE_DOMAIN) {
            return $host;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? '/' . ltrim($path, '/') : '/';
        $path = $path !== '/' ? rtrim($path, '/') : $path;

        return $host . $path;
    }

    private function buildRuleName(string $pattern, string $mode): string
    {
        return match ($mode) {
            OutboundLinkRule::MATCH_TYPE_DOMAIN => 'Dofollow domain: ' . $pattern,
            OutboundLinkRule::MATCH_TYPE_DOMAIN_PATH_PREFIX => 'Dofollow path: ' . $pattern,
            default => 'Dofollow URL: ' . Str::limit($pattern, 80),
        };
    }
}
