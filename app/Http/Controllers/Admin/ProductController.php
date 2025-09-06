<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Added Storage facade
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProductController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['user', 'categories']); // Eager load categories for search

        // Search functionality
        $searchTerm = $request->input('q');
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  // ->orWhere('sku', 'LIKE', "%{$searchTerm}%") // SKU field does not exist
                  ->orWhereHas('categories', function ($cq) use ($searchTerm) {
                      $cq->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Sorting functionality
        $sortBy = $request->input('sort_by', 'created_at'); // Default sort by creation date
        $sortDir = $request->input('sort_dir', 'desc'); // Default sort direction

        // Always prioritize promoted products
        $query->orderBy('is_promoted', 'desc');

        // Then, sort by the specified column
        if (in_array($sortBy, ['name', 'id', 'created_at', 'is_promoted'])) {
            if ($sortBy === 'is_promoted') {
                // If sorting by promotion, also sort by position
                $query->orderBy('promoted_position', $sortDir);
            } else {
                $query->orderBy($sortBy, $sortDir);
            }
        }
        
        $products = $query->paginate(15)->withQueryString(); // withQueryString appends sort/search params to pagination links

        return view('admin.products.index', compact('products', 'searchTerm', 'sortBy', 'sortDir'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $types = \App\Models\Type::with('categories')->get();
        $product->load('categories');

        $displayData = [
            'name' => $product->name,
            'slug' => $product->slug,
            'link' => $product->link,
            'tagline' => $product->tagline,
            'product_page_tagline' => $product->product_page_tagline,
            'description' => $product->description,
            'current_categories' => $product->categories->pluck('id')->toArray(),
            'logo' => $product->logo,
            'logo_url' => $product->logo_url,
        ];

        return view('admin.products.edit', compact('product', 'categories', 'types', 'displayData'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
        ]);

        // Handle logo removal
        if ($request->has('remove_logo') && $product->logo) {
            if (!Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $validated['logo'] = null;
        }
        // Handle new logo upload
        if ($request->hasFile('logo')) {
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $product->update($validated);
        $product->categories()->sync($validated['categories']);
        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    /**
     * Remove the specified resources from storage.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $productIds = $request->input('product_ids');
        
        // Delete associated logos from storage if they are not external URLs
        $productsToDelete = Product::whereIn('id', $productIds)->get();
        foreach ($productsToDelete as $product) {
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            // Consider deleting other related files if necessary
        }

        Product::whereIn('id', $productIds)->delete();

        return redirect()->route('admin.products.index')->with('success', count($productIds) . ' products deleted successfully.');
    }

    public function updatePromotion(Request $request, Product $product)
    {
        $validated = $request->validate([
            'is_promoted' => 'nullable|string',
            'promoted_position' => [
                'nullable',
                'integer',
                'min:1',
                // Unique only if is_promoted is true and a position is provided.
                // Rule::unique('products', 'promoted_position')->ignore($product->id)->where(function ($query) {
                //     return $query->where('is_promoted', true);
                // }) // This complex conditional unique rule is tricky with where(callback).
                // Simpler approach: validate uniqueness if is_promoted and position is set.
            ],
        ]);

        if ($request->has('is_promoted') && $request->filled('promoted_position')) {
            // Check uniqueness for promoted_position manually if is_promoted is true
            $positionTaken = Product::where('id', '!=', $product->id)
                                     ->where('is_promoted', true)
                                     ->where('promoted_position', $request->input('promoted_position'))
                                     ->exists();
            if ($positionTaken) {
                return back()->withErrors(['promoted_position' => 'This promotion position is already taken.'])->withInput()->with('error_product_id', $product->id);
            }
            $product->promoted_position = $request->input('promoted_position');
        } else {
            $product->promoted_position = null; // Clear position if not promoted or no position given
        }

        $product->is_promoted = $request->has('is_promoted');
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Product promotion status updated successfully.');
    }
}
