<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PremiumProduct;

class PremiumProductController extends Controller
{
    public function index()
    {
        $premiumProducts = PremiumProduct::with('product')->where('expires_at', '>', now())->get();
        return view('admin.premium-products.index', [
            'premiumProducts' => $premiumProducts,
            'actions' => [],
        ]);
    }

    public function destroy(PremiumProduct $premium_product)
    {
        $premium_product->delete();
        return redirect()->route('admin.premium-products.index')->with('success', 'Product removed from premium spots.');
    }
}
