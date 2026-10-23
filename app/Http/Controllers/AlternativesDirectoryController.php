<?php

namespace App\Http\Controllers;

use App\Services\ProductResearchService;
use Illuminate\Http\Request;

class AlternativesDirectoryController extends Controller
{
    public function __invoke(Request $request, ProductResearchService $research)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($validated['q'] ?? '');
        $products = $research->directoryQuery()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->paginate(18)->withQueryString();

        abort_if($products->currentPage() > $products->lastPage(), 404);

        $entries = $products->getCollection()->map(function ($product) use ($research) {
            $links = $research->forProducts([$product], 3);

            return $links->isEmpty() ? null : ['product' => $product, 'links' => $links];
        })->filter()->values();

        $canonicalUrl = route('alternatives.index', $products->currentPage() > 1 ? ['page' => $products->currentPage()] : []);

        return view('pseo.index', compact('products', 'entries', 'search', 'canonicalUrl'));
    }
}
