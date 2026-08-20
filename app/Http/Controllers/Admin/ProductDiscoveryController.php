<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDiscoverySource;
use App\Models\ProductRecommendation;
use App\Services\ProductDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductDiscoveryController extends Controller
{
    public function inspect(Request $request, ProductDiscoveryService $discovery): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'url:http,https', 'max:2048']]);

        try {
            return response()->json($discovery->inspect($data['url']));
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ProductRecommendation::STATUSES, true)
            ? $request->query('status') : 'new';
        $editSource = $request->filled('edit_source')
            ? ProductDiscoverySource::findOrFail($request->integer('edit_source'))
            : null;

        return view('admin.product-discovery.index', [
            'sources' => ProductDiscoverySource::withCount('recommendations')->orderBy('name')->get(),
            'recommendations' => ProductRecommendation::with('source')->where('status', $status)
                ->orderByDesc('score')->orderByDesc('discovered_at')->paginate(30)->withQueryString(),
            'status' => $status,
            'statusCounts' => ProductRecommendation::selectRaw('status, count(*) as aggregate')
                ->groupBy('status')->pluck('aggregate', 'status'),
            'editSource' => $editSource,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedSource($request);
        ProductDiscoverySource::create($data);

        return back()->with('success', 'Discovery source added.');
    }

    public function update(Request $request, ProductDiscoverySource $source): RedirectResponse
    {
        $source->update($this->validatedSource($request));

        return redirect()->route('admin.product-discovery.index')->with('success', 'Discovery source updated.');
    }

    public function destroy(ProductDiscoverySource $source): RedirectResponse
    {
        $source->delete();

        return back()->with('success', 'Discovery source and its recommendations deleted.');
    }

    public function toggle(ProductDiscoverySource $source): RedirectResponse
    {
        $source->update(['is_active' => ! $source->is_active]);

        return back()->with('success', $source->is_active ? 'Discovery source activated.' : 'Discovery source paused.');
    }

    public function scan(ProductDiscoverySource $source, ProductDiscoveryService $discovery): RedirectResponse
    {
        try {
            $count = $discovery->scan($source);

            return back()->with('success', "{$source->name} scanned; {$count} items processed.");
        } catch (\Throwable $exception) {
            return back()->withErrors(['scan' => "{$source->name}: {$exception->getMessage()}"]);
        }
    }

    public function updateRecommendation(Request $request, ProductRecommendation $recommendation): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', ProductRecommendation::STATUSES)]]);
        $recommendation->update($data);

        return back()->with('success', 'Recommendation updated.');
    }

    private function validatedSource(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'type' => ['required', 'in:auto,html,feed'],
            'item_selector' => ['nullable', 'string', 'max:500'],
            'link_selector' => ['nullable', 'string', 'max:500'],
            'title_selector' => ['nullable', 'string', 'max:500'],
            'description_selector' => ['nullable', 'string', 'max:500'],
            'max_items' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
