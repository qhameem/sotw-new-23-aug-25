<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdZone;
use Illuminate\Http\Request;

class AdZoneController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $adZones = AdZone::latest()->paginate(15);
        return view('admin.ad_zones.index', compact('adZones'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ad_zones.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ad_zones,name',
            'slug' => 'required|string|max:255|unique:ad_zones,slug|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'description' => 'nullable|string',
            'display_after_nth_product' => 'nullable|integer|min:1',
        ]);

        AdZone::create($validated);

        return redirect()->route('admin.ad-zones.index')->with('success', 'Ad Zone created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AdZone $adZone)
    {
        // Not typically used for admin CRUD, redirect to edit or index.
        return redirect()->route('admin.ad-zones.edit', $adZone);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AdZone $adZone)
    {
        return view('admin.ad_zones.edit', compact('adZone'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AdZone $adZone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ad_zones,name,' . $adZone->id,
            'slug' => 'required|string|max:255|unique:ad_zones,slug,' . $adZone->id . '|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'description' => 'nullable|string',
            'display_after_nth_product' => 'nullable|integer|min:1',
        ]);

        // Ensure null is saved if the field is empty, rather than 0 or an empty string
        $validated['display_after_nth_product'] = $request->filled('display_after_nth_product') ? $validated['display_after_nth_product'] : null;

        $adZone->update($validated);

        return redirect()->route('admin.ad-zones.index')->with('success', 'Ad Zone updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AdZone $adZone)
    {
        // Check if any ads are associated with this zone before deleting
        if ($adZone->ads()->count() > 0) {
            return redirect()->route('admin.ad-zones.index')->with('error', 'Cannot delete Ad Zone: It has ads associated with it.');
        }
        $adZone->delete();
        return redirect()->route('admin.ad-zones.index')->with('success', 'Ad Zone deleted successfully.');
    }
}
