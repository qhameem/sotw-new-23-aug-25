<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CategoryNavigationService;
use App\Services\ProductFilterNavigationService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class BroadCategoryController extends Controller
{
    private const PER_PAGE = 50;

    public function show(
        Request $request,
        CategoryNavigationService $navigation,
        ProductFilterNavigationService $filterNavigation,
        string $group,
        ?int $page = null
    )
    {
        $broadCategory = $navigation->getBroadGroup($group);

        abort_unless($broadCategory, 404);

        $currentPage = max(1, $page ?? 1);
        $categorySlugs = collect($broadCategory['items'])->pluck('slug')->all();

        $query = Product::query()
            ->approvedAndPublished()
            ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereIn('slug', $categorySlugs))
            ->with([
                'categories.types',
                'user',
                'userUpvotes' => function ($query) {
                    if (Auth::check()) {
                        $query->where('user_id', Auth::id());
                    }
                },
            ])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderByDesc('id');

        $total = (clone $query)->count();
        abort_if($currentPage > 1 && $currentPage > (int) ceil($total / self::PER_PAGE), 404);

        $products = $query
            ->forPage($currentPage, self::PER_PAGE)
            ->get();

        $regularProducts = new LengthAwarePaginator(
            $products,
            $total,
            self::PER_PAGE,
            $currentPage,
            ['path' => route('software-groups.show', ['group' => $group])]
        );

        $canonicalUrl = $currentPage === 1
            ? route('software-groups.show', ['group' => $group])
            : route('software-groups.page', ['group' => $group, 'page' => $currentPage]);

        return view('site.software-groups.show', [
            'broadCategory' => $broadCategory,
            'regularProducts' => $regularProducts,
            'promotedProducts' => collect(),
            'canonicalUrl' => $canonicalUrl,
            'currentPage' => $currentPage,
            'lastPage' => $regularProducts->lastPage(),
            'types' => $filterNavigation->getTypes(),
        ]);
    }
}
