<?php

namespace App\Http\Controllers;

use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $collections = $user->productCollections()
            ->withCount('items')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('collections.index', [
            'collections' => $collections,
            'defaultProductCollectionNames' => ProductCollection::defaultNames(),
            'collectionsCount' => $collections->count(),
            'publicCollectionsCount' => $collections->where('visibility', ProductCollection::VISIBILITY_PUBLIC)->count(),
            'privateCollectionsCount' => $collections->where('visibility', ProductCollection::VISIBILITY_PRIVATE)->count(),
            'savedProductsCount' => (int) $collections->sum('items_count'),
        ]);
    }

    public function show(Request $request, User $owner, string $collectionSlug): View
    {
        $collection = $owner->productCollections()
            ->where('slug', $collectionSlug)
            ->firstOrFail();

        $viewer = $request->user();
        $isOwner = $viewer?->id === $collection->user_id;

        abort_unless($collection->isPublic() || $isOwner, 404);

        $collection->load([
            'user',
            'items.product.user',
            'items.product.categories.types',
        ]);

        return view('collections.show', [
            'collection' => $collection,
            'isOwner' => $isOwner,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('product_collections', 'name')->where(
                    fn ($query) => $query->where('user_id', $request->user()->id)
                ),
            ],
            'visibility' => ['required', Rule::in([
                ProductCollection::VISIBILITY_PUBLIC,
                ProductCollection::VISIBILITY_PRIVATE,
            ])],
        ]);

        $validated['name'] = trim($validated['name']);
        $request->user()->productCollections()->create($validated);

        return redirect()
            ->route('collections.index')
            ->with('status', 'collection-created');
    }

    public function update(Request $request, ProductCollection $collection): RedirectResponse
    {
        $this->ensureOwner($request, $collection);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('product_collections', 'name')
                    ->ignore($collection->id)
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
            'visibility' => ['required', Rule::in([
                ProductCollection::VISIBILITY_PUBLIC,
                ProductCollection::VISIBILITY_PRIVATE,
            ])],
        ]);

        $validated['name'] = trim($validated['name']);
        $collection->update($validated);

        return back()->with('status', 'collection-updated');
    }

    public function destroy(Request $request, ProductCollection $collection): RedirectResponse
    {
        $this->ensureOwner($request, $collection);

        $collection->delete();

        return redirect()
            ->route('collections.index')
            ->with('status', 'collection-deleted');
    }

    protected function ensureOwner(Request $request, ProductCollection $collection): void
    {
        abort_unless($request->user()?->id === $collection->user_id, 403);
    }
}
