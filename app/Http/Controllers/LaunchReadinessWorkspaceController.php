<?php

namespace App\Http\Controllers;

use App\Models\ToolAuthMagicLink;
use App\Models\ToolScan;
use App\Models\ToolUser;
use App\Services\LaunchReadinessAuditService;
use App\Support\LaunchReadinessBranding;
use App\Support\LaunchReadinessPageData;
use App\Support\ToolSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LaunchReadinessWorkspaceController extends Controller
{
    public function __construct(
        private readonly LaunchReadinessAuditService $auditService,
        private readonly ToolSettings $toolSettings,
        private readonly LaunchReadinessPageData $pageData,
        private readonly LaunchReadinessBranding $branding,
    ) {}

    public function dashboard(Request $request): View
    {
        $toolUser = $this->toolUser();
        $scans = $toolUser->scans()
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->latest('scanned_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('tools.launch-readiness.workspace.dashboard', $this->workspaceData($toolUser, [
            'scans' => $scans,
            'dailyAllowed' => $this->dailyLimit(),
            'usedToday' => $this->usedToday($toolUser),
            'remainingToday' => max(0, $this->dailyLimit() - $this->usedToday($toolUser)),
        ]));
    }

    public function clearScans(): RedirectResponse
    {
        $toolUser = $this->toolUser();

        $toolUser->scans()
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->delete();

        return back()->with('status', 'All of your launch-readiness scans were cleared.');
    }

    public function bulkScans(Request $request): RedirectResponse
    {
        $toolUser = $this->toolUser();

        $validated = $request->validate([
            'action' => ['required', Rule::in(['delete', 'retest'])],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
        ]);

        $scans = ToolScan::query()
            ->where('tool_user_id', $toolUser->id)
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->whereIn('id', $validated['selected'])
            ->get();

        if ($scans->isEmpty()) {
            return back()->withErrors([
                'dashboard' => 'Select at least one test first.',
            ]);
        }

        if ($validated['action'] === 'delete') {
            ToolScan::query()
                ->whereIn('id', $scans->pluck('id'))
                ->delete();

            return back()->with('status', 'Selected tests were deleted.');
        }

        $remaining = max(0, $this->dailyLimit() - $this->usedToday($toolUser));

        if ($scans->count() > $remaining) {
            return back()->withErrors([
                'dashboard' => "You can retest {$remaining} more site(s) today.",
            ]);
        }

        foreach ($scans as $scan) {
            $report = $this->auditService->run($scan->submitted_url);

            ToolScan::create([
                'tool_user_id' => $toolUser->id,
                'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
                'result_token' => (string) \Illuminate\Support\Str::uuid(),
                'submitted_url' => $scan->submitted_url,
                'normalized_url' => $report['summary']['normalized_url'],
                'final_url' => $report['summary']['final_url'],
                'final_host' => $report['summary']['final_host'],
                'guest_hash' => null,
                'launch_score' => $report['launch_score'],
                'seo_score' => $report['seo_score'],
                'ai_score' => $report['ai_score'],
                'trust_score' => $report['trust_score'],
                'passed_checks' => $report['passed_checks'],
                'warning_checks' => $report['warning_checks'],
                'failed_checks' => $report['failed_checks'],
                'status_label' => $report['status_label'],
                'save_to_history' => true,
                'audit_payload' => $report,
                'scanned_at' => now(),
            ]);
        }

        return back()->with('status', 'Selected tests were retested.');
    }

    public function recheck(ToolScan $toolScan): RedirectResponse
    {
        $toolUser = $this->toolUser();
        $this->authorizeScanOwner($toolScan, $toolUser);

        $remaining = max(0, $this->dailyLimit() - $this->usedToday($toolUser));

        if ($remaining < 1) {
            return back()->withErrors([
                'dashboard' => 'Daily scan limit reached. Please come back tomorrow.',
            ]);
        }

        $report = $this->auditService->run($toolScan->submitted_url);
        $newScan = ToolScan::create([
            'tool_user_id' => $toolUser->id,
            'tool_key' => ToolSettings::LAUNCH_READINESS_KEY,
            'result_token' => (string) \Illuminate\Support\Str::uuid(),
            'submitted_url' => $toolScan->submitted_url,
            'normalized_url' => $report['summary']['normalized_url'],
            'final_url' => $report['summary']['final_url'],
            'final_host' => $report['summary']['final_host'],
            'guest_hash' => null,
            'launch_score' => $report['launch_score'],
            'seo_score' => $report['seo_score'],
            'ai_score' => $report['ai_score'],
            'trust_score' => $report['trust_score'],
            'passed_checks' => $report['passed_checks'],
            'warning_checks' => $report['warning_checks'],
            'failed_checks' => $report['failed_checks'],
            'status_label' => $report['status_label'],
            'save_to_history' => true,
            'audit_payload' => $report,
            'scanned_at' => now(),
        ]);

        return redirect()->route('launch-readiness.results.show', [
            'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
            'toolScan' => $newScan,
        ])->with('status', 'A fresh launch-readiness test has been created.');
    }

    public function settings(Request $request): View
    {
        $toolUser = $this->toolUser();
        $activeTab = $request->query('tab', 'profile');
        $allowedTabs = ['profile', 'password', 'two-factor'];

        if ($toolUser->isAdmin()) {
            $allowedTabs[] = 'branding';
        }

        if (! in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'profile';
        }

        return view('tools.launch-readiness.workspace.settings', $this->workspaceData($toolUser, [
            'activeTab' => $activeTab,
        ]));
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $toolUser = $this->toolUser();
        $this->authorizeAdmin($toolUser);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'tool_slug' => [
                'required',
                'string',
                'max:120',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $this->toolSettings->candidateSlug($value)) {
                        $fail('The slug must contain usable letters or numbers.');
                        return;
                    }

                    if (! $this->toolSettings->slugAvailable(ToolSettings::LAUNCH_READINESS_KEY, $value)
                        && $this->toolSettings->candidateSlug($value) !== $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY)) {
                        $fail('That slug is already used by another tool.');
                    }
                },
            ],
            'homepage_h1' => ['required', 'string', 'max:160'],
            'homepage_title_tag' => ['required', 'string', 'max:255'],
            'homepage_meta_description' => ['required', 'string', 'max:255'],
            'font_url' => [
                'nullable',
                'url',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (blank($value)) {
                        return;
                    }

                    if (! str_starts_with((string) $value, 'https://fonts.googleapis.com/css')) {
                        $fail('The font link must be a valid Google Fonts CSS URL.');
                    }
                },
            ],
            'font_size' => ['required', 'integer', 'min:14', 'max:20'],
            'font_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'background_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'logo' => ['nullable', 'file', 'mimes:svg,png,jpg,jpeg,webp,gif', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:512'],
            'og_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
            'remove_og_image' => ['nullable', 'boolean'],
        ]);

        $current = $this->branding->get();
        $next = [
            'site_name' => trim((string) $validated['site_name']),
            'homepage_h1' => trim((string) $validated['homepage_h1']),
            'homepage_title_tag' => trim((string) $validated['homepage_title_tag']),
            'homepage_meta_description' => trim((string) $validated['homepage_meta_description']),
            'font_url' => blank($validated['font_url'] ?? null) ? null : trim((string) $validated['font_url']),
            'font_family' => 'Inter',
            'font_size' => (int) $validated['font_size'],
            'font_color' => $validated['font_color'],
            'background_color' => $validated['background_color'],
            'logo_path' => $current['logo_path'] ?? null,
            'favicon_path' => $current['favicon_path'] ?? null,
            'og_image_path' => $current['og_image_path'] ?? null,
        ];

        $fontFamilies = $this->branding->extractFontFamiliesFromUrl($next['font_url']);

        if ($next['font_url'] && $fontFamilies === []) {
            return back()
                ->withErrors(['font_url' => 'Could not extract a usable font family from that Google Fonts link.'])
                ->withInput();
        }

        if ($fontFamilies !== []) {
            $next['font_family'] = $fontFamilies[0];
        }

        if ($request->boolean('remove_logo')) {
            $this->branding->deleteAsset($current['logo_path'] ?? null);
            $next['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            $this->branding->deleteAsset($current['logo_path'] ?? null);
            $next['logo_path'] = $this->branding->storeUploadedAsset($request->file('logo'), 'logo');
        }

        if ($request->boolean('remove_favicon')) {
            $this->branding->deleteAsset($current['favicon_path'] ?? null);
            $next['favicon_path'] = null;
        } elseif ($request->hasFile('favicon')) {
            $this->branding->deleteAsset($current['favicon_path'] ?? null);
            $next['favicon_path'] = $this->branding->storeUploadedAsset($request->file('favicon'), 'favicon');
        }

        if ($request->boolean('remove_og_image')) {
            $this->branding->deleteAsset($current['og_image_path'] ?? null);
            $next['og_image_path'] = null;
        } elseif ($request->hasFile('og_image')) {
            $this->branding->deleteAsset($current['og_image_path'] ?? null);
            $next['og_image_path'] = $this->branding->storeUploadedAsset($request->file('og_image'), 'og-image');
        }

        $this->branding->save($next);
        $this->branding->get();
        $newSlug = $this->toolSettings->updateSlug(ToolSettings::LAUNCH_READINESS_KEY, $validated['tool_slug']);

        return redirect()->route('launch-readiness.settings', [
            'toolSlug' => $newSlug,
            'tab' => 'branding',
        ])->with('status', 'Project branding updated.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $toolUser = $this->toolUser();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('tool_users', 'email')->ignore($toolUser->id)],
        ]);

        $toolUser->forceFill([
            'name' => $validated['name'] ?: null,
            'email' => strtolower($validated['email']),
        ])->save();

        return redirect()->route('launch-readiness.settings', [
            'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
            'tab' => 'profile',
        ])->with('status', 'Profile updated.');
    }

    public function destroyAccount(Request $request): RedirectResponse
    {
        $toolUser = $this->toolUser();

        ToolScan::query()->where('tool_user_id', $toolUser->id)->delete();
        ToolAuthMagicLink::query()->where('tool_user_id', $toolUser->id)->delete();
        ToolAuthMagicLink::query()->where('email', $toolUser->email)->delete();

        Auth::guard('tool_user')->logout();
        $toolUser->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($this->toolSettings->path(ToolSettings::LAUNCH_READINESS_KEY))
            ->with('status', 'Your tool account was deleted.')
            ->with('auth_sync_event', 'signed-out');
    }

    private function workspaceData(ToolUser $toolUser, array $data = []): array
    {
        return $this->pageData->merge($data, $toolUser, request()->getHost());
    }

    private function toolUser(): ToolUser
    {
        /** @var ToolUser $toolUser */
        $toolUser = Auth::guard('tool_user')->user();

        return $toolUser;
    }

    private function usedToday(ToolUser $toolUser): int
    {
        return ToolScan::query()
            ->where('tool_user_id', $toolUser->id)
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function dailyLimit(): int
    {
        return config('launch_readiness.daily_limit', 20);
    }

    private function authorizeScanOwner(ToolScan $toolScan, ToolUser $toolUser): void
    {
        abort_unless(
            $toolScan->tool_user_id === $toolUser->id && $toolScan->tool_key === ToolSettings::LAUNCH_READINESS_KEY,
            403
        );
    }

    private function authorizeAdmin(ToolUser $toolUser): void
    {
        abort_unless($toolUser->isAdmin(), 403);
    }
}
