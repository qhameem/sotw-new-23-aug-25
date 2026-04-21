<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdZoneController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.advertising.index', ['tab' => 'ad_zones']);
    }

    public function create(): View
    {
        return view('admin.ad_zones.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        AdZone::create($this->validateRequest($request));

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ad_zones'])
            ->with('success', 'Ad zone created successfully.');
    }

    public function show(AdZone $adZone): RedirectResponse
    {
        return redirect()->route('admin.ad-zones.edit', $adZone);
    }

    public function edit(AdZone $adZone): View
    {
        return view('admin.ad_zones.edit', $this->formData($adZone));
    }

    public function update(Request $request, AdZone $adZone): RedirectResponse
    {
        $adZone->update($this->validateRequest($request, $adZone));

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ad_zones'])
            ->with('success', 'Ad zone updated successfully.');
    }

    public function destroy(AdZone $adZone): RedirectResponse
    {
        if ($adZone->ads()->exists()) {
            return redirect()
                ->route('admin.advertising.index', ['tab' => 'ad_zones'])
                ->with('error', 'Cannot delete ad zone while ads are still assigned to it.');
        }

        $adZone->delete();

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ad_zones'])
            ->with('success', 'Ad zone deleted successfully.');
    }

    protected function formData(?AdZone $adZone = null): array
    {
        return [
            'adZone' => $adZone,
            'placementTypes' => AdZone::PLACEMENT_TYPES,
            'rotationModes' => AdZone::ROTATION_MODES,
            'deviceScopes' => AdZone::DEVICE_SCOPES,
            'fallbackModes' => AdZone::FALLBACK_MODES,
            'supportedAdTypes' => AdZone::SUPPORTED_AD_TYPES,
        ];
    }

    protected function validateRequest(Request $request, ?AdZone $adZone = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('ad_zones', 'name')->ignore($adZone?->id)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('ad_zones', 'slug')->ignore($adZone?->id), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string'],
            'render_location' => ['nullable', 'string', 'max:255'],
            'placement_type' => ['required', Rule::in(AdZone::PLACEMENT_TYPES)],
            'supported_ad_types' => ['nullable', 'array'],
            'supported_ad_types.*' => [Rule::in(AdZone::SUPPORTED_AD_TYPES)],
            'max_ads' => ['required', 'integer', 'min:1', 'max:50'],
            'rotation_mode' => ['required', Rule::in(AdZone::ROTATION_MODES)],
            'device_scope' => ['required', Rule::in(AdZone::DEVICE_SCOPES)],
            'fallback_mode' => ['required', Rule::in(AdZone::FALLBACK_MODES)],
            'display_after_nth_product' => ['nullable', 'integer', 'min:1'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->input('placement_type') !== 'in_feed' && $request->filled('display_after_nth_product')) {
                $validator->errors()->add('display_after_nth_product', 'Inline position is only available for in-feed zones.');
            }
        });

        $validated = $validator->validate();
        $validated['supported_ad_types'] = $validated['supported_ad_types'] ?? AdZone::SUPPORTED_AD_TYPES;
        $validated['display_after_nth_product'] = $validated['placement_type'] === 'in_feed'
            ? ($validated['display_after_nth_product'] ?? null)
            : null;

        return $validated;
    }
}
