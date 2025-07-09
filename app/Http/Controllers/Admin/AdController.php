<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator; // Added

class AdController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ads = Ad::with('adZones')->latest()->paginate(15);
        return view('admin.ads.index', compact('ads'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $adZones = AdZone::orderBy('name')->get();
        return view('admin.ads.create', compact('adZones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'internal_name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['image_banner', 'text_link', 'html_snippet'])],
            'content_image' => 'nullable', // Base rule, specific rules in sometimes
            'content_text' => 'nullable',  // Base rule, specific rules in sometimes
            'content_html' => 'nullable', // Base rule, specific rules in sometimes
            'target_url' => 'nullable|url|required_if:type,image_banner,text_link|max:2048',
            'open_in_new_tab' => 'nullable|boolean',
            'ad_zones' => 'required|array|min:1',
            'ad_zones.*' => 'exists:ad_zones,id',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->sometimes('content_image', 'required|image|max:2048', function ($input) {
            return $input->type === 'image_banner';
        });

        $validator->sometimes('content_text', 'required|string|max:255', function ($input) {
            return $input->type === 'text_link';
        });

        $validator->sometimes('content_html', 'required|string', function ($input) {
            return $input->type === 'html_snippet';
        });

        $validated = $validator->validate();

        $adData = [
            'internal_name' => $validated['internal_name'],
            'type' => $validated['type'],
            'target_url' => $validated['target_url'] ?? null,
            'open_in_new_tab' => $request->has('open_in_new_tab'),
            'is_active' => $request->has('is_active'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];

        if ($validated['type'] === 'image_banner' && $request->hasFile('content_image')) {
            $path = $request->file('content_image')->store('ads', 'public');
            $adData['content'] = $path;
        } elseif ($validated['type'] === 'text_link') {
            $adData['content'] = $validated['content_text'];
        } elseif ($validated['type'] === 'html_snippet') {
            $adData['content'] = $validated['content_html'];
        }

        $ad = Ad::create($adData);
        $ad->adZones()->sync($validated['ad_zones']);

        return redirect()->route('admin.ads.index')->with('success', 'Ad created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ad $ad)
    {
        return redirect()->route('admin.ads.edit', $ad);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ad $ad)
    {
        $adZones = AdZone::orderBy('name')->get();
        return view('admin.ads.edit', compact('ad', 'adZones'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ad $ad)
    {
        $rules = [
            'internal_name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['image_banner', 'text_link', 'html_snippet'])],
            'content_image' => 'nullable|image|max:2048', // Image rule here, but nullable
            'content_text' => 'nullable|string', // Base for validation, sometimes adds 'required'
            'content_html' => 'nullable|string', // Base for validation, sometimes adds 'required'
            'target_url' => 'nullable|url|required_if:type,image_banner,text_link|max:2048',
            'open_in_new_tab' => 'nullable|boolean',
            'ad_zones' => 'required|array|min:1',
            'ad_zones.*' => 'exists:ad_zones,id',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];

        $validator = Validator::make($request->all(), $rules);

        // content_image rule is already appropriate in base rules for update (nullable|image)

        $validator->sometimes('content_text', 'required|string|max:255', function ($input) {
            return $input->type === 'text_link';
        });

        $validator->sometimes('content_html', 'required|string', function ($input) {
            return $input->type === 'html_snippet';
        });

        $validated = $validator->validate();

        $adData = [
            'internal_name' => $validated['internal_name'],
            'type' => $validated['type'],
            'target_url' => $validated['target_url'] ?? null,
            'open_in_new_tab' => $request->has('open_in_new_tab'),
            'is_active' => $request->has('is_active'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];

        if ($validated['type'] === 'image_banner') {
            if ($request->hasFile('content_image')) {
                // Delete old image if it exists
                if ($ad->content && Storage::disk('public')->exists($ad->content)) {
                    Storage::disk('public')->delete($ad->content);
                }
                $path = $request->file('content_image')->store('ads', 'public');
                $adData['content'] = $path;
            }
            // If no new image, keep existing content (path)
        } elseif ($validated['type'] === 'text_link') {
            $adData['content'] = $validated['content_text'];
        } elseif ($validated['type'] === 'html_snippet') {
            $adData['content'] = $validated['content_html'];
        }


        $ad->update($adData);
        $ad->adZones()->sync($validated['ad_zones']);

        return redirect()->route('admin.ads.index')->with('success', 'Ad updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ad $ad)
    {
        if ($ad->type === 'image_banner' && $ad->content && Storage::disk('public')->exists($ad->content)) {
            Storage::disk('public')->delete($ad->content);
        }
        $ad->adZones()->detach(); // Detach from pivot table
        $ad->delete();
        return redirect()->route('admin.ads.index')->with('success', 'Ad deleted successfully.');
    }
}
