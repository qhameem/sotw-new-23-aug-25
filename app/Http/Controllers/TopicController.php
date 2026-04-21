<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Services\AdDeliveryService;
use App\Services\CategoryNavigationService;

class TopicController extends Controller
{
    /**
     * Display a listing of all product categories.
     *
     * @return \Illuminate\View\View
     */
    public function index(CategoryNavigationService $categoryNavigation): View
    {
        return view('site.topics.index', [
            'categoryNavigationGroups' => $categoryNavigation->getMenuGroups(),
        ]);
    }

    /**
     * Display the specified category and its products.
     *
     * @param \App\Models\Category $category
     * @return \Illuminate\View\View
     */
    public function show(Request $request, Category $category, AdDeliveryService $adDeliveryService): View
    {
        // 1. Fetch Promoted Products for this category
        $promotedProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->orderBy('promoted_position', 'asc')
            ->get();

        // 2. Fetch Regular (non-promoted) Products for this category
        $regularProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhereDate('published_at', '<=', Carbon::today());
            })
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->orderByDesc('votes_count')
            ->orderBy('name', 'asc')
            ->get();

        // 3. Fetch all types with their categories and product counts for the sidebar
        $allTypes = Type::with([
            'categories' => function ($query) {
                $query->withCount([
                    'products' => function ($subQuery) {
                        $subQuery->where('approved', true)
                            ->where(function ($subSubQuery) {
                                $subSubQuery->whereNull('published_at')
                                    ->orWhereDate('published_at', '<=', Carbon::today());
                            });
                    }
                ])->orderByDesc('products_count')->orderBy('name');
            }
        ])->orderBy('name')->get();

        $softwareTypes = $allTypes->filter(fn($type) => $type->name === 'Software Categories');
        $pricingTypes = $allTypes->filter(fn($type) => $type->name === 'Pricing');
        $otherTypes = $allTypes->filter(fn($type) => $type->name !== 'Software Categories' && $type->name !== 'Pricing');
        $types = $softwareTypes->concat($otherTypes)->concat($pricingTypes);

        // 4. Fetch all categories for the "Other" categories list in sidebar
        $allCategories = Category::withCount([
            'products' => function ($query) {
                $query->where('approved', true)
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('published_at')
                            ->orWhereDate('published_at', '<=', Carbon::today());
                    });
            }
        ])->orderBy('name')->get();


        // 5. Fetch an active ad for the 'below-product-listing' zone and its position
        $belowProductListingPlacement = $adDeliveryService->placementForZone('below-product-listing', $adDeliveryService->contextFromRequest($request, [
            'category_id' => $category->id,
            'page_type' => 'topic',
        ]));
        $belowProductListingAd = $belowProductListingPlacement['ads']->first();
        $belowProductListingAdPosition = $belowProductListingPlacement['position'];

        $currentYear = Carbon::now()->year;
        $meta_title = "Discover Top " . strip_tags($category->name) . " (" . $currentYear . ") - Software on the Web";

        return view('site.topics.category', compact(
            'category', // The current category being viewed
            'promotedProducts',
            'regularProducts',
            'types',          // For the sidebar category listing
            'allCategories',  // For the "Other" categories in sidebar, renamed from 'categories' to avoid conflict
            'belowProductListingAd',
            'belowProductListingAdPosition',
            'meta_title'
        ));
    }
}
