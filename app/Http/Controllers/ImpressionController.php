<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ImpressionController extends Controller
{
    public function store(Request $request)
    {
        $productIds = $request->input('products', []);
        $productIds = collect($productIds)
            ->filter(fn ($productId) => is_numeric($productId))
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $product->recordImpressionAndAutoUpvote();
            }
        }

        return response()->json(['status' => 'success']);
    }
}
