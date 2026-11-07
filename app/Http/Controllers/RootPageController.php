<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;
use App\Models\Product;

class RootPageController extends Controller
{
    public function __invoke(string $product_name)
    {
        $product = Product::where('slug', $product_name)->first();

        if ($product) {
            return redirect()->route('products.show', ['product' => $product->slug], 301);
        }

        $page = CustomPage::where('slug', $product_name)->where('is_published', true)->firstOrFail();

        return view('site.custom-page', [
            'page' => $page,
            'pageSidebarSections' => $page->sidebarSections(),
            'hideSidebar' => ! in_array(true, $page->sidebarSections(), true),
        ]);
    }
}
