<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        $products = Product::with('categories')->where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orWhere('tagline', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'slug', 'logo', 'link', 'tagline', 'description']);

        $categories = Category::where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
    }
    public function sidebarSearch(Request $request)
    {
        $query = $request->input('query');

        $products = \App\Models\Product::where('name', 'like', '%' . $query . '%')
            ->orWhere('tagline', 'like', '%' . $query . '%')
            ->take(5)
            ->get();

        $categories = \App\Models\Category::where('name', 'like', '%' . $query . '%')
            ->take(3)
            ->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}