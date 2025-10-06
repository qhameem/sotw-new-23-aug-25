<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ImpressionController extends Controller
{
    public function store(Request $request)
    {
        $productIds = $request->input('products', []);

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $product->increment('impressions');
            }
        }

        return response()->json(['status' => 'success']);
    }
}
