<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Support\Str;

class FastTrackController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $products = $user->products()
            ->where('approved', false)
            ->whereNull('published_at')
            ->with('categories.types')
            ->get();

        $alpineProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $title = 'Fast-Track The Launch of Your Products';

        return view('fast-track.index', compact('products', 'title', 'alpineProducts'));
    }
}
