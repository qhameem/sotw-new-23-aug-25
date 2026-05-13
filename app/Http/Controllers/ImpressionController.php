<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ProductMetricsService;

class ImpressionController extends Controller
{
    public function store(Request $request, ProductMetricsService $metricsService)
    {
        $productIds = $request->input('products', []);
        $surface = (string) $request->input('surface', 'list');
        $productIds = collect($productIds)
            ->filter(fn ($productId) => is_numeric($productId))
            ->map(fn ($productId) => (int) $productId)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return response()->json(['status' => 'success']);
        }

        if ($surface === 'product_detail') {
            Product::query()
                ->whereIn('id', $productIds)
                ->where('approved', true)
                ->where('is_published', true)
                ->get()
                ->each(function (Product $product) use ($metricsService) {
                    $product->recordImpressionAndAutoUpvote();
                    $metricsService->recordDetailView($product);
                });
        } else {
            $metricsService->recordListImpressions($productIds);
        }

        return response()->json(['status' => 'success']);
    }
}
