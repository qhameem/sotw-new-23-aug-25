<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;
use App\Models\Product;
use App\Services\StartupDirectoryCatalog;
use Illuminate\Http\Request;

class RootPageController extends Controller
{
    public function __invoke(Request $request, string $product_name, StartupDirectoryCatalog $catalog)
    {
        $product = Product::where('slug', $product_name)->first();

        if ($product) {
            return redirect()->route('products.show', ['product' => $product->slug], 301);
        }

        $page = CustomPage::where('slug', $product_name)->where('is_published', true)->firstOrFail();

        $directoryData = $page->slug === 'directories' ? $catalog->forRequest($request) : [];

        if (isset($directoryData['directories'])) {
            abort_if($directoryData['directories']->currentPage() > $directoryData['directories']->lastPage(), 404);
        }

        return view('site.custom-page', $directoryData + [
            'page' => $page,
            'blankPageTemplate' => $page->slug === 'directories' || $page->template === 'blank',
            'pageSidebarSections' => $page->sidebarSections(),
            'hideSidebar' => ! in_array(true, $page->sidebarSections(), true),
        ]);
    }
}
