<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OutboundLinkRule;
use App\Services\OutboundLinkPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutboundLinkRuleController extends Controller
{
    private const MATCH_TYPES = [
        OutboundLinkRule::MATCH_TYPE_EXACT_URL,
        OutboundLinkRule::MATCH_TYPE_DOMAIN,
        OutboundLinkRule::MATCH_TYPE_DOMAIN_PATH_PREFIX,
    ];

    private const SOURCE_SCOPES = [
        OutboundLinkRule::SOURCE_SCOPE_ALL,
        'article',
        'product_link',
        'product_description',
        'maker_link',
        'product_social',
        'pricing_page',
        'ad',
        'footer_embed',
        'system_view',
    ];

    public function index(Request $request): View
    {
        $rules = OutboundLinkRule::query()
            ->orderByDesc('priority')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.outbound_links.rules.index', [
            'rules' => $rules,
            'matchTypes' => self::MATCH_TYPES,
            'sourceScopes' => self::SOURCE_SCOPES,
            'prefill' => [
                'name' => (string) $request->query('name', ''),
                'match_type' => (string) $request->query('match_type', OutboundLinkRule::MATCH_TYPE_EXACT_URL),
                'pattern' => (string) $request->query('pattern', ''),
                'source_scope' => (string) $request->query('source_scope', OutboundLinkRule::SOURCE_SCOPE_ALL),
                'rel_nofollow' => $request->boolean('rel_nofollow', false),
                'rel_ugc' => $request->boolean('rel_ugc', false),
                'rel_sponsored' => $request->boolean('rel_sponsored', false),
                'rel_noopener' => $request->boolean('rel_noopener', true),
                'rel_noreferrer' => $request->boolean('rel_noreferrer', true),
                'priority' => (int) $request->query('priority', 500),
                'notes' => (string) $request->query('notes', ''),
            ],
        ]);
    }

    public function store(Request $request, OutboundLinkPolicyService $policyService): RedirectResponse
    {
        $validated = $this->validateRule($request);
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        OutboundLinkRule::create($validated);
        $policyService->clearRuleCache();

        return redirect()
            ->route('admin.outbound-links.rules.index')
            ->with('success', 'Outbound link rule created successfully.');
    }

    public function update(Request $request, OutboundLinkRule $rule, OutboundLinkPolicyService $policyService): RedirectResponse
    {
        $validated = $this->validateRule($request);
        $validated['updated_by'] = $request->user()?->id;

        $rule->update($validated);
        $policyService->clearRuleCache();

        return redirect()
            ->route('admin.outbound-links.rules.index')
            ->with('success', 'Outbound link rule updated successfully.');
    }

    public function destroy(OutboundLinkRule $rule, OutboundLinkPolicyService $policyService): RedirectResponse
    {
        $rule->delete();
        $policyService->clearRuleCache();

        return redirect()
            ->route('admin.outbound-links.rules.index')
            ->with('success', 'Outbound link rule deleted successfully.');
    }

    private function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'match_type' => 'required|string|in:' . implode(',', self::MATCH_TYPES),
            'pattern' => 'required|string|max:2048',
            'source_scope' => 'required|string|in:' . implode(',', self::SOURCE_SCOPES),
            'priority' => 'required|integer|min:0|max:999999',
            'notes' => 'nullable|string|max:5000',
            'is_active' => 'nullable|boolean',
            'rel_nofollow' => 'nullable|boolean',
            'rel_ugc' => 'nullable|boolean',
            'rel_sponsored' => 'nullable|boolean',
            'rel_noopener' => 'nullable|boolean',
            'rel_noreferrer' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['rel_nofollow'] = $request->boolean('rel_nofollow');
        $validated['rel_ugc'] = $request->boolean('rel_ugc');
        $validated['rel_sponsored'] = $request->boolean('rel_sponsored');
        $validated['rel_noopener'] = $request->boolean('rel_noopener', true);
        $validated['rel_noreferrer'] = $request->boolean('rel_noreferrer', true);

        return $validated;
    }
}
