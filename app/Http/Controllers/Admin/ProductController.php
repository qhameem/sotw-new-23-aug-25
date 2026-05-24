<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage; // Added Storage facade
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Services\ProductLogoStorageService;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Support\CategoryTypeRegistry;
use App\Support\SocialLinkValidator;
use App\Support\ProductMediaSeo;

class ProductController extends Controller
{
    use AuthorizesRequests;

    private function loadProductCategoryGroups(): array
    {
        return [
            'regularCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE));
            })->orderBy('name')->get(),
            'useCaseCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE));
            })->orderBy('name')->get(),
            'pricingCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING));
            })->orderBy('name')->get(),
            'bestForCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR));
            })->orderBy('name')->get(),
            'platformCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM));
            })->orderBy('name')->get(),
        ];
    }

    protected function sortOptions(): array
    {
        return [
            'created_at' => 'Created date',
            'name' => 'Name',
            'id' => 'Product ID',
            'is_promoted' => 'Promotion',
            'votes_count' => 'Total votes',
            'user_upvotes_count' => 'Manual upvotes',
            'view_upvotes' => 'View upvotes',
            'click_upvotes' => 'Link-click upvotes',
            'auto_upvotes_total' => 'Auto upvotes total',
            'impressions' => 'Views',
            'outbound_clicks_count' => 'Link clicks',
        ];
    }

    protected function normalizeSortBy(?string $sortBy): string
    {
        $sortBy = (string) $sortBy;

        return array_key_exists($sortBy, $this->sortOptions()) ? $sortBy : 'created_at';
    }

    protected function normalizeSortDir(?string $sortDir): string
    {
        return in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';
    }

    protected function applySort($query, string $sortBy, string $sortDir): void
    {
        if ($sortBy === 'is_promoted') {
            $query->orderBy('promoted_position', $sortDir);
            return;
        }

        if (in_array($sortBy, ['created_at', 'name', 'id', 'votes_count', 'user_upvotes_count', 'impressions', 'outbound_clicks_count'], true)) {
            $query->orderBy($sortBy, $sortDir);
            return;
        }

        if (in_array($sortBy, ['view_upvotes', 'click_upvotes', 'auto_upvotes_total'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['user', 'categories'])
            ->select('products.*')
            ->selectRaw(
                '((COALESCE(impressions, 0) - (COALESCE(impressions, 0) % ?)) / ?) as view_upvotes',
                [Product::AUTO_UPVOTE_VIEW_THRESHOLD, Product::AUTO_UPVOTE_VIEW_THRESHOLD]
            )
            ->selectRaw(
                '((COALESCE(outbound_clicks_count, 0) - (COALESCE(outbound_clicks_count, 0) % ?)) / ?) as click_upvotes',
                [Product::AUTO_UPVOTE_OUTBOUND_CLICK_THRESHOLD, Product::AUTO_UPVOTE_OUTBOUND_CLICK_THRESHOLD]
            )
            ->selectRaw(
                '(((COALESCE(impressions, 0) - (COALESCE(impressions, 0) % ?)) / ?) + ((COALESCE(outbound_clicks_count, 0) - (COALESCE(outbound_clicks_count, 0) % ?)) / ?)) as auto_upvotes_total',
                [
                    Product::AUTO_UPVOTE_VIEW_THRESHOLD,
                    Product::AUTO_UPVOTE_VIEW_THRESHOLD,
                    Product::AUTO_UPVOTE_OUTBOUND_CLICK_THRESHOLD,
                    Product::AUTO_UPVOTE_OUTBOUND_CLICK_THRESHOLD,
                ]
            )
            ->withCount('userUpvotes');

        $searchTerm = trim((string) $request->input('q'));
        $this->applySearch($query, $searchTerm);
        $selectedProductId = $request->integer('selected_product_id');

        // Sorting functionality
        $sortOptions = $this->sortOptions();
        $sortBy = $this->normalizeSortBy($request->input('sort_by'));
        $sortDir = $this->normalizeSortDir($request->input('sort_dir'));

        // Always prioritize promoted products
        $query->orderBy('is_promoted', 'desc');

        $this->applySort($query, $sortBy, $sortDir);

        $products = $query->paginate(15)->withQueryString();
        $selectedProduct = null;

        if ($selectedProductId) {
            $selectedProduct = Product::with(['user', 'categories'])->withCount('userUpvotes')->find($selectedProductId);
        }

        return view('admin.products.index', compact('products', 'searchTerm', 'sortBy', 'sortDir', 'selectedProduct', 'sortOptions'));
    }

    public function autocomplete(Request $request)
    {
        $searchTerm = trim((string) $request->input('q'));
        $sortBy = $this->normalizeSortBy($request->input('sort_by'));
        $sortDir = $this->normalizeSortDir($request->input('sort_dir'));

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
        [
            'regularCategories' => $regularCategories,
            'useCaseCategories' => $useCaseCategories,
            'pricingCategories' => $pricingCategories,
            'bestForCategories' => $bestForCategories,
            'platformCategories' => $platformCategories,
        ] = $this->loadProductCategoryGroups();

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

        return view('admin.products.create', compact('displayData', 'regularCategories', 'useCaseCategories', 'bestForCategories', 'pricingCategories', 'platformCategories', 'allTechStacksData'));
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

        $useCaseType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->with('categories')->first();
        $selected = collect(is_array($request->input('categories')) ? $request->input('categories') : [])->map(fn($id) => (int) $id);
        $useCaseIds = $useCaseType ? $useCaseType->categories->pluck('id') : collect();

        if ($useCaseIds->count() && $selected->intersect($useCaseIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one use case.'])->withInput();
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = app(ProductLogoStorageService::class)
                ->storeUploadedFile($request->file('logo'));
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
        $productController = app(\App\Http\Controllers\ProductController::class);

        $view = $productController->showProductPage($product);
        return $view->with('isAdminView', true);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load(['categories', 'proposedCategories', 'techStacks', 'media']);

        [
            'regularCategories' => $regularCategories,
            'useCaseCategories' => $useCaseCategories,
            'pricingCategories' => $pricingCategories,
            'bestForCategories' => $bestForCategories,
            'platformCategories' => $platformCategories,
        ] = $this->loadProductCategoryGroups();

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
            'gallery' => $product->media->whereIn('type', ['image', 'screenshot'])->take(1)->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
        ];

        $allCategories = Category::with('types')->orderBy('name')->get();
        $types = \App\Models\Type::with('categories')->get();
        $selectedBestForCategories = $product->categories()
            ->whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR));
            })
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $selectedUseCaseCategories = $product->categories()
            ->whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE));
            })
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        $selectedPlatformCategories = $product->categories()
            ->whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM));
            })
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        return view('admin.products.edit', compact('product', 'displayData', 'regularCategories', 'useCaseCategories', 'bestForCategories', 'pricingCategories', 'platformCategories', 'allTechStacksData', 'allCategories', 'types', 'selectedUseCaseCategories', 'selectedBestForCategories', 'selectedPlatformCategories'));
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
            'custom_categories' => 'nullable|array|max:14',
            'custom_categories.*.name' => 'required|string|max:100',
            'custom_categories.*.type' => 'required|in:category,use_case,best_for,platform',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'logo_url' => 'nullable|string',
            'remove_logo' => 'nullable|boolean',
            'video_url' => 'nullable|string',
            'maker_links' => 'nullable|array',
            'maker_links.*' => [
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!SocialLinkValidator::isAllowedMakerLinkUrl($value)) {
                        $fail('Only social or profile links like GitHub, LinkedIn, and similar social platforms are allowed.');
                    }
                },
            ],
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'media' => 'nullable|array|max:1',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'media_urls' => 'nullable|array|max:1',
            'media_urls.*' => 'nullable|string|max:2048',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
            'comparison_overrides_input' => 'nullable|string|max:5000',
            'alternative_overrides_input' => 'nullable|string|max:5000',
        ]);

        $useCaseType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->with('categories')->first();
        $selected = collect(is_array($request->input('categories')) ? $request->input('categories') : [])->map(fn($id) => (int) $id);
        $useCaseIds = $useCaseType ? $useCaseType->categories->pluck('id') : collect();
        $customCategories = $request->input('custom_categories', []);
        $hasCustomUseCase = collect($customCategories)->contains(function ($category) {
            return ($category['type'] ?? null) === 'use_case' && filled(trim((string) ($category['name'] ?? '')));
        });

        if ($useCaseIds->count() && $selected->intersect($useCaseIds)->isEmpty() && !$hasCustomUseCase) {
            return back()->withErrors(['categories' => 'Please select at least one use case.'])->withInput();
        }

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
            $validated['logo'] = app(ProductLogoStorageService::class)
                ->storeUploadedFile($request->file('logo'));
        } elseif ($request->filled('logo_url')) {
            $resolvedLogoPath = $this->resolveLogoPathFromInput((string) $request->input('logo_url'), (string) $request->input('link', $product->link));
            if ($resolvedLogoPath) {
                $validated['logo'] = $resolvedLogoPath;
            }
        }

        // Process description to ensure proper paragraph structure
        if (isset($validated['description'])) {
            $productController = app(\App\Http\Controllers\ProductController::class);

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

        // Check if the user came from the product approvals page
        $fromApprovals = $request->input('from') === 'approvals';

        if ($product->approved) {
            $newCategories = $validated['categories'] ?? [];
            $newTechStacks = $validated['tech_stacks'] ?? [];

            if ($request->boolean('remove_logo')) {
                if ($product->proposed_logo_path && !Str::startsWith($product->proposed_logo_path, 'http')) {
                    Storage::disk('public')->delete($product->proposed_logo_path);
                }
                $product->proposed_logo_path = null;
            } elseif (isset($validated['logo'])) {
                if ($product->proposed_logo_path && !Str::startsWith($product->proposed_logo_path, 'http')) {
                    Storage::disk('public')->delete($product->proposed_logo_path);
                }
                $product->proposed_logo_path = $validated['logo'];
            }

            $product->proposed_name = $validated['name'];
            $product->proposed_link = Product::normalizeLink($validated['link']);
            $product->proposed_tagline = $validated['tagline'];
            $product->proposed_product_page_tagline = $validated['product_page_tagline'];
            $product->proposed_description = $validated['description'] ?? null;
            $product->proposed_video_url = $validated['video_url'] ?? null;
            $product->proposed_maker_links = $validated['maker_links'] ?? [];
            $product->proposed_sell_product = $validated['sell_product'] ?? false;
            $product->proposed_asking_price = $validated['asking_price'] ?? null;
            $product->proposed_pricing_page_url = $validated['pricing_page_url'] ?? null;
            $product->proposed_x_account = $validated['x_account'] ?? null;
            $product->comparison_product_ids = $validated['comparison_product_ids'] ?? [];
            $product->alternative_product_ids = $validated['alternative_product_ids'] ?? [];
            $product->proposedCategories()->sync($newCategories);
            $product->proposedTechStacks()->sync($newTechStacks);
            $product->has_pending_edits = true;
            $product->last_edited_by_id = Auth::id();

            $mediaUrl = collect((array) $request->input('media_urls', []))
                ->filter(fn ($url) => filled($url))
                ->first();

            if ($request->hasFile('media')) {
                $manager = new ImageManager(new Driver());
                $this->storeProposedScreenshotMedia($product, $request->file('media')[0], $manager);
            } elseif ($mediaUrl) {
                $manager = new ImageManager(new Driver());
                $this->storeProposedScreenshotFromUrl($product, $mediaUrl, $manager);
            }

            $this->syncPendingCustomSubmissions($product, $request);
            $product->save();

            $redirectRoute = 'admin.products.pending-edits.index';
            $message = 'Approved product changes were saved as pending edits for review.';
        } else {
            $product->update($validated);

            if ($request->has('categories')) {
                $product->categories()->sync($validated['categories']);
            }

            if ($request->has('tech_stacks')) {
                $product->techStacks()->sync($validated['tech_stacks']);
            }

            $mediaUrl = collect((array) $request->input('media_urls', []))
                ->filter(fn ($url) => filled($url))
                ->first();

            if ($request->hasFile('media')) {
                $manager = new ImageManager(new Driver());
                $this->replacePrimaryScreenshotMedia($product, $request->file('media')[0], $manager);
            } elseif ($mediaUrl) {
                $manager = new ImageManager(new Driver());
                $this->replacePrimaryScreenshotFromUrl($product, $mediaUrl, $manager);
            }

            $this->syncPendingCustomSubmissions($product, $request);

            $redirectRoute = $fromApprovals ? 'admin.product-approvals.index' : 'admin.products.index';
            $message = 'Product updated successfully.';
        }

        if ($request->wantsJson() || $request->ajax()) {
            $redirectUrl = route($redirectRoute);
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect_url' => $redirectUrl
            ]);
        }

        return redirect()->route($redirectRoute)->with('success', $message);
    }

    private function syncPendingCustomSubmissions(Product $product, Request $request): void
    {
        $product->customCategorySubmissions()
            ->where('status', 'pending')
            ->whereIn('type', ['category', 'use_case', 'best_for', 'platform', 'tech_stack'])
            ->delete();

        foreach ($request->input('custom_categories', []) as $customCategory) {
            \App\Models\CustomCategorySubmission::create([
                'product_id' => $product->id,
                'type' => $customCategory['type'],
                'name' => $customCategory['name'],
                'status' => 'pending',
            ]);
        }

        foreach ($request->input('custom_tech_stacks', []) as $customTechStack) {
            \App\Models\CustomCategorySubmission::create([
                'product_id' => $product->id,
                'type' => 'tech_stack',
                'name' => $customTechStack['name'],
                'status' => 'pending',
            ]);
        }
    }

    private function resolveLogoPathFromInput(string $logoInput, string $productLink): ?string
    {
        $logoInput = trim($logoInput);

        if ($logoInput === '') {
            return null;
        }

        $storageService = app(ProductLogoStorageService::class);

        if (Str::startsWith($logoInput, 'data:image')) {
            return $storageService->storeDataUrl($logoInput);
        }

        if (Str::startsWith($logoInput, '/storage/')) {
            return $storageService->storePublicDiskPath(ltrim(Str::after($logoInput, '/storage/'), '/'));
        }

        if (filter_var($logoInput, FILTER_VALIDATE_URL)) {
            $appUrl = rtrim((string) config('app.url'), '/');

            if ($appUrl !== '' && Str::startsWith($logoInput, $appUrl . '/storage/')) {
                return $storageService->storePublicDiskPath(ltrim(Str::after($logoInput, $appUrl . '/storage/'), '/'));
            }

            try {
                return $storageService->storeRemoteUrl($logoInput) ?? $logoInput;
            } catch (\Throwable $throwable) {
                \Log::warning('Failed to localize remote product logo during admin update; keeping external URL.', [
                    'url' => $logoInput,
                    'error' => $throwable->getMessage(),
                ]);

                return $logoInput;
            }
        }

        return null;
    }

    private function replacePrimaryScreenshotFromUrl(Product $product, string $url, ImageManager $manager): void
    {
        $downloadedPath = $this->downloadMediaUrlToTemporaryPublicPath($url);

        if (!$downloadedPath) {
            return;
        }

        $this->replacePrimaryScreenshotMedia($product, Storage::disk('public')->path($downloadedPath), $manager, true);
        Storage::disk('public')->delete($downloadedPath);
    }

    private function replacePrimaryScreenshotMedia(Product $product, $file, ImageManager $manager, bool $isExternalPath = false): void
    {
        $storedMedia = $this->storeScreenshotAsset($product, $file, $manager, $isExternalPath);

        if (!$storedMedia) {
            return;
        }

        $liveMedia = $product->media()
            ->whereIn('type', ['image', 'screenshot'])
            ->orderBy('id')
            ->first();

        if ($liveMedia) {
            $this->deleteMediaFiles($liveMedia->path, $liveMedia->path_thumb, $liveMedia->path_medium);
            $liveMedia->path = $storedMedia['path'];
            $liveMedia->path_thumb = $storedMedia['path_thumb'];
            $liveMedia->path_medium = $storedMedia['path_medium'];
            $liveMedia->alt_text = ProductMediaSeo::productMediaAltText($product, 'screenshot', 1);
            $liveMedia->type = 'screenshot';
            $liveMedia->save();

            return;
        }

        $product->media()->create([
            'path' => $storedMedia['path'],
            'path_thumb' => $storedMedia['path_thumb'],
            'path_medium' => $storedMedia['path_medium'],
            'alt_text' => ProductMediaSeo::productMediaAltText($product, 'screenshot', 1),
            'type' => 'screenshot',
        ]);
    }

    private function storeProposedScreenshotFromUrl(Product $product, string $url, ImageManager $manager): void
    {
        $downloadedPath = $this->downloadMediaUrlToTemporaryPublicPath($url);

        if (!$downloadedPath) {
            return;
        }

        $this->storeProposedScreenshotMedia($product, Storage::disk('public')->path($downloadedPath), $manager, true);
        Storage::disk('public')->delete($downloadedPath);
    }

    private function storeProposedScreenshotMedia(Product $product, $file, ImageManager $manager, bool $isExternalPath = false): void
    {
        $storedMedia = $this->storeScreenshotAsset($product, $file, $manager, $isExternalPath, 'proposed-');

        if (!$storedMedia) {
            return;
        }

        $this->deleteMediaFiles(
            $product->proposed_screenshot_path,
            $product->proposed_screenshot_thumb_path,
            $product->proposed_screenshot_medium_path
        );

        $product->proposed_screenshot_path = $storedMedia['path'];
        $product->proposed_screenshot_thumb_path = $storedMedia['path_thumb'];
        $product->proposed_screenshot_medium_path = $storedMedia['path_medium'];
    }

    private function storeScreenshotAsset(Product $product, $file, ImageManager $manager, bool $isExternalPath = false, string $filenamePrefix = ''): ?array
    {
        if ($isExternalPath) {
            $absolutePath = $file;
            $mimeType = mime_content_type($file);
        } else {
            $mimeType = $file->getMimeType();
        }

        if (!Str::startsWith((string) $mimeType, 'image')) {
            return null;
        }

        $extension = $isExternalPath
            ? strtolower(pathinfo((string) $file, PATHINFO_EXTENSION)) ?: 'png'
            : (strtolower($file->getClientOriginalExtension()) ?: 'png');

        $filename = $filenamePrefix . ProductMediaSeo::productMediaFilename($product, 'screenshot', $extension, 1);
        $path = 'product_media/' . $filename;

        if ($isExternalPath) {
            Storage::disk('public')->put($path, file_get_contents($absolutePath));
        } else {
            $path = $file->storeAs('product_media', $filename, 'public');
            $absolutePath = Storage::disk('public')->path($path);
        }

        $pathThumb = null;
        $pathMedium = null;

        try {
            $storedFilename = basename($path);
            $directory = dirname($path);

            $imageThumb = $manager->read($absolutePath);
            $imageThumb->scale(width: 300);
            $pathThumb = $directory . '/thumb_' . $storedFilename;
            Storage::disk('public')->put($pathThumb, (string) $imageThumb->encode());

            $imageMedium = $manager->read($absolutePath);
            $imageMedium->scale(width: 800);
            $pathMedium = $directory . '/medium_' . $storedFilename;
            Storage::disk('public')->put($pathMedium, (string) $imageMedium->encode());
        } catch (\Throwable $e) {
            \Log::warning('Image resizing skipped: ' . $e->getMessage());
        }

        return [
            'path' => $path,
            'path_thumb' => $pathThumb,
            'path_medium' => $pathMedium,
        ];
    }

    private function downloadMediaUrlToTemporaryPublicPath(string $url): ?string
    {
        try {
            $appUrl = config('app.url');
            $isLocal = str_starts_with($url, $appUrl . '/storage/')
                || str_starts_with($url, '/storage/')
                || str_contains($url, '/storage/screenshots/');

            if ($isLocal) {
                $storagePath = preg_replace('#^.*?/storage/#', '', $url);

                if (Storage::disk('public')->exists($storagePath)) {
                    $extension = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'jpg';
                    $path = 'product_media/tmp-' . Str::uuid() . '.' . $extension;
                    Storage::disk('public')->copy($storagePath, $path);

                    return $path;
                }
            }

            $response = Http::get($url);
            if (!$response->successful()) {
                return null;
            }

            $extension = 'jpg';
            if (str_contains($url, '.png')) {
                $extension = 'png';
            } elseif (str_contains($url, '.webp')) {
                $extension = 'webp';
            } elseif (str_contains($url, '.avif')) {
                $extension = 'avif';
            }

            $path = 'product_media/tmp-' . Str::uuid() . '.' . $extension;
            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Throwable $e) {
            \Log::error('Failed to process media from URL: ' . $url . ' - ' . $e->getMessage());

            return null;
        }
    }

    private function deleteMediaFiles(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path && !Str::startsWith($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
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
