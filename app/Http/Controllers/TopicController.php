<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Product;
use App\Models\Type;
use App\Models\AdZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TopicController extends Controller
{
    /**
     * Display a listing of all product categories.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        // Eager load categories with their types and count the number of approved products
        $categories = Category::with(['types'])
            ->withCount(['products' => function ($query) {
                $query->where('approved', true)
                      ->where(function ($subQuery) {
                          $subQuery->whereNull('published_at')
                                   ->orWhereDate('published_at', '<=', Carbon::today());
                      });
            }])
            ->get();

        // Sort the categories by the number of products in descending order
        $sortedCategories = $categories->sortByDesc('products_count');

        // Group categories by their type name
        $groupedCategories = $sortedCategories->groupBy(function ($category) {
            // A category might belong to multiple types, we'll group by the first one for simplicity
            // or handle it based on specific business logic. Here, we check for 'Software' or 'Pricing'.
            $typeName = $category->types->first()->name ?? 'Other';
            if (str_contains($typeName, 'Software')) {
                return 'Software';
            }
            if (str_contains($typeName, 'Pricing')) {
                return 'Pricing';
            }
            return 'Other';
        });

        // Ensure 'Software' and 'Pricing' groups exist even if empty
        $finalGroupedCategories = collect([
            'Software' => $groupedCategories->get('Software', collect()),
            'Pricing' => $groupedCategories->get('Pricing', collect()),
            'Other' => $groupedCategories->get('Other', collect()),
        ]);

        return view('site.topics.index', ['groupedCategories' => $finalGroupedCategories]);
    }

    /**
     * Display the specified category and its products.
     *
     * @param \App\Models\Category $category
     * @return \Illuminate\View\View
     */
    public function show(Category $category): View
    {
        // 1. Fetch Promoted Products for this category
        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category->id);
            })
            ->orderBy('promoted_position', 'asc')
            ->get();

        // 2. Fetch Regular (non-promoted) Products for this category
        $regularProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }])
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
        $allTypes = Type::with(['categories' => function ($query) {
            $query->withCount(['products' => function ($subQuery) {
                $subQuery->where('approved', true)
                         ->where(function ($subSubQuery) {
                             $subSubQuery->whereNull('published_at')
                                        ->orWhereDate('published_at', '<=', Carbon::today());
                         });
            }])->orderByDesc('products_count')->orderBy('name');
        }])->orderBy('name')->get();

        $softwareTypes = $allTypes->filter(fn ($type) => $type->name === 'Software Categories');
        $pricingTypes = $allTypes->filter(fn ($type) => $type->name === 'Pricing');
        $otherTypes = $allTypes->filter(fn ($type) => $type->name !== 'Software Categories' && $type->name !== 'Pricing');
        $types = $softwareTypes->concat($otherTypes)->concat($pricingTypes);

        // 4. Fetch all categories for the "Other" categories list in sidebar
        $allCategories = Category::withCount(['products' => function ($query) {
            $query->where('approved', true)
                  ->where(function ($subQuery) {
                      $subQuery->whereNull('published_at')
                               ->orWhereDate('published_at', '<=', Carbon::today());
                  });
        }])->orderBy('name')->get();


        // 5. Fetch an active ad for the 'below-product-listing' zone and its position
        $belowProductListingAd = null;
        $belowProductListingAdPosition = null;
        $belowProductListingAdZone = AdZone::where('slug', 'below-product-listing')->first();
        if ($belowProductListingAdZone) {
            $belowProductListingAd = $belowProductListingAdZone->ads()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('start_date')
                          ->orWhere('start_date', '<=', Carbon::now());
                })
                ->where(function ($query) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', Carbon::now());
                })
                ->inRandomOrder()
                ->first();
            if ($belowProductListingAd) {
                $belowProductListingAdPosition = $belowProductListingAdZone->display_after_nth_product;
            }
        }

        return view('site.topics.category', compact(
            'category', // The current category being viewed
            'promotedProducts',
            'regularProducts',
            'types',          // For the sidebar category listing
            'allCategories',  // For the "Other" categories in sidebar, renamed from 'categories' to avoid conflict
            'belowProductListingAd',
            'belowProductListingAdPosition'
        ));
    }
}