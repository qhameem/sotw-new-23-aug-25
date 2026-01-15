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
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // Ensure Storage facade is imported
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Session; // Added for session management
use Illuminate\Support\Facades\Log; // Added for logging
use App\Services\FaviconExtractorService;
use App\Services\SlugService;
use App\Services\TechStackDetectorService;
use App\Services\NameExtractorService;
use App\Services\LogoExtractorService;
use App\Jobs\FetchOgImage;
use DOMDocument;

class ProductController extends Controller
{
    protected FaviconExtractorService $faviconExtractor;
    protected SlugService $slugService;
    protected TechStackDetectorService $techStackDetector;
    protected NameExtractorService $nameExtractor;
    protected LogoExtractorService $logoExtractor;
    protected \App\Services\CategoryClassifier $categoryClassifier;

    public function __construct(FaviconExtractorService $faviconExtractor, SlugService $slugService, TechStackDetectorService $techStackDetector, NameExtractorService $nameExtractor, LogoExtractorService $logoExtractor, \App\Services\CategoryClassifier $categoryClassifier)
    {
        $this->faviconExtractor = $faviconExtractor;
        $this->slugService = $slugService;
        $this->techStackDetector = $techStackDetector;
        $this->nameExtractor = $nameExtractor;
        $this->logoExtractor = $logoExtractor;
        $this->categoryClassifier = $categoryClassifier;
    }

    public function home(Request $request)
    {
        $now = Carbon::now();

        // Find the last available week with products (most recent week with approved products)
        // Use the current date to determine the current week properly
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $lastAvailableWeek = $this->findLastAvailableWeekWithProducts($startOfWeek);

        if ($lastAvailableWeek) {
            $year = $lastAvailableWeek->year;
            $week = $lastAvailableWeek->weekOfYear;
            // Debug: Log which week was found
            \Log::info("Home page: Found week {$week} of year {$year} as the most recent week with products");
        } else {
            // If no weeks with products are found, default to current week
            $year = $now->year;
            $week = $now->weekOfYear;
            \Log::info("Home page: No weeks with products found, defaulting to current week {$week} of year {$year}");
        }

        return $this->productsByWeek($request, $year, $week, true);
    }

    public function create()
    {
        $allTechStacks = TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);

        $categoryTypes = json_decode(Storage::get('category_types.json'), true);
        $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
        $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
        $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

        $regularCategoryIds = DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
        $pricingCategoryIds = DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
        $bestForCategoryIds = DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

        $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get();
        $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get();
        $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get();

        $oldInput = session()->getOldInput();
        $displayData = [
            'name' => $oldInput['name'] ?? '',
            'slug' => $oldInput['slug'] ?? '',
            'link' => $oldInput['link'] ?? '',
            'tagline' => $oldInput['tagline'] ?? '',
            'product_page_tagline' => $oldInput['product_page_tagline'] ?? '',
            'description' => $oldInput['description'] ?? '',
            'logo' => null,
            'video_url' => null,
            'current_categories' => $oldInput['categories'] ?? [],
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? [],
        ];

