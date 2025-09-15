<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Type;
use App\Models\PremiumProduct;
use App\Models\TechStack;
use App\Models\UserProductUpvote; // Added for upvote checking
use App\Models\Ad; // Added for Ad model
use App\Models\AdZone; // Added for AdZone model
use App\Models\User;
use App\Notifications\ProductSubmitted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Ensure Storage facade is imported
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session; // Added for session management
use Illuminate\Support\Facades\Log; // Added for logging
use App\Services\FaviconExtractorService;
use App\Services\SlugService;
use App\Services\CategoryClassifier;
use App\Services\TechStackDetectorService;
use App\Jobs\FetchOgImage;
use Intervention\Image\Laravel\Facades\Image;
use DOMDocument;

class ProductController extends Controller
{
    protected FaviconExtractorService $faviconExtractor;
    protected SlugService $slugService;
    protected CategoryClassifier $categoryClassifier;
    protected TechStackDetectorService $techStackDetector;

    public function __construct(FaviconExtractorService $faviconExtractor, SlugService $slugService, CategoryClassifier $categoryClassifier, TechStackDetectorService $techStackDetector)
    {
        $this->faviconExtractor = $faviconExtractor;
        $this->slugService = $slugService;
        $this->categoryClassifier = $categoryClassifier;
        $this->techStackDetector = $techStackDetector;
    }

    public function home(Request $request)
    {
        $now = Carbon::now();
        return redirect()->route('products.byWeek', ['year' => $now->year, 'week' => $now->weekOfYear]);

        $displayDateString = $serverTodayDate->toDateString();

        $categories = Category::withCount(['products' => function ($query) {
            $query->where('approved', true)
                ->where('is_published', true);
        }])->orderByDesc('products_count')->orderBy('name')->get();

        $allTypes = Type::with(['categories' => function ($query) {
            $query->withCount(['products' => function ($subQuery) {
                $subQuery->where('approved', true)
                    ->where('is_published', true);
            }])->orderByDesc('products_count')->orderBy('name');
        }])->orderBy('name')->get();

        $softwareTypes = $allTypes->filter(fn($type) => $type->name === 'Software Categories');
        $pricingTypes = $allTypes->filter(fn($type) => $type->name === 'Pricing');
        $otherTypes = $allTypes->filter(fn($type) => !in_array($type->name, ['Software Categories', 'Pricing']));
        $types = $softwareTypes->concat($otherTypes)->concat($pricingTypes);

        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position'); // Key by position for easy lookup

        $baseRegularProductsQuery = Product::where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true);

