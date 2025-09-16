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
}
