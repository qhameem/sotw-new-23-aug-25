<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdZone;
use App\Models\CodeSnippet;
use App\Services\CodeSnippetVisibilityService;
use App\Support\CountryOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisingController extends Controller
{
    public function index()
    {
        $ads = Ad::with('adZones')->latest()->paginate(15, ['*'], 'ads_page');
        $adZones = AdZone::with('ads')->orderBy('name')->paginate(15, ['*'], 'zones_page');
        $snippets = CodeSnippet::latest()->get();
        $countries = CountryOptions::all();

        return view('admin.advertising.index', compact('ads', 'adZones', 'snippets', 'countries'));
    }

    public function detectAudience(Request $request, CodeSnippetVisibilityService $visibilityService): JsonResponse
    {
        $ipAddress = $visibilityService->resolveIpAddress($request);
        $countryCode = $visibilityService->resolveCountryCode($request);
        $countries = CountryOptions::all();

        return response()->json([
            'ip' => $ipAddress,
            'country_code' => $countryCode,
            'country_name' => $countryCode !== null ? ($countries[$countryCode] ?? $countryCode) : null,
        ]);
    }
}
