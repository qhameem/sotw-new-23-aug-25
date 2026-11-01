<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CategoryNavigationService;
use App\Services\ProductFilterNavigationService;
use App\Support\PathPaginator;
use Illuminate\Http\Request;
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
    ) {
        $broadCategory = $navigation->getBroadGroup($group);

        abort_unless($broadCategory, 404);

        if ($request->query->has('page')) {
            $legacyPage = $request->query('page');
            abort_unless(is_scalar($legacyPage) && preg_match('/^[1-9][0-9]*$/D', (string) $legacyPage), 404);
            $targetPage = (int) $legacyPage;

            return redirect()->route($targetPage === 1 ? 'software-groups.show' : 'software-groups.page', [
                'group' => $group,
                ...($targetPage > 1 ? ['page' => $targetPage] : []),
            ], 301);
        }

        if ($page === 1) {
            return redirect()->route('software-groups.show', ['group' => $group], 301);
        }

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

        $regularProducts = new PathPaginator(
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
