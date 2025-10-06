<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ad;
use App\Models\AdZone;
use App\Models\CodeSnippet;

class AdvertisingController extends Controller
{
    public function index()
    {
        $ads = Ad::with('adZones')->latest()->paginate(10);
        $adZones = AdZone::latest()->paginate(10);
        $snippets = CodeSnippet::all();

        return view('admin.advertising.index', compact('ads', 'adZones', 'snippets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'internal_name' => 'required_without:product_id|string|max:255',
            'tagline' => 'required_without:product_id|string|max:255',
            'target_url' => 'required_without:product_id|url',
            'logo' => 'required_without:product_id|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_id' => 'nullable|exists:products,id',
            'ad_zone_id' => 'required|exists:ad_zones,id',
        ]);

        if ($request->product_id) {
            $product = \App\Models\Product::find($request->product_id);
            $logoPath = $product->logo;
            $internalName = $product->name;
            $tagline = $product->tagline;
            $targetUrl = $product->url;
        } else {
            $logoPath = $request->file('logo')->store('logos', 'public');
            $internalName = $request->internal_name;
            $tagline = $request->tagline;
            $targetUrl = $request->target_url;
        }

        $ad = new \App\Models\Ad([
            'internal_name' => $internalName,
            'type' => 'image_banner',
            'content' => \Illuminate\Support\Facades\Storage::url($logoPath),
            'target_url' => $targetUrl,
            'is_active' => true,
        ]);
        $ad->save();

        $ad->adZones()->attach($request->ad_zone_id);

        return redirect()->route('admin.advertising.index')->with('success', 'Sponsor created successfully.');
    }

    public function create()
    {
        return view('admin.advertising.create');
    }
}