        // Apply date range filters to the base query for regular products
        switch ($range) {
            case 'weekly':
                $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
                $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();
                $baseRegularProductsQuery->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfWeek, $endOfWeek]);
                break;
            case 'monthly':
                $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
                $today = Carbon::now()->toDateString();
                $baseRegularProductsQuery->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfMonth, $today]);
                break;
            case 'yearly':
                $startOfYear = Carbon::now()->startOfYear()->toDateString();
                $today = Carbon::now()->toDateString();
                $baseRegularProductsQuery->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfYear, $today]);
                break;
        }

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'home_' . $range);

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $totalProductsCount = $shuffledRegularProductIds->count() + $promotedProducts->count();
        $paginatedRegularProductIds = [];
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($shuffledRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $shuffledRegularProductIds[$regularProductIndex];
                    $regularProductIndex++;
                }
            }
        }

        // Manually paginate the final product order (which contains mixed Product objects and IDs)
        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);

        // Separate IDs from Product objects to fetch full details for regular products
        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn($item) => is_numeric($item))->values();
        $promotedProductsOnPage = collect($currentPageItems)->filter(fn($item) => is_object($item))->values();

        $regularProductsOnPageQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
        ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        // Merge and re-sort to maintain the exact order
        $combinedProducts = collect();
        foreach ($currentPageItems as $item) {
            if (is_object($item)) { // It's a promoted product object
                $combinedProducts->push($item);
            } else { // It's a regular product ID
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        // Create a LengthAwarePaginator manually
        $regularProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedProducts,
            $totalProductsCount,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $headerAd = Ad::whereHas('adZones', fn($q) => $q->where('slug', 'header-above-calendar'))->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
        $sidebarTopAd = Ad::whereHas('adZones', fn($q) => $q->where('slug', 'sidebar-top'))->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
        $belowProductListingAdZone = AdZone::where('slug', 'below-product-listing')->first();
        $belowProductListingAd = null;
        $belowProductListingAdPosition = null;
        if ($belowProductListingAdZone) {
            $belowProductListingAd = $belowProductListingAdZone->ads()->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
            if ($belowProductListingAd) {
                $belowProductListingAdPosition = $belowProductListingAdZone->display_after_nth_product;
            }
        }

        $activeDates = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('DISTINCT DATE(COALESCE(published_at, created_at)) as date')
            ->pluck('date')
            ->toArray();
        
        $premiumProducts = PremiumProduct::with('product.categories.types', 'product.user', 'product.userUpvotes')
            ->where('expires_at', '>', now())
            ->get()
            ->pluck('product')
            ->shuffle();

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact(
            'categories', 'types',
            'regularProducts',
            'premiumProducts',
            'alpineProducts',
            'serverTodayDateString', 'displayDateString',
            'headerAd', 'sidebarTopAd',
            'belowProductListingAd', 'belowProductListingAdPosition',
            'activeDates',
            'nextLaunchTime'
        ));
    }

    public function create()
    {
        $types = Type::with('categories')->get();
        $allCategories = Category::with('types')->orderBy('name')->get();
        $allTechStacks = TechStack::orderBy('name')->get();
        $allCategoriesData = $allCategories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'types' => $category->types->pluck('name')->toArray(),
            ];
        });
        $allTechStacksData = $allTechStacks->map(function ($ts) {
            return [
                'id' => $ts->id,
                'name' => $ts->name,
            ];
        });
        $categories = $allCategories;
        $oldInput = session()->getOldInput();
        $displayData = [
            'name' => $oldInput['name'] ?? '',
            'slug' => $oldInput['slug'] ?? '',
            'link' => $oldInput['link'] ?? '',
            'tagline' => $oldInput['tagline'] ?? '',
            'product_page_tagline' => $oldInput['product_page_tagline'] ?? '',
            'description' => $oldInput['description'] ?? '',
            'current_categories' => $oldInput['categories'] ?? [],
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? [],
        ];

        return view('products.create', compact('categories', 'types', 'allCategoriesData', 'displayData', 'allTechStacksData'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'logo_url' => 'nullable|url|max:2048',
            'video_url' => 'nullable|url|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        $existsCheck = function ($slug) {
            return Product::where('slug', $slug)->exists();
        };

        $validated['slug'] = $this->slugService->generateUniqueSlug($validated['name'], $existsCheck);

        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $softwareType = Type::where('name', 'Software Categories')->with('categories')->first();
        $selected = collect($request->input('categories', []))->map(fn($id) => (int)$id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id')->map(fn($id) => (int)$id) : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id')->map(fn($id) => (int)$id) : collect();

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }

        $validated['user_id'] = Auth::id();
        $validated['votes_count'] = 0;
        $validated['approved'] = false;
        
        if ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $extension = $image->getClientOriginalExtension();
            $filename = Str::uuid();
            $path = 'logos/';

            if ($extension === 'svg') {
                $filenameWithExtension = $filename . '.svg';
                $image->storePubliclyAs($path, $filenameWithExtension, 'public');
                $validated['logo'] = $path . $filenameWithExtension;
            } else {
                $filenameWithExtension = $filename . '.webp';
                $img = \Intervention\Image\Laravel\Facades\Image::make($image->getRealPath());
                $encodedImage = $img->toWebp(80); // Convert to WebP with 80% quality
                Storage::disk('public')->put($path . $filenameWithExtension, (string) $encodedImage);
                $validated['logo'] = $path . $filenameWithExtension;
            }
        } elseif ($request->filled('logo_url')) {
            $validated['logo'] = $validated['logo_url'];
        }
        unset($validated['logo_url']);

        $validated['description'] = $this->addNofollowToLinks($validated['description']);
        $product = Product::create($validated);
        $product->categories()->sync($validated['categories']);
        if (isset($validated['tech_stacks'])) {
            $product->techStacks()->sync($validated['tech_stacks']);
        }

        FetchOgImage::dispatch($product);

        $admins = User::getAdmins();
        Notification::send($admins, new ProductSubmitted($product));

        return redirect()->route('products.submission.success', ['product' => $product->id]);
    }

    public function showSubmissionSuccess(Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !(Auth::check() && $user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $submissionDate = Carbon::now();
        $tentativeLiveDate = $submissionDate->copy()->addWeeks(2);
        $daysToLive = $submissionDate->diffInDays($tentativeLiveDate);

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);
        $totalSpots = $settings['premium_product_spots'] ?? 6; // Use 6 as default if not found in settings
        $spotsTaken = PremiumProduct::where('expires_at', '>', now())->count();
        $spotsAvailable = $totalSpots - $spotsTaken;

        return view('products.submission_success', compact('product', 'daysToLive', 'spotsAvailable'));
    }

    public function edit(Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !(Auth::check() && $user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $allCategories = Category::with('types')->orderBy('name')->get();
        $allTechStacks = TechStack::orderBy('name')->get();
        $allCategoriesData = $allCategories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'types' => $category->types->pluck('name')->toArray(),
            ];
        });
        $allTechStacksData = $allTechStacks->map(function ($ts) {
            return [
                'id' => $ts->id,
                'name' => $ts->name,
            ];
        });
        $categories = $allCategories;
        $types = Type::with('categories')->get();
        $product->load(['categories', 'proposedCategories', 'techStacks']); // Eager load proposed categories as well

        // Prepare data for the view, prioritizing proposed edits if they exist and product is approved
        $oldInput = session()->getOldInput();
        $displayData = [
            'name' => $oldInput['name'] ?? $product->name,
            'slug' => $oldInput['slug'] ?? $product->slug,
            'link' => $oldInput['link'] ?? $product->link,
            'logo' => $product->logo, // Current live logo
            'tagline' => $oldInput['tagline'] ?? $product->tagline,
            'description' => $oldInput['description'] ?? $product->description,
            'current_categories' => $oldInput['categories'] ?? $product->categories->pluck('id')->toArray(),
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? $product->techStacks->pluck('id')->toArray(),
        ];

        if ($product->approved && $product->has_pending_edits) {
            $displayData['logo'] = $product->proposed_logo_path ?? $product->logo;
            $displayData['tagline'] = $product->proposed_tagline ?? $product->tagline;
            $displayData['description'] = $product->proposed_description ?? $product->description;
            $displayData['current_categories'] = $product->proposedCategories->pluck('id')->toArray(); // Use proposed categories for form prefill
        }
        
        // The view 'products.create' is used for both create and edit.
        // We pass $product for existing product context, and $displayData for form fields.
        return view('products.create', compact('product', 'categories', 'types', 'displayData', 'allCategoriesData', 'allTechStacksData'));
    }

    public function update(Request $request, Product $product)
    {
        // Authorization: User can only update their own products. Admins can update any.
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !(Auth::check() && $user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        // Validation rules for editable fields
        $validated = $request->validate([
            // 'name' and 'slug' are not editable by users directly in this form
            'tagline' => 'required|string|max:60', // Max 60 chars
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'required|string|max:5000', // Max 5000 chars
            // 'link' is not editable by users directly in this form
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048', // File upload for logo
            'remove_logo' => 'nullable|boolean', // For removing existing logo
            'video_url' => 'nullable|url|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        // Category validation (ensure at least one from each required type is selected)
        // This logic can be kept or adjusted based on whether proposed edits should also adhere to it.
        // For simplicity, we'll assume it applies.
        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $softwareType = Type::where('name', 'Software Categories')->with('categories')->first(); // Assuming this type name
        $selected = collect($request->input('categories', []))->map(fn($id) => (int)$id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id')->map(fn($id) => (int)$id) : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id')->map(fn($id) => (int)$id) : collect();

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }

        // Prepare data for update
        $updateData = [
            'tagline' => $validated['tagline'],
            'product_page_tagline' => $validated['product_page_tagline'],
            'description' => $this->addNofollowToLinks($validated['description']),
            'video_url' => $validated['video_url'],
        ];
        $newCategories = $validated['categories'];
        $newTechStacks = $validated['tech_stacks'] ?? [];
        $logoPath = null;

        // Handle logo upload
        if ($request->boolean('remove_logo')) {
            $logoPath = null; // Explicitly set to null for removal
        } elseif ($request->hasFile('logo')) {
            $image = $request->file('logo');
            $extension = $image->getClientOriginalExtension();
            $filename = Str::uuid();
            $logoPath = 'logos/';

            if ($extension === 'svg') {
                $filenameWithExtension = $filename . '.svg';
                $image->storePubliclyAs($logoPath, $filenameWithExtension, 'public');
                $logoPath .= $filenameWithExtension;
            } else {
                $filenameWithExtension = $filename . '.webp';
                $img = \Intervention\Image\Laravel\Facades\Image::make($image->getRealPath());
                $encodedImage = $img->toWebp(80); // Convert to WebP with 80% quality
                Storage::disk('public')->put($logoPath . $filenameWithExtension, (string) $encodedImage);
                $logoPath .= $filenameWithExtension;
            }
        }
 
        if ($product->approved) {
            // Product is approved, store edits as proposed changes
            if ($request->boolean('remove_logo')) {
                // If there was a proposed logo, delete it. The live logo remains.
                if ($product->proposed_logo_path && !Str::startsWith($product->proposed_logo_path, 'http')) {
                    Storage::disk('public')->delete($product->proposed_logo_path);
                }
                $product->proposed_logo_path = null;
            } elseif ($logoPath) {
                 // If there was a previous proposed logo, delete it before storing the new one
                if ($product->proposed_logo_path && !Str::startsWith($product->proposed_logo_path, 'http')) {
                    Storage::disk('public')->delete($product->proposed_logo_path);
                }
                $product->proposed_logo_path = $logoPath;
            }
            // Only update proposed_logo_path if a new logo was uploaded or explicitly removed.
            // If no new logo and not removed, proposed_logo_path remains unchanged (or null if never set).

            $product->proposed_tagline = $updateData['tagline'];
            $product->product_page_tagline = $updateData['product_page_tagline'];
            $product->proposed_description = $updateData['description'];
            $product->proposedCategories()->sync($newCategories);
            $product->techStacks()->sync($newTechStacks);
            $product->has_pending_edits = true;
            $product->save(); // Save these specific fields and the flag

            return redirect()->route('products.my')->with('success', 'Your proposed edits have been submitted for review.');

        } else {
            // Product is not yet approved, update directly
            if ($request->boolean('remove_logo')) {
                if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                    Storage::disk('public')->delete($product->logo);
                }
                $updateData['logo'] = null;
            } elseif ($logoPath) {
                if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                    Storage::disk('public')->delete($product->logo);
                }
                $updateData['logo'] = $logoPath;
            }
            // If no new logo and not removed, $updateData['logo'] will not be set, existing logo remains.

            // For non-approved products, also clear any potential "proposed" fields
            // in case its status was toggled or for data consistency.
            if ($product->proposed_logo_path) {
                Storage::disk('public')->delete($product->proposed_logo_path);
            }
            $product->proposed_logo_path = null;
            $product->proposed_tagline = null;
            $product->proposed_description = null;
            $product->proposedCategories()->detach(); // Clear proposed categories
            $product->has_pending_edits = false; // Ensure this is false

            // Update main product fields
            $product->update($updateData);
            $product->categories()->sync($newCategories);
            $product->techStacks()->sync($newTechStacks);
            // 'approved' status remains false as it's handled by admin

            return redirect()->route('products.my')->with('success', 'Product updated successfully. It is awaiting approval.');
        }
    }

    public function checkUrl(Request $request)
    {
        $url = $request->query('url');
        if (!$url) {
            return response()->json(['exists' => false]);
        }
        $parsed = parse_url($url);
        $base = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['path'])) {
            $base .= rtrim($parsed['path'], '/');
        }
        $baseWithSlash = $base . '/';
        $exists = Product::where(function ($q) use ($base, $baseWithSlash) {
            $q->where('link', $base)->orWhere('link', $baseWithSlash);
        })->exists();
        return response()->json(['exists' => $exists]);
    }

    public function categoryProducts(Category $category)
    {
        // 1. Fetch Promoted Products (always shown, regardless of category filter for regular products)
        // Note: For category pages, you might decide if promoted products should *also* belong to the current category.
        // The current requirement is "immunity to filters", so we fetch all promoted products.
        // If they should be filtered by category, add ->whereHas('categories', fn($q) => $q->where('category_id', $category->id))
        $promotedProductsQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc');
        
        $promotedProducts = $promotedProductsQuery->get();

        // 2. Fetch Regular (non-promoted) Products for the current category
        $regularProductsQuery = $category->products()
            ->where('approved', true)
            ->where('is_promoted', false) // Exclude promoted products
            ->where('is_published', true)
            ->with(['categories.types', 'user', 'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }]);
        
        $regularProducts = $regularProductsQuery->orderByDesc('votes_count')->orderBy('name', 'asc')->paginate(15);

        // Alpine products mapping - based on all products for the modal.
        $allProducts = $promotedProducts->merge($regularProducts);
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(function ($type) {
                            return ['name' => $type->name];
                        })->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        // Fetch all types with their categories and product counts
        $allTypesCollection = Type::with(['categories' => function($query) {
            $query->withCount('products')->orderByDesc('products_count')->orderBy('name');
        }])->orderBy('name')->get();

        // Separate types into Software, Pricing, and Others
        $softwareTypes = $allTypesCollection->filter(function ($type) {
            return $type->name === 'Software Categories'; // Assuming 'Software Categories' is the name
        });

        $pricingTypes = $allTypesCollection->filter(function ($type) {
            return $type->name === 'Pricing';
        });

        $otherTypes = $allTypesCollection->filter(function ($type) {
            return !in_array($type->name, ['Software Categories', 'Pricing']);
        });

        $types = $softwareTypes->concat($otherTypes)->concat($pricingTypes);

        // Fetch ads for this category page
        $headerAd = Ad::whereHas('adZones', fn($q) => $q->where('slug', 'header-above-calendar'))->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
        $sidebarTopAd = Ad::whereHas('adZones', fn($q) => $q->where('slug', 'sidebar-top'))->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
        $belowProductListingAdZone = AdZone::where('slug', 'below-product-listing')->first();
        $belowProductListingAd = null;
        $belowProductListingAdPosition = null;
        if ($belowProductListingAdZone) {
            $belowProductListingAd = $belowProductListingAdZone->ads()->where('is_active', true)->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', Carbon::now()))->where(fn($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', Carbon::now()))->inRandomOrder()->first();
            if ($belowProductListingAd) {
                $belowProductListingAdPosition = $belowProductListingAdZone->display_after_nth_product;
            }
        }

        $categories = Category::withCount(['products' => function ($query) {
            $query->where('approved', true)
                ->where('is_published', true);
        }])->orderBy('name')->get();

        $currentYear = Carbon::now()->year;
        $title = "The Best " . strip_tags($category->name) . " Software Products of " . $currentYear;
        $isCategoryPage = true;

        $premiumProducts = PremiumProduct::with('product.categories.types', 'product.user', 'product.userUpvotes')
            ->where('expires_at', '>', now())
            ->get()
            ->pluck('product')
            ->shuffle();

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact(
            'category', 'categories', 'types',
            'promotedProducts', 'regularProducts', 'premiumProducts', 'alpineProducts',
            'headerAd', 'sidebarTopAd',
            'belowProductListingAd', 'belowProductListingAdPosition',
            'title', 'isCategoryPage',
            'nextLaunchTime'
        ));
    }

    public function getProductDates()
    {
        $dates = Product::where('approved', true)
            ->where('is_published', true)
            ->select(DB::raw('DISTINCT COALESCE(DATE(published_at), DATE(created_at)) as product_date'))
            ->orderBy('product_date', 'desc')
            ->pluck('product_date');

        return response()->json($dates);
    }

    public function productsByDate(Request $request, $date, $isHomepage = false)
    {
        try {
            $date = Carbon::parse($date);
        } catch (\Exception $e) {
            abort(404, 'Invalid date format provided.');
        }

        $baseRegularProductsQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->where(function ($query) use ($date) {
                $query->whereDate('published_at', $date->toDateString());
            });

        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'date_' . $date->toDateString());

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $totalProductsCount = $shuffledRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($shuffledRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $shuffledRegularProductIds[$regularProductIndex];
                    $regularProductIndex++;
                }
            }
        }

        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);

        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn($item) => is_numeric($item))->values();
        $promotedProductsOnPage = collect($currentPageItems)->filter(fn($item) => is_object($item))->values();

        $regularProductsOnPageQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
        ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        $combinedProducts = collect();
        foreach ($currentPageItems as $item) {
            if (is_object($item)) {
                $combinedProducts->push($item);
            } else {
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        $regularProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedProducts,
            $totalProductsCount,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $date->toDateString();

        if ($isHomepage) {
            $title = 'Top Products';
            $pageTitle = 'Top Products - Software on the web';
        } else {
            $title = 'Top Products';
            $pageTitle = 'Top Products';
        }
        
        $activeDates = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('DISTINCT DATE(COALESCE(published_at, created_at)) as date')
            ->pluck('date')
            ->toArray();

        if ($request->ajax()) {
            return view('partials.products_list_with_pagination', compact('regularProducts', 'promotedProducts'))->render();
        }

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $dayOfYear = $date->dayOfYear;
        $fullDate = $date->format('d F, Y');

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'activeDates', 'alpineProducts', 'dayOfYear', 'fullDate', 'nextLaunchTime'));
    }
    public function redirectToCurrentWeek()
    {
        $now = Carbon::now();
        return redirect()->route('products.byWeek', ['year' => $now->year, 'week' => $now->weekOfYear]);
    }

    public function redirectToCurrentMonth()
    {
        $now = Carbon::now();
        return redirect()->route('products.byMonth', ['year' => $now->year, 'month' => $now->month]);
    }

    public function redirectToCurrentYear()
    {
        $now = Carbon::now();
        return redirect()->route('products.byYear', ['year' => $now->year]);
    }

    public function productsByWeek(Request $request, $year, $week)
    {
        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $baseRegularProductsQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfWeek->toDateString(), $endOfWeek->toDateString()]);

        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'week_' . $year . '_' . $week);

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $totalProductsCount = $shuffledRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($shuffledRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $shuffledRegularProductIds[$regularProductIndex];
                    $regularProductIndex++;
                }
            }
        }

        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);

        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn($item) => is_numeric($item))->values();
        $promotedProductsOnPage = collect($currentPageItems)->filter(fn($item) => is_object($item))->values();

        $regularProductsOnPageQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
        ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        $combinedProducts = collect();
        foreach ($currentPageItems as $item) {
            if (is_object($item)) {
                $combinedProducts->push($item);
            } else {
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        $regularProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedProducts,
            $totalProductsCount,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfWeek->toDateString();
        $title = 'This Week'; // For potential in-page display
        $pageTitle = 'Best of Week ' . $week . ' of ' . $year . ' | Software on the web'; // For <title> tag

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        $weekOfYear = $week;

        $activeWeeks = Product::where('approved', true)
            ->where('is_published', true)
            ->selectRaw('DISTINCT CONCAT(YEAR(COALESCE(published_at, created_at)), "-", WEEK(COALESCE(published_at, created_at), 1)) as week')
            ->pluck('week')
            ->toArray();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'alpineProducts', 'nextLaunchTime', 'weekOfYear', 'year', 'activeWeeks'));
    }

    public function productsByMonth(Request $request, $year, $month)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $baseRegularProductsQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);

        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'month_' . $year . '_' . $month);

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $totalProductsCount = $shuffledRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($shuffledRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $shuffledRegularProductIds[$regularProductIndex];
                    $regularProductIndex++;
                }
            }
        }

        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);

        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn($item) => is_numeric($item))->values();
        $promotedProductsOnPage = collect($currentPageItems)->filter(fn($item) => is_object($item))->values();

        $regularProductsOnPageQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
        ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        $combinedProducts = collect();
        foreach ($currentPageItems as $item) {
            if (is_object($item)) {
                $combinedProducts->push($item);
            } else {
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        $regularProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedProducts,
            $totalProductsCount,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfMonth->toDateString();
        $title = 'on ' . $startOfMonth->format('F Y'); // For potential in-page display
        $pageTitle = 'Best of ' . $startOfMonth->format('F Y') . ' | Software on the web'; // For <title> tag

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'alpineProducts', 'nextLaunchTime'));
    }

    public function productsByYear(Request $request, $year)
    {
        $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfYear = $startOfYear->copy()->endOfYear();

        $baseRegularProductsQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfYear->toDateString(), $endOfYear->toDateString()]);

        $promotedProducts = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'year_' . $year);

        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;

        $totalProductsCount = $shuffledRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($shuffledRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $shuffledRegularProductIds[$regularProductIndex];
                    $regularProductIndex++;
                }
            }
        }

        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);

        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn($item) => is_numeric($item))->values();
        $promotedProductsOnPage = collect($currentPageItems)->filter(fn($item) => is_object($item))->values();

        $regularProductsOnPageQuery = Product::with(['categories.types', 'user', 'userUpvotes' => function ($query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        }])
        ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        $combinedProducts = collect();
        foreach ($currentPageItems as $item) {
            if (is_object($item)) {
                $combinedProducts->push($item);
            } else {
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        $regularProducts = new \Illuminate\Pagination\LengthAwarePaginator(
            $combinedProducts,
            $totalProductsCount,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfYear->toDateString();
        $title = 'in ' . $year; // For potential in-page display
        $pageTitle = 'Best of ' . $year . ' | Software on the web'; // For <title> tag

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine
        $alpineProducts = $allProducts->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => $product->isUpvotedByCurrentUser ?? false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'alpineProducts', 'nextLaunchTime'));
    }

    public function search(Request $request)
    {
        $term = $request->input('term');
        if (empty($term)) {
            return response()->json([]);
        }

        $products = Product::where('approved', true)
            ->where('is_published', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', "%{$term}%")
                    ->orWhere('tagline', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->with('categories') // Eager load categories
            ->orderBy('votes_count', 'desc')
            ->take(10)
            ->get();

        // Manually construct the JSON response to include necessary fields
        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo ? (Str::startsWith($product->logo, 'http') ? $product->logo : asset('storage/' . $product->logo)) : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'types' => $category->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        });

        return response()->json($results);
    }

    public function myProducts(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->input('per_page', 15);
        $allowedPerPages = [15, 30, 50, 100];
        if (!in_array($perPage, $allowedPerPages)) {
            $perPage = 15;
        }

        $myProducts = Product::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('products.my_products', [
            'products' => $myProducts,
            'perPage' => $perPage,
            'allowedPerPages' => $allowedPerPages,
        ]);
    }

    public function showProductPage(Product $product)
    {
        if (!$product->approved || !$product->is_published) {
            abort(404);
        }

        $product->load('categories.types', 'user', 'userUpvotes');

        $pricingCategory = $product->categories->first(function ($category) {
            return $category->types->contains('name', 'Pricing');
        });

        $categoryIds = $product->categories->pluck('id');

        $similarProducts = Product::where('id', '!=', $product->id)
            ->where('approved', true)
            ->where('is_published', true)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->orderByDesc('votes_count')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $title = $product->name;
        $pageTitle = $product->name . ': ' . $product->product_page_tagline . ' - Software on the Web';

        $description = strip_tags($product->description);
        $metaDescription = Str::limit($description, 160);

        return view('products.show', compact('product', 'title', 'pageTitle', 'pricingCategory', 'similarProducts', 'metaDescription'));
    }

    /**
     * Helper function to get shuffled product IDs for a given date range and filters.
     * The shuffle is seeded daily per session to ensure fairness and consistency for the user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The base query for regular products.
     * @param string $cacheKeySuffix A suffix for the cache key to differentiate between different product lists (e.g., 'home', 'category_X', 'date_Y').
     * @return \Illuminate\Support\Collection
     */
    protected function getShuffledProductIds($query, $cacheKeySuffix)
    {
        $today = Carbon::now()->toDateString();
        $sessionId = Session::getId();
        $seed = crc32($today . '_' . $sessionId); // Generate a consistent seed for the day and session

        $cacheKey = 'shuffled_product_ids_' . $cacheKeySuffix . '_' . $today . '_' . $sessionId;

        // Try to retrieve from cache first
        $shuffledIds = cache()->remember($cacheKey, Carbon::tomorrow()->diffInMinutes(), function () use ($query, $seed) {
            $allProductIds = $query->pluck('id'); // Get all IDs first
            
            // Convert to array, shuffle with seed, then convert back to collection
            $shuffledArray = $allProductIds->shuffle($seed)->all();
            return collect($shuffledArray);
        });

        return $shuffledIds;
    }
    private function addNofollowToLinks($html)
    {
        if (empty($html)) {
            return $html;
        }

        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            $link->setAttribute('rel', 'nofollow');
        }

        return $dom->saveHTML();
    }
    public function fetchUrlData(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch URL data', ['url' => $url, 'status' => $response->status()]);
                return response()->json(['error' => 'Failed to fetch data from the URL.'], 500);
            }

            $html = $response->body();
            $doc = new DOMDocument();
            @$doc->loadHTML($html);

            $titleNode = $doc->getElementsByTagName('title')->item(0);
            $title = $titleNode ? $titleNode->nodeValue : '';

            $description = '';
            $ogImage = '';
            $ogImages = [];
            $logos = [];

            $metas = $doc->getElementsByTagName('meta');
            for ($i = 0; $i < $metas->length; $i++) {
                $meta = $metas->item($i);
                if (strtolower($meta->getAttribute('name')) == 'description') {
                    $description = $meta->getAttribute('content');
                }
                if (strtolower($meta->getAttribute('property')) == 'og:image') {
                    $ogImageContent = $meta->getAttribute('content');
                    if ($ogImageContent) {
                        $ogImages[] = $this->resolveUrl($url, $ogImageContent);
                    }
                }
            }

            if (!empty($ogImages)) {
                $ogImage = $ogImages[0];
                $logos = array_merge($logos, $ogImages);
            }

            $links = $doc->getElementsByTagName('link');
            foreach ($links as $link) {
                $rel = strtolower($link->getAttribute('rel'));
                if (in_array($rel, ['icon', 'shortcut icon', 'apple-touch-icon'])) {
                    $href = $link->getAttribute('href');
                    if ($href) {
                        $logos[] = $this->resolveUrl($url, $href);
                    }
                }
            }

            $images = $doc->getElementsByTagName('img');
            foreach ($images as $img) {
                $src = $img->getAttribute('src');
                if (preg_match('/logo/i', $src)) {
                    $logos[] = $this->resolveUrl($url, $src);
                }
            }

            $logos = array_values(array_unique($logos));
            if (empty($logos)) {
                $logos[] = 'https://www.google.com/s2/favicons?sz=128&domain_url=' . urlencode($url);
            }
            $logos = $this->rankAndSelectLogos($logos);


            $categoryNames = array_keys($this->categoryClassifier->classify($html));
            $categoryIds = \App\Models\Category::whereIn('name', $categoryNames)->pluck('id')->toArray();
            Log::info('Classified Categories:', ['url' => $url, 'categories' => $categoryIds]);

            $techStackNames = $this->techStackDetector->detect($url);
            $techStackIds = \App\Models\TechStack::whereIn('name', $techStackNames)->pluck('id')->toArray();
            Log::info('Detected Tech Stacks:', ['url' => $url, 'tech_stacks' => $techStackIds]);

            return response()->json([
                'title' => trim($title),
                'description' => trim($description),
                'og_image' => $ogImage,
                'logos' => array_values($logos),
                'og_images' => array_values(array_unique($ogImages)),
                'categories' => $categoryIds,
                'tech_stacks' => $techStackIds,
            ]);

        } catch (\Exception $e) {
            Log::error('Exception when fetching URL data', ['url' => $url, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'An unexpected error occurred.'], 500);
        }
    }

    private function resolveUrl($baseUrl, $relativeUrl)
    {
        if (Str::startsWith($relativeUrl, ['http://', 'https://', '//'])) {
            if (Str::startsWith($relativeUrl, '//')) {
                return 'https:' . $relativeUrl;
            }
            return $relativeUrl;
        }

        $base = parse_url($baseUrl);
        $path = $base['path'] ?? '';

        if (Str::startsWith($relativeUrl, '/')) {
            $path = '';
        } else {
            $path = dirname($path);
        }
        
        $path = rtrim($path, '/');

        return $base['scheme'] . '://' . $base['host'] . $path . '/' . ltrim($relativeUrl, '/');
    }
    private function rankAndSelectLogos(array $logos): array
    {
        $scoredLogos = [];
        foreach ($logos as $logo) {
            $score = 0;
            if (stripos($logo, 'logo') !== false) {
                $score += 5;
            }
            if (stripos($logo, '.svg') !== false) {
                $score += 3;
            }
            if (stripos($logo, '.png') !== false) {
                $score += 2;
            }
            if (stripos($logo, '.jpg') !== false || stripos($logo, '.jpeg') !== false || stripos($logo, '.webp') !== false) {
                $score += 1;
            }
            $scoredLogos[] = ['url' => $logo, 'score' => $score];
        }

        usort($scoredLogos, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice(array_column($scoredLogos, 'url'), 0, 6);
    }
}
