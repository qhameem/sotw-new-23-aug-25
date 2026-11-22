<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductFilterNavigationService;
use App\Support\CategoryTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCatalogController extends Controller
{
    public function __invoke(Request $request, ProductFilterNavigationService $navigation): View
    {
        $sort = $request->query('sort') === 'oldest' ? 'oldest' : 'newest';
        $direction = $sort === 'oldest' ? 'asc' : 'desc';

        $catalogCategories = Category::submittable()
            ->whereHas('products', fn ($query) => $query->approvedAndPublished())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $types = $navigation->getTypes();
        $taxonomies = [
            'platform' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM),
            'pricing' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING),
            'use_case' => CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE),
        ];
        $filterOptions = ['category' => $catalogCategories];
        foreach ($taxonomies as $key => $typeNames) {
            $filterOptions[$key] = $types->whereIn('name', $typeNames)
                ->flatMap(fn ($type) => $type->categories)
                ->where('products_count', '>', 0)
                ->unique('id')
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
        }

        $query = Product::approvedAndPublished();
        $filters = [];
        foreach ($filterOptions as $key => $options) {
            $slug = $request->query($key);
            $selected = is_string($slug) ? $options->firstWhere('slug', $slug) : null;
            if ($selected) {
                $filters[$key] = $selected->slug;
                $query->whereHas('categories', fn ($categories) => $categories->whereKey($selected->id));
            }
        }

        $catalogParameters = $filters + ($sort === 'oldest' ? ['sort' => $sort] : []);
        $products = $query
            ->with('categories.types')
            ->orderByRaw("COALESCE(published_at, created_at) {$direction}")
            ->orderBy('id', $direction)
            ->paginate(24)
            ->onEachSide(1)
            ->appends($catalogParameters);

        return view('products.catalog', [
            'products' => $products,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'catalogParameters' => $catalogParameters,
            'taxonomies' => $taxonomies,
            'sort' => $sort,
            'types' => $types,
        ]);
    }
}
