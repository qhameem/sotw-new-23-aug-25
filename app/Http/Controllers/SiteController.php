<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\PremiumProduct;
use Illuminate\Support\Facades\Storage;

class SiteController extends Controller
{
    public function promote()
    {
        $products = Auth::check() ? Auth::user()->products()->orderBy('created_at', 'desc')->get() : collect();
        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        $premiumProductSpots = $settings['premium_product_spots'] ?? 6;
        $spotsAvailable = $premiumProductSpots - PremiumProduct::where('expires_at', '>', now())->count();

        return view('site.promote', compact('products', 'spotsAvailable'))->with('hideSidebar', true);
    }
}