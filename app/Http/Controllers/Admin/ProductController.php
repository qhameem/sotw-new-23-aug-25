<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Added Storage facade
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Support\ProductMediaSeo;

class ProductController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['user', 'categories']);

        $searchTerm = trim((string) $request->input('q'));
        $this->applySearch($query, $searchTerm);
        $selectedProductId = $request->integer('selected_product_id');

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

        $products = $query->paginate(15)->withQueryString();
        $selectedProduct = null;

        if ($selectedProductId) {
            $selectedProduct = Product::with(['user', 'categories'])->find($selectedProductId);
        }

        return view('admin.products.index', compact('products', 'searchTerm', 'sortBy', 'sortDir', 'selectedProduct'));
    }

    public function autocomplete(Request $request)
    {
        $searchTerm = trim((string) $request->input('q'));
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        if (!in_array($sortBy, ['name', 'id', 'created_at', 'is_promoted'], true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (mb_strlen($searchTerm) < 2) {
            return response()->json([]);
        }

        $products = Product::with('user')
            ->select(['id', 'user_id', 'name', 'slug', 'tagline', 'link', 'logo'])
            ->tap(fn ($query) => $this->applySearch($query, $searchTerm))
            ->orderBy('is_promoted', 'desc')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return response()->json($products->map(function (Product $product) use ($searchTerm, $sortBy, $sortDir) {
            $domain = parse_url($product->link, PHP_URL_HOST);
            $domain = is_string($domain) ? preg_replace('/^www\./i', '', $domain) : null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'domain' => $domain,
                'logo_url' => $product->logo_url,
                'owner_name' => $product->user?->name,
                'owner_email' => $product->user?->email,
                'admin_url' => route('admin.products.show', $product),
                'select_url' => route('admin.products.index', [
                    'q' => $searchTerm,
                    'sort_by' => $sortBy,
                    'sort_dir' => $sortDir,
                    'selected_product_id' => $product->id,
                ]) . '#selected-product-card',
            ];
        })->values());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categoryTypes = json_decode(Storage::get('category_types.json'), true);
        $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
        $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
        $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

        $regularCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
        $pricingCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
        $bestForCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

        $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get();
        $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get();
        $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get();

        $allTechStacks = \App\Models\TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);

        $displayData = [
            'name' => old('name'),
            'slug' => old('slug'),
            'link' => old('link'),
            'logo' => null,
            'tagline' => old('tagline'),
            'product_page_tagline' => old('product_page_tagline'),
            'description' => old('description'),
            'maker_links' => old('maker_links', []),
            'sell_product' => old('sell_product', false),
            'asking_price' => old('asking_price'),
            'pricing_page_url' => old('pricing_page_url'),
            'x_account' => old('x_account'),
            'current_categories' => old('categories', []),
            'current_tech_stacks' => old('tech_stacks', []),
            'video_url' => old('video_url'),
        ];

        return view('admin.products.create', compact('displayData', 'regularCategories', 'bestForCategories', 'pricingCategories', 'allTechStacksData'));
    }

    protected function applySearch($query, string $searchTerm): void
    {
        if ($searchTerm === '') {
            return;
        }

        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('slug', 'LIKE', "%{$searchTerm}%")
                ->orWhere('tagline', 'LIKE', "%{$searchTerm}%")
                ->orWhere('product_page_tagline', 'LIKE', "%{$searchTerm}%")
                ->orWhere('link', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('user', function ($uq) use ($searchTerm) {
                    $uq->where('name', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                })
                ->orWhereHas('categories', function ($cq) use ($searchTerm) {
                    $cq->where('name', 'LIKE', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug',
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'video_url' => 'nullable|string',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $validated['user_id'] = Auth::id();

        $product = Product::create($validated);

        if ($request->has('categories')) {
            $product->categories()->sync($validated['categories']);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $productController = new \App\Http\Controllers\ProductController(
            app(\App\Services\FaviconExtractorService::class),
            app(\App\Services\SlugService::class),
            app(\App\Services\TechStackDetectorService::class),
            app(\App\Services\NameExtractorService::class),
            app(\App\Services\LogoExtractorService::class),
            app(\App\Services\CategoryClassifier::class),
            app(\App\Services\ScreenshotService::class),
            app(\App\Services\BadgeService::class),
            app(\App\Services\RelatedProductService::class)
        );

        $view = $productController->showProductPage($product);
        return $view->with('isAdminView', true);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['categories', 'proposedCategories', 'techStacks', 'media']);

        $categoryTypes = json_decode(Storage::get('category_types.json'), true);
        $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
        $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
        $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

        $regularCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
        $pricingCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
        $bestForCategoryIds = \Illuminate\Support\Facades\DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

        $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get();
        $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get();
        $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get();

        $allTechStacks = \App\Models\TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);

        // For admin editing, always show the original product data, not proposed changes
        $product->load('media');
        $displayData = [
            'name' => old('name', $product->name),
            'slug' => old('slug', $product->slug),
            'link' => old('link', $product->link),
            'logo' => $product->logo, // Use original logo
            'logo_url' => $product->logo_url, // Full URL for preview
            'tagline' => old('tagline', $product->tagline), // Use original tagline
            'product_page_tagline' => old('product_page_tagline', $product->product_page_tagline),
            'description' => old('description', $product->description), // Use original description
            'current_categories' => old('categories', $product->categories->pluck('id')->toArray()), // Use original categories
            'current_tech_stacks' => old('tech_stacks', $product->techStacks->pluck('id')->toArray()),
            'video_url' => old('video_url', $product->video_url),
            'maker_links' => old('maker_links', is_array($product->maker_links) ? $product->maker_links : []),
            'sell_product' => old('sell_product', $product->sell_product),
            'asking_price' => old('asking_price', $product->asking_price),
            'pricing_page_url' => old('pricing_page_url', $product->pricing_page_url),
            'x_account' => old('x_account', $product->x_account),
            'comparison_overrides_input' => old('comparison_overrides_input', implode(', ', $product->comparison_product_ids ?? [])),
            'alternative_overrides_input' => old('alternative_overrides_input', implode(', ', $product->alternative_product_ids ?? [])),
            'id' => $product->id,
            'logos' => $product->media->whereIn('type', ['image', 'screenshot'])->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
            'gallery' => $product->media->whereIn('type', ['image', 'screenshot'])->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
        ];

        $allCategories = Category::with('types')->orderBy('name')->get();
        $types = \App\Models\Type::with('categories')->get();
        $selectedBestForCategories = $product->categories()
            ->whereHas('types', function ($query) {
                $query->where('types.id', 3);
            })
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        return view('admin.products.edit', compact('product', 'displayData', 'regularCategories', 'bestForCategories', 'pricingCategories', 'allTechStacksData', 'allCategories', 'types', 'selectedBestForCategories'));
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
            'categories' => 'sometimes|array',
            'categories.*' => 'exists:categories,id',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'video_url' => 'nullable|string',
            'maker_links' => 'nullable|array',
            'maker_links.*' => 'url|max:2048',
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'comparison_overrides_input' => 'nullable|string|max:5000',
            'alternative_overrides_input' => 'nullable|string|max:5000',
        ]);

        // Handle logo removal
        if (($request->has('remove_logo') || $request->input('logo') === 'null') && $product->logo) {
            if (!Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $product->logo = null;
        }

        // Handle new logo upload
        if ($request->hasFile('logo')) {
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // Process description to ensure proper paragraph structure
        if (isset($validated['description'])) {
            $productController = new \App\Http\Controllers\ProductController(
                app(\App\Services\FaviconExtractorService::class),
                app(\App\Services\SlugService::class),
                app(\App\Services\TechStackDetectorService::class),
                app(\App\Services\NameExtractorService::class),
                app(\App\Services\LogoExtractorService::class),
                app(\App\Services\CategoryClassifier::class),
                app(\App\Services\ScreenshotService::class),
                app(\App\Services\BadgeService::class),
                app(\App\Services\RelatedProductService::class)
            );

            $validated['description'] = $productController->ensureProperParagraphStructure(
                $productController->addNofollowToLinks($validated['description'])
            );
        }

        if (array_key_exists('x_account', $validated)) {
            $validated['x_account'] = Product::normalizeXAccount($validated['x_account']);
        }

        if (!empty($validated['pricing_page_url'])) {
            $validated['pricing_page_url'] = Product::normalizeLink($validated['pricing_page_url']);
        }

        $validated['comparison_product_ids'] = $this->resolveRelatedProductOverrides(
            $validated['comparison_overrides_input'] ?? null,
            $product->id
        );
        $validated['alternative_product_ids'] = $this->resolveRelatedProductOverrides(
            $validated['alternative_overrides_input'] ?? null,
            $product->id
        );
        unset($validated['comparison_overrides_input'], $validated['alternative_overrides_input']);

        $product->update($validated);

        if ($request->has('categories')) {
            $product->categories()->sync($validated['categories']);
        }

        if ($request->has('tech_stacks')) {
            $product->techStacks()->sync($validated['tech_stacks']);
        }

        // Handle gallery images
        if ($request->hasFile('media')) {
            $manager = new ImageManager(new Driver());
            $mediaPosition = $product->media()->count();

            foreach ($request->file('media') as $file) {
                $mimeType = $file->getMimeType();
                $type = \Illuminate\Support\Str::startsWith($mimeType, 'image') ? 'image' : 'video';
                $mediaPosition++;
                $extension = strtolower($file->getClientOriginalExtension()) ?: ($type === 'image' ? 'png' : 'bin');
                $filename = ProductMediaSeo::productMediaFilename($product, $type, $extension, $mediaPosition);
                $path = $file->storeAs('product_media', $filename, 'public');

                $pathThumb = null;
                $pathMedium = null;

                if ($type === 'image') {
                    try {
                        $filename = basename($path);
                        $directory = dirname($path);

                        // Generate Thumbnail (300px width)
                        $imageThumb = $manager->read($file->getRealPath());
                        $imageThumb->scale(width: 300);
                        $thumbFilename = 'thumb_' . $filename;
                        $pathThumb = $directory . '/' . $thumbFilename;
                        Storage::disk('public')->put($pathThumb, (string) $imageThumb->encode());

                        // Generate Medium (800px width)
                        $imageMedium = $manager->read($file->getRealPath());
                        $imageMedium->scale(width: 800);
                        $mediumFilename = 'medium_' . $filename;
                        $pathMedium = $directory . '/' . $mediumFilename;
                        Storage::disk('public')->put($pathMedium, (string) $imageMedium->encode());
                    } catch (\Throwable $e) {
                        // Fallback: if resizing fails, we just don't set the paths, keeping original behavior
                        \Log::warning('Image resizing skipped: ' . $e->getMessage());
                    }
                }

                $product->media()->create([
                    'path' => $path,
                    'path_thumb' => $pathThumb,
                    'path_medium' => $pathMedium,
                    'alt_text' => ProductMediaSeo::productMediaAltText($product, $type, $mediaPosition),
                    'type' => $type,
                ]);
            }
        }


        // Check if the user came from the product approvals page
        $fromApprovals = $request->input('from') === 'approvals';

        if ($request->wantsJson() || $request->ajax()) {
            $redirectUrl = $fromApprovals ? route('admin.product-approvals.index') : route('admin.products.index');
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'redirect_url' => $redirectUrl
            ]);
        }

        $redirectRoute = $fromApprovals ? 'admin.product-approvals.index' : 'admin.products.index';
        return redirect()->route($redirectRoute)->with('success', 'Product updated successfully.');
    }

    private function resolveRelatedProductOverrides(?string $rawInput, ?int $excludeProductId = null): array
    {
        $rawInput = trim((string) $rawInput);
        if ($rawInput === '') {
            return [];
        }

        $tokens = collect(preg_split('/[\s,;]+/', $rawInput) ?: [])
            ->map(fn($token) => trim((string) $token))
            ->filter()
            ->values();

        if ($tokens->isEmpty()) {
            return [];
        }

        $normalized = $tokens->map(function (string $token): array {
            $token = trim($token);

            if (Str::startsWith($token, ['http://', 'https://'])) {
                $path = trim((string) parse_url($token, PHP_URL_PATH), '/');
                if ($path !== '') {
                    $segments = explode('/', $path);
                    $token = end($segments) ?: $token;
                }
            }

            $token = trim($token, "/ \t\n\r\0\x0B");

            if (is_numeric($token)) {
                return ['kind' => 'id', 'value' => (int) $token];
            }

            $slug = Str::slug($token);
            if ($slug === '') {
                $slug = Str::of($token)->lower()->trim('-')->value();
            }

            return ['kind' => 'slug', 'value' => $slug];
        })->filter(fn($entry) => !empty($entry['value']))->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        $numericIds = $normalized
            ->where('kind', 'id')
            ->pluck('value')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $slugs = $normalized
            ->where('kind', 'slug')
            ->pluck('value')
            ->map(fn($slug) => (string) $slug)
            ->unique()
            ->values();

        $products = Product::query()
            ->select(['id', 'slug'])
            ->where(function ($query) use ($numericIds, $slugs) {
                if ($numericIds->isNotEmpty()) {
                    $query->whereIn('id', $numericIds);
                }
                if ($slugs->isNotEmpty()) {
                    if ($numericIds->isNotEmpty()) {
                        $query->orWhereIn('slug', $slugs);
                    } else {
                        $query->whereIn('slug', $slugs);
                    }
                }
            })
            ->get();

        $idMap = $products->pluck('id', 'id');
        $slugMap = $products->pluck('id', 'slug');

        $resolved = [];
        foreach ($normalized as $entry) {
            $resolvedId = null;
            if ($entry['kind'] === 'id') {
                $resolvedId = $idMap->get((int) $entry['value']);
            } else {
                $resolvedId = $slugMap->get((string) $entry['value']);
            }

            if (!$resolvedId) {
                continue;
            }
            if ($excludeProductId && (int) $resolvedId === (int) $excludeProductId) {
                continue;
            }
            if (in_array((int) $resolvedId, $resolved, true)) {
                continue;
            }

            $resolved[] = (int) $resolvedId;
            if (count($resolved) >= 30) {
                break;
            }
        }

        return $resolved;
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
