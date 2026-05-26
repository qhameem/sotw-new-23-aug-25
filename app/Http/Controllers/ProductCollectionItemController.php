<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductCollectionItemController extends Controller
{
    public function sync(Request $request, Product $product): JsonResponse
    {
        abort_unless($product->approved && $product->is_published, 404);

        $user = $request->user();
        $validated = $request->validate([
            'collections' => ['nullable', 'array'],
            'collections.*.id' => ['nullable', 'integer'],
            'collections.*.default_name' => ['nullable', 'string', Rule::in(ProductCollection::defaultNames())],
            'collections.*.comment' => ['nullable', 'string', 'max:1000'],
            'new_collection.name' => [
                'nullable',
                'string',
                'max:120',
                Rule::unique('product_collections', 'name')->where(
                    fn ($query) => $query->where('user_id', $user->id)
                ),
            ],
            'new_collection.visibility' => ['nullable', Rule::in([
                ProductCollection::VISIBILITY_PUBLIC,
                ProductCollection::VISIBILITY_PRIVATE,
            ])],
            'new_collection.comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedCollections = collect($validated['collections'] ?? []);
        $newCollection = $validated['new_collection'] ?? [];
        $newCollectionName = trim((string) ($newCollection['name'] ?? ''));
        $targetCollectionIds = [];

        if ($newCollectionName !== '') {
            $selectedNames = $selectedCollections
                ->map(function (array $selection) use ($user) {
                    if (!empty($selection['id'])) {
                        return optional($user->productCollections()->find($selection['id']))->name;
                    }

                    return $selection['default_name'] ?? null;
                })
                ->filter()
                ->map(fn ($name) => Str::lower(trim((string) $name)));

            if ($selectedNames->contains(Str::lower($newCollectionName))) {
                return response()->json([
                    'message' => 'Choose a different name for the new collection.',
                    'errors' => [
                        'new_collection.name' => ['Choose a different name for the new collection.'],
                    ],
                ], 422);
            }
        }

        DB::transaction(function () use ($product, $user, $selectedCollections, $newCollection, &$targetCollectionIds) {
            foreach ($selectedCollections as $selection) {
                $collection = null;

                if (!empty($selection['id'])) {
                    $collection = $user->productCollections()->findOrFail($selection['id']);
                } elseif (!empty($selection['default_name'])) {
                    $collection = $user->productCollections()->firstOrCreate(
                        ['name' => $selection['default_name']],
                        ['visibility' => ProductCollection::VISIBILITY_PUBLIC]
                    );
                }

                if (!$collection) {
                    continue;
                }

                $collection->items()->updateOrCreate(
                    ['product_id' => $product->id],
                    ['comment' => $this->normalizeComment($selection['comment'] ?? null)]
                );

                $targetCollectionIds[] = $collection->id;
            }

            if (filled($newCollection['name'] ?? null)) {
                $collection = $user->productCollections()->create([
                    'name' => trim($newCollection['name']),
                    'visibility' => $newCollection['visibility'] ?? ProductCollection::VISIBILITY_PUBLIC,
                ]);

                $collection->items()->create([
                    'product_id' => $product->id,
                    'comment' => $this->normalizeComment($newCollection['comment'] ?? null),
                ]);

                $targetCollectionIds[] = $collection->id;
            }

            $user->productCollections()
                ->whereNotIn('id', array_unique($targetCollectionIds))
                ->each(function (ProductCollection $collection) use ($product) {
                    $collection->items()->where('product_id', $product->id)->delete();
                });
        });

        $collections = $this->buildCollectionOptions($user, $product);
        $savedCount = collect($collections)->filter(fn (array $collection) => $collection['selected'])->count();

        return response()->json([
            'message' => $savedCount > 0 ? 'Saved to your collections.' : 'Removed from your collections.',
            'collections' => $collections,
            'saved_collection_count' => $savedCount,
            'is_saved' => $savedCount > 0,
        ]);
    }

    public function update(Request $request, ProductCollection $collection, Product $product): RedirectResponse
    {
        $this->ensureOwner($request, $collection);

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $collection->items()->where('product_id', $product->id)->firstOrFail()->update([
            'comment' => $this->normalizeComment($validated['comment'] ?? null),
        ]);

        return back()->with('status', 'collection-item-updated');
    }

    public function destroy(Request $request, ProductCollection $collection, Product $product): RedirectResponse
    {
        $this->ensureOwner($request, $collection);

        $collection->items()->where('product_id', $product->id)->delete();

        return back()->with('status', 'collection-item-removed');
    }

    protected function buildCollectionOptions($user, Product $product): array
    {
        $collections = $user->productCollections()
            ->with(['items' => fn ($query) => $query->where('product_id', $product->id)])
            ->orderBy('name')
            ->get();

        if ($collections->isEmpty()) {
            return collect(ProductCollection::defaultNames())
                ->map(fn (string $name) => [
                    'id' => null,
                    'name' => $name,
                    'visibility' => ProductCollection::VISIBILITY_PUBLIC,
                    'selected' => false,
                    'comment' => '',
                    'is_default' => true,
                    'default_name' => $name,
                    'url' => null,
                ])
                ->all();
        }

        return $collections->map(function (ProductCollection $collection) {
            $item = $collection->items->first();

            return [
                'id' => $collection->id,
                'name' => $collection->name,
                'visibility' => $collection->visibility,
                'selected' => $item !== null,
                'comment' => (string) ($item?->comment ?? ''),
                'is_default' => false,
                'default_name' => null,
                'url' => route('collections.show', $collection->publicRouteParameters()),
            ];
        })->all();
    }

    protected function normalizeComment(?string $comment): ?string
    {
        $comment = is_string($comment) ? trim($comment) : null;

        return filled($comment) ? $comment : null;
    }

    protected function ensureOwner(Request $request, ProductCollection $collection): void
    {
        abort_unless($request->user()?->id === $collection->user_id, 403);
    }
}
