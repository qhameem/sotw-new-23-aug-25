<?php

namespace App\Http\Controllers;

use App\Models\CustomPage;
use App\Models\Product;
use App\Models\StartupDirectory;
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

        if ($page->slug === 'directories' && $request->query('_directory_snapshot') === '1') {
            $rows = StartupDirectory::query()->orderBy('name')->orderBy('id')->limit(1501)->get();
            abort_if($rows->count() > 1500, 413);

            return response()->json($rows->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'domain' => $row->domain(),
                'url' => $row->website_url,
                'dr' => $row->dr,
                'category' => $row->category,
                'pricing' => $row->pricing,
                'link_type' => $row->link_type,
                'html' => view('site.directories.row', ['directory' => $row, 'editable' => false])->render(),
            ]))->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'private, max-age=60');
        }

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