        $types = Type::with('categories')->get();
        return view('products.create', compact(
            'displayData',
            'regularCategories',
            'bestForCategories',
            'pricingCategories',
            'allTechStacksData',
            'types'
        ));
    }

    public function createSubmission()
    {
        $allTechStacks = TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);

        $categoryTypes = json_decode(Storage::get('category_types.json'), true);
        $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
        $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
        $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

        $regularCategoryIds = DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
        $pricingCategoryIds = DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
        $bestForCategoryIds = DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

        $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get();
        $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get();
        $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get();

        $oldInput = session()->getOldInput();
        $displayData = [
            'name' => $oldInput['name'] ?? '',
            'slug' => $oldInput['slug'] ?? '',
            'link' => $oldInput['link'] ?? '',
            'tagline' => $oldInput['tagline'] ?? '',
            'product_page_tagline' => $oldInput['product_page_tagline'] ?? '',
            'description' => $oldInput['description'] ?? '',
            'logo' => null,
            'video_url' => null,
            'current_categories' => $oldInput['categories'] ?? [],
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? [],
        ];

        $types = Type::with('categories')->get();
        $submissionBgUrl = config('theme.submission_bg_url') ? Storage::url(config('theme.submission_bg_url')) : asset('images/submission-pattern.png');

        return view('products.create', compact(
            'displayData',
            'regularCategories',
            'bestForCategories',
            'pricingCategories',
            'allTechStacksData',
            'types',
            'submissionBgUrl'
        ));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'maker_links' => 'nullable|array',
            'maker_links.*' => 'url|max:2048',
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'x_account' => 'nullable|string|max:255',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'logo_url' => 'nullable|url|max:2048',
            'video_url' => 'nullable|string|max:2048',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        // Check if a product with this URL already exists
        $existingProduct = Product::where('link', $validated['link'])->first();
        if ($existingProduct) {
            return back()->withErrors(['link' => 'A product with this URL already exists. You cannot add the same product twice.'])->withInput();
        }

        $existsCheck = function ($slug) {
            return Product::where('slug', $slug)->exists();
        };

        $validated['slug'] = $this->slugService->generateUniqueSlug($validated['name'], $existsCheck);

        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $softwareType = Type::where('name', 'Software Categories')->with('categories')->first();
        $bestForType = Type::where('id', 3)->with('categories')->first();
        $selected = collect($request->input('categories', []))->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();
        $bestForIds = $bestForType ? $bestForType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }
        if ($bestForIds->count() && $selected->intersect($bestForIds)->isEmpty()) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Best for group.'])->withInput();
        }

        $validated['user_id'] = Auth::id();
        $validated['votes_count'] = 0;
        $validated['approved'] = false;
        $validated['description'] = $request->input('description');

        // Handle optional fields
        $validated['maker_links'] = $request->input('maker_links', []);
        $validated['sell_product'] = $request->boolean('sell_product', false);
        $validated['asking_price'] = $request->input('asking_price');
        $validated['x_account'] = $request->input('x_account');

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
                // $img = Image::make($image->getRealPath());
                // $encodedImage = $img->toWebp(80); // Convert to WebP with 80% quality
                // Storage::disk('public')->put($path . $filenameWithExtension, (string) $encodedImage);
                // $validated['logo'] = $path . $filenameWithExtension;
                $image->storePubliclyAs($path, $image->getClientOriginalName(), 'public');
                $validated['logo'] = $path . $image->getClientOriginalName();
            }
        } elseif ($request->filled('logo_url')) {
            $validated['logo'] = $validated['logo_url'];
        }
        unset($validated['logo_url']);

        $product = Product::create($validated);
        $product->categories()->sync($validated['categories']);
        if (isset($validated['tech_stacks'])) {
            $product->techStacks()->sync($validated['tech_stacks']);
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $path = $file->store('product_media', 'public');
                $product->media()->create([
                    'path' => $path,
                    'alt_text' => $product->name . ' media',
                    'type' => Str::startsWith($file->getMimeType(), 'image') ? 'image' : 'video',
                ]);
            }
        } else {
            FetchOgImage::dispatch($product);
        }

        $admins = User::getAdmins();
        Notification::send($admins, new ProductSubmitted($product));

        // Send notification to the user who submitted the product
        $user = User::find($product->user_id);
        if ($user) {
            $user->notify(new \App\Notifications\ProductSubmissionConfirmation($product));
        }

        // Return JSON response for API calls, redirect for regular form submissions
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Product submitted successfully',
                'product_id' => $product->id,
                'redirect_url' => route('products.submission.success', ['product' => $product->id])
            ]);
        }

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

        $allTechStacks = TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);

        $categoryTypes = json_decode(Storage::get('category_types.json'), true);
        $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
        $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
        $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

        $regularCategoryIds = DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
        $pricingCategoryIds = DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
        $bestForCategoryIds = DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

        $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get();
        $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get();
        $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get();

        $product->load(['categories', 'proposedCategories', 'techStacks']);

        $oldInput = session()->getOldInput();

        if ($product->approved && $product->has_pending_edits) {
            // When there are pending edits, use proposed values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'logo' => $product->proposed_logo_path ?? $product->logo,
                'tagline' => $product->proposed_tagline ?? $product->tagline,
                'product_page_tagline' => $oldInput['product_page_tagline'] ?? $product->product_page_tagline,
                'description' => $product->proposed_description ?? $product->description,
                'current_categories' => $product->proposedCategories->pluck('id')->toArray(),
                'current_tech_stacks' => $oldInput['tech_stacks'] ?? $product->techStacks->pluck('id')->toArray(),
                'maker_links' => $oldInput['maker_links'] ?? $product->maker_links,
                'sell_product' => $oldInput['sell_product'] ?? $product->sell_product,
                'asking_price' => $oldInput['asking_price'] ?? $product->asking_price,
                'x_account' => $oldInput['x_account'] ?? $product->x_account,
                'id' => $product->id,
            ];
        } else {
            // When no pending edits, use original values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'logo' => $product->logo,
                'tagline' => $oldInput['tagline'] ?? $product->tagline,
                'product_page_tagline' => $oldInput['product_page_tagline'] ?? $product->product_page_tagline,
                'description' => $oldInput['description'] ?? $product->description,
                'current_categories' => $oldInput['categories'] ?? $product->categories->pluck('id')->toArray(),
                'current_tech_stacks' => $oldInput['tech_stacks'] ?? $product->techStacks->pluck('id')->toArray(),
                'maker_links' => $oldInput['maker_links'] ?? $product->maker_links,
                'sell_product' => $oldInput['sell_product'] ?? $product->sell_product,
                'asking_price' => $oldInput['asking_price'] ?? $product->asking_price,
                'x_account' => $oldInput['x_account'] ?? $product->x_account,
                'id' => $product->id,
            ];
        }

        $types = Type::with('categories')->get();

        // Get the selected bestFor categories to pass to the JavaScript component
        $selectedBestForCategories = $product->categories()
            ->whereHas('types', function ($query) {
                $query->where('types.id', 3); // Best for type ID
            })
            ->pluck('categories.id')
            ->map(fn($id) => (string) $id)
            ->toArray();

        // Debug: Log the display data to see what's being passed
        \Log::info('Product edit displayData', [
            'product_id' => $product->id,
            'display_data' => $displayData,
            'selected_best_for_categories' => $selectedBestForCategories,
            'categories_count' => count($displayData['current_categories'] ?? [])
        ]);

        return view('products.create', compact(
            'product',
            'displayData',
            'regularCategories',
            'bestForCategories',
            'pricingCategories',
            'allTechStacksData',
            'types',
            'selectedBestForCategories'
        ));
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
            'video_url' => 'nullable|string|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
        ]);

        // Category validation (ensure at least one from each required type is selected)
        // This logic can be kept or adjusted based on whether proposed edits should also adhere to it.
        // For simplicity, we'll assume it applies.
        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $softwareType = Type::where('name', 'Software Categories')->with('categories')->first(); // Assuming this type name
        $bestForType = Type::where('id', 3)->with('categories')->first();
        $selected = collect($request->input('categories', []))->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();
        $bestForIds = $bestForType ? $bestForType->categories->pluck('id')->map(fn($id) => (int) $id) : collect();

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty()) {
            // Return JSON response for API calls
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one category from the Pricing group.',
                    'errors' => ['categories' => ['Please select at least one category from the Pricing group.']]
                ], 422);
            }
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty()) {
            // Return JSON response for API calls
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one category from the Software Categories group.',
                    'errors' => ['categories' => ['Please select at least one category from the Software Categories group.']]
                ], 422);
            }
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }
        if ($bestForIds->count() && $selected->intersect($bestForIds)->isEmpty()) {
            // Return JSON response for API calls
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one category from the Best for group.',
                    'errors' => ['categories' => ['Please select at least one category from the Best for group.']]
                ], 422);
            }
            return back()->withErrors(['categories' => 'Please select at least one category from the Best for group.'])->withInput();
        }

        // Prepare data for update
        $updateData = [
            'tagline' => $validated['tagline'],
            'product_page_tagline' => $validated['product_page_tagline'],
            'description' => $this->addNofollowToLinks($validated['description']),
            'video_url' => $validated['video_url'] ?? null,
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
                // $img = Image::make($image->getRealPath());
                // $encodedImage = $img->toWebp(80); // Convert to WebP with 80% quality
                // Storage::disk('public')->put($logoPath . $filenameWithExtension, (string) $encodedImage);
                // $logoPath .= $filenameWithExtension;
                $image->storePubliclyAs($logoPath, $image->getClientOriginalName(), 'public');
                $logoPath .= $image->getClientOriginalName();
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

            // Return JSON response for API calls
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Your proposed edits have been submitted for review.',
                    'product_id' => $product->id,
                    'redirect_url' => route('products.my')
                ]);
            }

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

            // Return JSON response for API calls
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully. It is awaiting approval.',
                    'product_id' => $product->id,
                    'redirect_url' => route('products.my')
                ]);
            }

            return redirect()->route('products.my')->with('success', 'Product updated successfully. It is awaiting approval.');
        }
    }

    public function checkUrl(Request $request)
    {
        $url = $request->input('url');
        $excludeId = $request->input('exclude_id');
        \Log::info('Checking URL for existence: ' . $url . ($excludeId ? ' (excluding ID: ' . $excludeId . ')' : ''));
        if (!$url) {
            \Log::info('URL is empty, returning false');
            return response()->json(['exists' => false]);
        }

        $query = Product::where('link', $url);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        $product = $query->first(); // Check all products, not just approved ones
        \Log::info('Product found for URL: ' . $url . ', exists: ' . ($product ? 'true' : 'false'));

        if ($product) {
            \Log::info('URL exists, product name: ' . $product->name);
            return response()->json([
                'exists' => true,
                'product' => [
                    'name' => $product->name,
                    'slug' => $product->slug,
                ],
            ]);
        }

        \Log::info('URL does not exist in database');
        return response()->json(['exists' => false]);
    }

    public function categoryProducts(Category $category)
    {
        // 1. Fetch Promoted Products (always shown, regardless of category filter for regular products)
        // Note: For category pages, you might decide if promoted products should *also* belong to the current category.
        // The current requirement is "immunity to filters", so we fetch all promoted products.
        // If they should be filtered by category, add ->whereHas('categories', fn($q) => $q->where('category_id', $category->id))
        $promotedProductsQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->approvedAndPublished()
            ->promoted()
            ->orderBy('promoted_position', 'asc');

        $promotedProducts = $promotedProductsQuery->get();

        // 2. Fetch Regular (non-promoted) Products for the current category
        $regularProductsQuery = $category->products()
            ->approvedAndPublished()
            ->nonPromoted()
            ->with([
                'categories.types',
                'user',
                'userUpvotes' => function ($query) {
                    if (Auth::check()) {
                        $query->where('user_id', Auth::id());
                    }
                }
            ]);

        $regularProducts = $regularProductsQuery->orderByRaw('(votes_count + impressions) DESC')->orderBy('name', 'asc')->get();

        // Alpine products mapping - based on all products for the modal.
        $allProducts = $promotedProducts->merge($regularProducts);

        // Fetch all types with their categories and product counts
        $allTypesCollection = Type::with([
            'categories' => function ($query) {
                $query->withCount('products')->orderByDesc('products_count')->orderBy('name');
            }
        ])->orderBy('name')->get();

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

        $categories = Category::withCount([
            'products' => function ($query) {
                $query->where('approved', true)
                    ->where('is_published', true);
            }
        ])->orderBy('name')->get();

        $currentYear = Carbon::now()->year;
        $title = "The Best " . strip_tags($category->name) . " Software Products of " . $currentYear;
        $meta_title = strip_tags($category->name) . ' - Software on the Web';
        $isCategoryPage = true;
        $metaDescription = $category->meta_description;

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
            'category',
            'categories',
            'types',
            'promotedProducts',
            'regularProducts',
            'premiumProducts',
            'headerAd',
            'sidebarTopAd',
            'belowProductListingAd',
            'belowProductListingAdPosition',
            'title',
            'isCategoryPage',
            'metaDescription',
            'meta_title',
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

        $baseRegularProductsQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->where(function ($query) use ($date) {
                $query->whereDate('published_at', $date->toDateString());
            })
            ->orderByRaw('(votes_count + impressions) DESC');

        $promotedProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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

        $regularProductsOnPageQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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
            $title = 'Top Products of the Day';
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

        $dayOfYear = $date->dayOfYear;
        $fullDate = $date->format('d F, Y');

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'activeDates', 'dayOfYear', 'fullDate', 'nextLaunchTime'));
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

    public function productsByWeek(Request $request, $year, $week, $isHomepage = false)
    {
        $now = Carbon::now();
        if (!$isHomepage && $year == $now->year && $week == $now->weekOfYear) {
            return redirect()->route('home');
        }

        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $baseRegularProductsQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderByRaw('(votes_count + impressions) DESC');

        $promotedProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', true)
            ->whereNotNull('promoted_position')
            ->where('is_published', true)
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $shuffledRegularProductIds = $this->getShuffledProductIds($baseRegularProductsQuery, 'week_' . $year . '_' . $week);

        // Only check for missing products if this is not called from the home page
        // This prevents double redirects when home() method already handled the redirect
        if ($shuffledRegularProductIds->isEmpty() && !$isHomepage) {
            // Check if there are promoted products for this week - if so, we don't need to redirect
            // Only redirect if there are no products at all (regular or promoted)
            $hasAnyProductsThisWeek = Product::where('approved', true)
                ->where('is_published', true)
                ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [
                    $startOfWeek->toDateString(),
                    $endOfWeek->toDateString()
                ])
                ->exists();

            if (!$hasAnyProductsThisWeek) {
                // Find the last available week with products when no products exist for the requested week
                $lastAvailableWeek = $this->findLastAvailableWeekWithProducts($startOfWeek);

                if ($lastAvailableWeek) {
                    $year = $lastAvailableWeek->year;
                    $week = $lastAvailableWeek->weekOfYear;

                    // Redirect to the last available week with products
                    return $this->productsByWeek($request, $year, $week, false);
                }
            }
        }

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

        $regularProductIdsOnPage = collect($finalProductOrder)->filter(fn($item) => is_numeric($item))->values();

        $regularProductsOnPageQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->whereIn('id', $regularProductIdsOnPage);

        if ($regularProductIdsOnPage->isNotEmpty()) {
            $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
            $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
        }
        $regularProductsOnPage = $regularProductsOnPageQuery->get();

        $combinedProducts = collect();
        foreach ($finalProductOrder as $item) {
            if (is_object($item)) {
                $combinedProducts->push($item);
            } else {
                $combinedProducts->push($regularProductsOnPage->firstWhere('id', $item));
            }
        }

        $regularProducts = $combinedProducts;

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfWeek->toDateString();
        $title = 'Top Products of the Week'; // For potential in-page display
        $pageTitle = 'Best of Week ' . $week . ' of ' . $year . ' | Software on the web'; // For <title> tag

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        $weekOfYear = $week;

        // For MySQL, we need to use mode 3 to get ISO 8601 week numbers (week starting Monday, week 1 has Jan 4)
        if (DB::connection()->getDriverName() === 'mysql') {
            $activeWeeks = Product::where('approved', true)
                ->where('is_published', true)
                ->selectRaw('DISTINCT CONCAT(YEAR(COALESCE(published_at, created_at)), "-", WEEK(COALESCE(published_at, created_at), 3)) as week')
                ->pluck('week')
                ->toArray();
        } else {
            $activeWeeks = Product::where('approved', true)
                ->where('is_published', true)
                ->selectRaw("DISTINCT strftime('%Y-%W', COALESCE(published_at, created_at)) as week")
                ->pluck('week')
                ->toArray();
        }

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'weekOfYear', 'year', 'activeWeeks', 'startOfWeek', 'endOfWeek'));
    }

    public function productsByMonth(Request $request, $year, $month)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $baseRegularProductsQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderByRaw('(votes_count + impressions) DESC');

        $promotedProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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

        $regularProductsOnPageQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime'));
    }

    public function productsByYear(Request $request, $year)
    {
        $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfYear = $startOfYear->copy()->endOfYear();

        $baseRegularProductsQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
            ->where('approved', true)
            ->where('is_promoted', false)
            ->where('is_published', true)
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfYear->toDateString(), $endOfYear->toDateString()])
            ->orderByRaw('(votes_count + impressions) DESC');

        $promotedProducts = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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

        $regularProductsOnPageQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])
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

        $now = Carbon::now('UTC');
        $nextLaunchTime = Carbon::today('UTC')->setHour(7);
        if ($nextLaunchTime->isPast()) {
            $nextLaunchTime->addDay();
        }
        $nextLaunchTime = $nextLaunchTime->toIso8601String();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime'));
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
            ->orderByRaw('(votes_count + impressions) DESC')
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

        $myProducts = Product::with(['categories', 'media', 'techStacks'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('products.my_products', [
            'products' => $myProducts,
            'perPage' => $perPage,
            'allowedPerPages' => $allowedPerPages,
            'allCategories' => Category::orderBy('name')->get(),
            'allTechStacks' => TechStack::orderBy('name')->get(),
        ]);
    }

    public function showProductPage(Product $product)
    {
        if (!$product->approved || !$product->is_published) {
            abort(404);
        }

        $product->load('categories.types', 'user', 'userUpvotes');

        // Record impression
        $product->increment('impressions');

        $pricingCategory = $product->categories->first(function ($category) {
            return $category->types->contains('name', 'Pricing');
        });

        $bestForCategories = $product->categories->filter(function ($category) {
            return $category->types->contains('name', 'Best for');
        });

        $categoryIds = $product->categories->pluck('id');

        $similarProducts = Product::where('id', '!=', $product->id)
            ->where('approved', true)
            ->where('is_published', true)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->orderByRaw('(votes_count + impressions) DESC')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        $title = $product->name;
        $pageTitle = $product->name . ': ' . $product->product_page_tagline . ' - Software on the Web';

        $description = strip_tags($product->description);
        $metaDescription = Str::limit($description, 160);

        $allCategories = Category::orderBy('name')->get();
        return view('products.show', compact('product', 'title', 'pageTitle', 'pricingCategory', 'similarProducts', 'metaDescription', 'bestForCategories', 'allCategories'));
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
            // First get products with combined score > 0 ordered by score, then randomize products with score = 0
            $orderedProducts = $query->orderByRaw('(votes_count + impressions) DESC')->get(['id', 'votes_count', 'impressions']);

            $highScoreIds = collect();
            $zeroScoreIds = collect();

            foreach ($orderedProducts as $product) {
                $combinedScore = $product->votes_count + $product->impressions;
                if ($combinedScore > 0) {
                    $highScoreIds->push($product->id);
                } else {
                    $zeroScoreIds->push($product->id);
                }
            }

            // Shuffle only the zero-score products to randomize them
            $shuffledZeroScoreIds = $zeroScoreIds->shuffle($seed);

            // Combine the high-score products (maintaining order) with shuffled zero-score products
            $result = $highScoreIds->concat($shuffledZeroScoreIds);

            return $result;
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

    /**
     * Find the last available week with products before the given date
     *
     * @param Carbon $startDate The date to start searching backwards from
     * @return Carbon|null The start of the week with products, or null if none found
     */
    private function findLastAvailableWeekWithProducts(Carbon $startDate)
    {
        $searchDate = $startDate->copy();
        $initialWeek = $searchDate->weekOfYear;
        $initialYear = $searchDate->year;
        \Log::info("findLastAvailableWeekWithProducts: Starting search from week {$initialWeek} of year {$initialYear} (Date: {$searchDate->toDateString()})");

        // Search backwards up to 52 weeks (1 year) to find a week with products
        for ($i = 0; $i < 52; $i++) {
            $startOfWeek = $searchDate->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);
            $currentWeek = $startOfWeek->weekOfYear;
            $currentYear = $startOfWeek->year;

            \Log::info("findLastAvailableWeekWithProducts: Checking week {$currentWeek} of year {$currentYear} (Date range: {$startOfWeek->toDateString()} to {$endOfWeek->toDateString()})");

            // Check if there are any products in this week (promoted or non-promoted)
            $hasProducts = Product::where('approved', true)
                ->where('is_published', true)
                ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [
                    $startOfWeek->toDateString(),
                    $endOfWeek->toDateString()
                ])
                ->exists();

            if ($hasProducts) {
                \Log::info("findLastAvailableWeekWithProducts: Found products in week {$currentWeek} of year {$currentYear}");
                return $startOfWeek;
            }

            // Move to the previous week
            $searchDate = $searchDate->subWeek();
        }

        \Log::info("findLastAvailableWeekWithProducts: No weeks with products found after searching 52 weeks");
        // If no week with products was found, return null
        return null;
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


            // Category classification has been removed as part of AI functionality removal
            // $categoryNames = array_keys($this->categoryClassifier->classify($html));
            // $categoryIds = \App\Models\Category::whereIn('name', $categoryNames)->pluck('id')->toArray();
            // Log::info('Classified Categories:', ['url' => $url, 'categories' => $categoryIds]);
            $categoryIds = [];

            $techStackNames = $this->techStackDetector->detect($url);
            $techStackIds = \App\Models\TechStack::whereIn('name', $techStackNames)->pluck('id')->toArray();
            Log::info('Detected Tech Stacks:', ['url' => $url, 'tech_stacks' => $techStackIds]);

            return response()->json([
                'title' => trim($title),
                'description' => trim($description),
                'og_image' => $ogImage,
                'logos' => array_values($logos),
                'og_images' => array_values(array_unique($ogImages)),
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
    public function fetchProductData(Request $request)
    {
        $url = $request->input('url');

        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $response = Http::get($url);
            $html = $response->body();

            $doc = new DOMDocument();
            @$doc->loadHTML($html);

            $titleNode = $doc->getElementsByTagName('title')->item(0);
            $title = $titleNode ? $titleNode->nodeValue : '';

            $description = '';
            $metas = $doc->getElementsByTagName('meta');
            for ($i = 0; $i < $metas->length; $i++) {
                $meta = $metas->item($i);
                if (strtolower($meta->getAttribute('name')) == 'description') {
                    $description = $meta->getAttribute('content');
                }
            }

            return response()->json([
                'name' => trim($title),
                'tagline' => trim($description),
                'product_page_tagline' => trim($description),
                'description' => '',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data.'], 500);
        }
    }
    public function fetchMetadata(Request $request)
    {
        $url = $request->input('url');

        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $response = Http::get($url);
            $html = $response->body();

            $doc = new DOMDocument();
            @$doc->loadHTML($html);

            $titleNode = $doc->getElementsByTagName('title')->item(0);
            $title = $titleNode ? $titleNode->nodeValue : '';

            $description = '';
            $metas = $doc->getElementsByTagName('meta');
            for ($i = 0; $i < $metas->length; $i++) {
                $meta = $metas->item($i);
                if (strtolower($meta->getAttribute('name')) == 'description') {
                    $description = $meta->getAttribute('content');
                }

            }

            $faviconUrl = 'https://www.google.com/s2/favicons?sz=64&domain_url=' . urlencode($url);

            return response()->json([
                'name' => $this->nameExtractor->extract(trim($title)),
                'tagline' => trim($description),
                'description' => '',
                'favicon' => $faviconUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch data.'], 500);
        }
    }

    public function getCategories()
    {
        try {
            $categoryTypes = json_decode(Storage::disk('local')->get('category_types.json'), true);
            if (!$categoryTypes) {
                // Fallback or error if the JSON file is missing or invalid
                $categoryTypes = [
                    ['type_id' => 1, 'type_name' => 'Category'],
                    ['type_id' => 2, 'type_name' => 'Pricing'],
                    ['type_id' => 3, 'type_name' => 'Best for'],
                ];
            }

            $categoryTypeId = collect($categoryTypes)->firstWhere('type_name', 'Category')['type_id'] ?? 1;
            $pricingTypeId = collect($categoryTypes)->firstWhere('type_name', 'Pricing')['type_id'] ?? 2;
            $bestForTypeId = collect($categoryTypes)->firstWhere('type_name', 'Best for')['type_id'] ?? 3;

            $regularCategoryIds = DB::table('category_types')->where('type_id', $categoryTypeId)->pluck('category_id');
            $pricingCategoryIds = DB::table('category_types')->where('type_id', $pricingTypeId)->pluck('category_id');
            $bestForCategoryIds = DB::table('category_types')->where('type_id', $bestForTypeId)->pluck('category_id');

            $regularCategories = Category::whereIn('id', $regularCategoryIds)->orderBy('name')->get(['id', 'name']);
            $pricingCategories = Category::whereIn('id', $pricingCategoryIds)->orderBy('name')->get(['id', 'name']);
            $bestForCategories = Category::whereIn('id', $bestForCategoryIds)->orderBy('name')->get(['id', 'name']);

            return response()->json([
                'categories' => $regularCategories,
                'bestFor' => $bestForCategories,
                'pricing' => $pricingCategories,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories for API: ' . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve categories.'], 500);
        }
    }

    public function phpinfo()
    {
        phpinfo();
    }
    public function fetchInitialMetadata(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        $metadataResponse = $this->fetchMetadata($request);
        if ($metadataResponse->getStatusCode() !== 200) {
            return $metadataResponse;
        }
        $metadata = json_decode($metadataResponse->getContent(), true);

        // Extract additional information from the URL content
        $taglineDetailed = '';
        try {
            $response = Http::get($url);
            $html = $response->body();

            $doc = new DOMDocument();
            @$doc->loadHTML($html);

            // Extract potential detailed tagline from h1, h2, h3 tags or specific classes
            $potentialTaglines = [];
            $headings = $doc->getElementsByTagName('h1');
            foreach ($headings as $heading) {
                $potentialTaglines[] = $heading->nodeValue;
            }
            $headings = $doc->getElementsByTagName('h2');
            foreach ($headings as $heading) {
                $potentialTaglines[] = $heading->nodeValue;
            }
            $headings = $doc->getElementsByTagName('h3');
            foreach ($headings as $heading) {
                $potentialTaglines[] = $heading->nodeValue;
            }

            // Look for specific classes that might contain taglines
            $xpath = new \DOMXPath($doc);
            $elements = $xpath->query("//div[contains(@class, 'tagline') or contains(@class, 'slogan') or contains(@class, 'subtitle') or contains(@class, 'description') or contains(@class, 'headline')]");
            foreach ($elements as $element) {
                $potentialTaglines[] = $element->nodeValue;
            }

            // Use the first potential tagline that's not empty and not too long
            foreach ($potentialTaglines as $potential) {
                $potential = trim($potential);
                if (!empty($potential) && strlen($potential) < 200) {
                    $taglineDetailed = $potential;
                    break;
                }
            }

            // Fallback to the meta description if no detailed tagline was found
            if (empty($taglineDetailed)) {
                $taglineDetailed = $metadata['tagline'] ?? '';
            }

            // Limit the length of the detailed tagline
            $taglineDetailed = Str::limit($taglineDetailed, 160, '...');
        } catch (\Exception $e) {
            Log::error('Error extracting detailed tagline from URL: ' . $e->getMessage(), ['url' => $url]);
            // Use the regular tagline as fallback
            $taglineDetailed = $metadata['tagline'] ?? '';
        }

        $responseData = [
            'name' => $metadata['name'],
            'tagline' => $metadata['tagline'],
            'tagline_detailed' => $taglineDetailed,
            'favicon' => $metadata['favicon'],
        ];

        Log::info('Fetched initial metadata', ['url' => $url, 'data' => $responseData]);
        return response()->json($responseData);
    }

    public function processUrl(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        $name = $request->input('name');
        $tagline = $request->input('tagline');

        $fetchContent = $request->input('fetch_content', true);

        $description = '';
        $extractedTagline = '';
        $extractedTaglineDetailed = '';

        try {
            // 3. Fetch HTML for content extraction with timeout
            $htmlResponse = Http::timeout(15)->get($url);
            if (!$htmlResponse->successful()) {
                return response()->json([
                    'description' => $description,
                    'logos' => [],
                    'tagline' => $tagline,
                    'tagline_detailed' => '',
                    'categories' => [],
                    'bestFor' => [],
                    'pricing' => [],
                ]);
            }
            $htmlContent = $htmlResponse->body();

            if ($fetchContent) {
                // Extract content from HTML
                $doc = new DOMDocument();
                @$doc->loadHTML($htmlContent);

                // Extract title
                $titleNode = $doc->getElementsByTagName('title')->item(0);
                $title = $titleNode ? $titleNode->nodeValue : '';

                // Extract meta description
                $descriptionContent = '';
                $metas = $doc->getElementsByTagName('meta');
                for ($i = 0; $i < $metas->length; $i++) {
                    $meta = $metas->item($i);
                    if (strtolower($meta->getAttribute('name')) == 'description') {
                        $descriptionContent = $meta->getAttribute('content');
                        break;
                    }
                }

                // Extract potential taglines from h1, h2, h3 tags or specific classes
                $potentialTaglines = [];
                $headings = $doc->getElementsByTagName('h1');
                foreach ($headings as $heading) {
                    $potentialTaglines[] = $heading->nodeValue;
                }
                $headings = $doc->getElementsByTagName('h2');
                foreach ($headings as $heading) {
                    $potentialTaglines[] = $heading->nodeValue;
                }
                $headings = $doc->getElementsByTagName('h3');
                foreach ($headings as $heading) {
                    $potentialTaglines[] = $heading->nodeValue;
                }

                // Look for specific classes that might contain taglines
                $xpath = new \DOMXPath($doc);
                $elements = $xpath->query("//div[contains(@class, 'tagline') or contains(@class, 'slogan') or contains(@class, 'subtitle') or contains(@class, 'description') or contains(@class, 'headline')]");
                foreach ($elements as $element) {
                    $potentialTaglines[] = $element->nodeValue;
                }

                // Use the extracted content
                $extractedTagline = $descriptionContent ?: trim($title);
                $extractedTaglineDetailed = trim($title) ?: $descriptionContent;
                $description = $descriptionContent;

                // If we still don't have a good tagline, try from potential taglines
                if (empty($extractedTagline) && !empty($potentialTaglines)) {
                    foreach ($potentialTaglines as $potential) {
                        $potential = trim($potential);
                        if (strlen($potential) > 0 && strlen($potential) < 120) { // Reasonable tagline length
                            $extractedTagline = $potential;
                            break;
                        }
                    }
                }

                // If we still don't have a detailed tagline, try from potential taglines
                if (empty($extractedTaglineDetailed) && !empty($potentialTaglines)) {
                    foreach ($potentialTaglines as $potential) {
                        $potential = trim($potential);
                        if (strlen($potential) > 0 && strlen($potential) < 200) { // Reasonable detailed tagline length
                            $extractedTaglineDetailed = $potential;
                            break;
                        }
                    }
                }

                // Fallback to the first non-empty potential tagline if still empty
                if (empty($extractedTagline) && !empty($potentialTaglines)) {
                    foreach ($potentialTaglines as $potential) {
                        $potential = trim($potential);
                        if (!empty($potential)) {
                            $extractedTagline = $potential;
                            break;
                        }
                    }
                }

                if (empty($extractedTaglineDetailed) && !empty($potentialTaglines)) {
                    foreach ($potentialTaglines as $potential) {
                        $potential = trim($potential);
                        if (!empty($potential)) {
                            $extractedTaglineDetailed = $potential;
                            break;
                        }
                    }
                }

                // Limit the length of taglines to fit form requirements
                $extractedTagline = Str::limit($extractedTagline, 60, '...');
                $extractedTaglineDetailed = Str::limit($extractedTaglineDetailed, 160, '...');

                // If we have a name and tagline is still empty, try to enhance it
                if (empty($extractedTagline) && !empty($name)) {
                    $extractedTagline = $name;
                }
            }

            // Extract Logos
            $logos = $this->logoExtractor->extract($url, $htmlContent);

            // Classify categories and bestFor from the HTML content
            $classificationResult = $this->categoryClassifier->classify($htmlContent);
            $categories = $classificationResult['categories'] ?? [];
            $bestFor = $classificationResult['best_for'] ?? [];
            $pricing = $classificationResult['pricing'] ?? [];

            // Convert category names to IDs
            $categoryIds = [];
            if (!empty($categories)) {
                $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();
            }

            $bestForIds = [];
            if (!empty($bestFor)) {
                $bestForIds = Category::whereIn('name', $bestFor)->pluck('id')->toArray();
            }

            $pricingIds = [];
            if (!empty($pricing)) {
                $pricingIds = Category::whereIn('name', $pricing)->pluck('id')->toArray();
            }

            $responseData = [
                'description' => $description,
                'logos' => $logos,
                'tagline' => $extractedTagline ?: $tagline, // Use extracted tagline, fallback to provided tagline
                'tagline_detailed' => $extractedTaglineDetailed, // Use extracted detailed tagline
                'categories' => $categoryIds,
                'bestFor' => $bestForIds,
                'pricing' => $pricingIds,
            ];

            Log::info('Fetched remaining data', ['url' => $url, 'data' => $responseData]);
            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Error in processUrl: ' . $e->getMessage(), [
                'url' => $url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return a response with empty logos but maintain the structure to prevent frontend errors
            return response()->json([
                'description' => $description,
                'logos' => [],
                'tagline' => $tagline,
                'tagline_detailed' => '',
                'categories' => [],
                'bestFor' => [],
                'pricing' => [],
            ]);
        }
    }


    public function getTechStacks()
    {
        try {
            $techStacks = TechStack::orderBy('name')->get(['id', 'name']);
            return response()->json($techStacks);
        } catch (\Exception $e) {
            Log::error('Failed to fetch tech stacks for API: ' . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve tech stacks.'], 500);
        }
    }

    public function upvote(Request $request, Product $product)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'You must be logged in to upvote products.'
            ], 401);
        }

        $user = Auth::user();

        // Check if user has already upvoted this product
        $existingUpvote = UserProductUpvote::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existingUpvote) {
            // Remove the upvote (toggle off)
            $existingUpvote->delete();
            $product->decrement('votes_count');
            $isUpvoted = false;
        } else {
            // Add the upvote (toggle on)
            UserProductUpvote::create([
                'user_id' => $user->id,
                'product_id' => $product->id
            ]);
            $product->increment('votes_count');
            $isUpvoted = true;
        }

        return response()->json([
            'is_upvoted' => $isUpvoted,
            'votes_count' => $product->fresh()->votes_count
        ]);
    }

}
