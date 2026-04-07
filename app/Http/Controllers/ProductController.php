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
use App\Services\DescriptionRewriterService;
use App\Services\ScreenshotService;
use App\Services\BadgeService;
use App\Jobs\FetchOgImage;
use DOMDocument;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    protected FaviconExtractorService $faviconExtractor;
    protected SlugService $slugService;
    protected TechStackDetectorService $techStackDetector;
    protected NameExtractorService $nameExtractor;
    protected LogoExtractorService $logoExtractor;
    protected \App\Services\CategoryClassifier $categoryClassifier;
    protected ScreenshotService $screenshotService;
    protected BadgeService $badgeService;

    public function __construct(FaviconExtractorService $faviconExtractor, SlugService $slugService, TechStackDetectorService $techStackDetector, NameExtractorService $nameExtractor, LogoExtractorService $logoExtractor, \App\Services\CategoryClassifier $categoryClassifier, ScreenshotService $screenshotService, BadgeService $badgeService)
    {
        $this->faviconExtractor = $faviconExtractor;
        $this->slugService = $slugService;
        $this->techStackDetector = $techStackDetector;
        $this->nameExtractor = $nameExtractor;
        $this->logoExtractor = $logoExtractor;
        $this->categoryClassifier = $categoryClassifier;
        $this->screenshotService = $screenshotService;
        $this->badgeService = $badgeService;
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
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
            'categories' => [
                function ($attribute, $value, $fail) use ($request) {
                    $hasExisting = is_array($value) && count($value) > 0;
                    $hasCustom = $request->has('custom_categories') && is_array($request->input('custom_categories')) && count($request->input('custom_categories')) > 0;

                    if (!$hasExisting && !$hasCustom) {
                        $fail('The categories field is required.');
                    }
                },
            ],
            'categories.*' => 'nullable|exists:categories,id',
            'custom_categories' => 'nullable|array|max:3',
            'custom_categories.*.name' => 'required|string|max:100',
            'custom_categories.*.type' => 'required|in:category,best_for',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048',
            'logo_url' => 'nullable|string', // Relaxed for base64 support
            'video_url' => 'nullable|string|max:2048',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
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
        $submittedCategories = is_array($request->input('categories')) ? $request->input('categories') : [];
        $selected = collect($submittedCategories)->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id') : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id') : collect();
        $bestForIds = $bestForType ? $bestForType->categories->pluck('id') : collect();

        $customCategories = $request->input('custom_categories', []);
        $hasCustomPricing = collect($customCategories)->contains('type', 'pricing'); // Note: Assuming users don't submit custom pricing types, but theoretically could
        $hasCustomSoftware = collect($customCategories)->contains('type', 'category');
        $hasCustomBestFor = collect($customCategories)->contains('type', 'best_for');

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty() && !$hasCustomPricing) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty() && !$hasCustomSoftware) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }
        // bestFor is optional — no validation needed

        $validated['user_id'] = Auth::id();
        $validated['votes_count'] = 0;

        // Handle submission type: 'badge' submissions get instant approval
        $submissionType = $request->input('submission_type', 'free');
        $validated['submission_type'] = in_array($submissionType, ['free', 'badge']) ? $submissionType : 'free';

        if ($submissionType === 'badge') {
            $launchDate = $this->badgeService->getNextMondayLaunchDate();
            $validated['approved'] = true;
            $validated['is_published'] = false; // Scheduled, not instant
            $validated['published_at'] = $launchDate;
            $validated['badge_placement_url'] = $request->input('badge_placement_url') ?: $request->input('link');
        } else {
            $validated['approved'] = false;
        }
        $validated['description'] = $this->ensureProperParagraphStructure($this->addNofollowToLinks($request->input('description')));

        // Handle optional fields
        $validated['maker_links'] = $request->input('maker_links', []);
        $validated['sell_product'] = $request->boolean('sell_product', false);
        $validated['asking_price'] = $request->input('asking_price');

        $xAccount = $request->input('x_account');
        if ($xAccount && (str_contains($xAccount, 'x.com/') || str_contains($xAccount, 'twitter.com/'))) {
            $xAccount = basename(parse_url($xAccount, PHP_URL_PATH));
        }
        $validated['x_account'] = $xAccount;

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
            $logoUrl = $request->input('logo_url');

            // Handle Base64/Data URL
            if (Str::startsWith($logoUrl, 'data:image')) {
                try {
                    // Extract extension
                    $extension = 'png';
                    if (preg_match('/^data:image\/([\w\+\-\.]+);base64,/', $logoUrl, $matches)) {
                        $extension = $matches[1];
                        if ($extension === 'svg+xml') {
                            $extension = 'svg';
                        }
                    }

                    // Decode data
                    $base64Data = preg_replace('/^data:image\/[\w\+\-\.]+;base64,/', '', $logoUrl);
                    $decodedData = base64_decode($base64Data);

                    if ($decodedData) {
                        $filename = Str::uuid() . '.' . $extension;
                        $path = 'logos/' . $filename;
                        Storage::disk('public')->put($path, $decodedData);
                        $validated['logo'] = $path;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save base64 logo: ' . $e->getMessage());
                    // Fallback to URL if saving fails (though it might still fail validation later)
                    $validated['logo'] = $logoUrl;
                }
            } else {
                $validated['logo'] = $logoUrl;
            }
        }
        unset($validated['logo_url']);

        $product = Product::create($validated);
        $product->categories()->sync($validated['categories']);
        if (isset($validated['tech_stacks'])) {
            $product->techStacks()->sync($validated['tech_stacks']);
        }

        // Handle custom categories if any
        if ($request->has('custom_categories')) {
            foreach ($request->input('custom_categories') as $customCategory) {
                \App\Models\CustomCategorySubmission::create([
                    'product_id' => $product->id,
                    'type' => $customCategory['type'],
                    'name' => $customCategory['name'],
                    'status' => 'pending'
                ]);
            }
        }

        // Handle custom tech stacks if any
        if ($request->has('custom_tech_stacks')) {
            foreach ($request->input('custom_tech_stacks') as $customTechStack) {
                \App\Models\CustomCategorySubmission::create([
                    'product_id' => $product->id,
                    'type' => 'tech_stack',
                    'name' => $customTechStack['name'],
                    'status' => 'pending'
                ]);
            }
        }

        if ($request->hasFile('media')) {
            $manager = new ImageManager(new Driver());

            foreach ($request->file('media') as $file) {
                $this->processMediaItem($product, $file, $manager);
            }
        }

        if ($request->filled('media_urls')) {
            $manager = new ImageManager(new Driver());
            foreach ($request->input('media_urls') as $url) {
                if ($url) {
                    try {
                        // Check if this is a local screenshot URL (already on disk)
                        $appUrl = config('app.url');
                        $isLocal = str_starts_with($url, $appUrl . '/storage/') || str_contains($url, '/storage/screenshots/');

                        if ($isLocal) {
                            // Extract the relative storage path from the URL
                            // URL format: https://domain.com/storage/screenshots/filename.jpg
                            $storagePath = preg_replace('#^.*?/storage/#', '', $url);

                            if (Storage::disk('public')->exists($storagePath)) {
                                // Copy directly from disk — no HTTP request needed
                                $extension = pathinfo($storagePath, PATHINFO_EXTENSION) ?: 'jpg';
                                $filename = Str::uuid() . '.' . $extension;
                                $newPath = 'product_media/' . $filename;
                                Storage::disk('public')->copy($storagePath, $newPath);
                                $this->processMediaItem($product, Storage::disk('public')->path($newPath), $manager, true);
                                continue;
                            }
                        }

                        // Fallback: download from external URL
                        $imageContents = Http::get($url)->body();
                        $extension = 'jpg';
                        if (str_contains($url, '.png'))
                            $extension = 'png';
                        if (str_contains($url, '.webp'))
                            $extension = 'webp';

                        $filename = Str::uuid() . '.' . $extension;
                        $path = 'product_media/' . $filename;
                        Storage::disk('public')->put($path, $imageContents);

                        $this->processMediaItem($product, Storage::disk('public')->path($path), $manager, true);
                    } catch (\Exception $e) {
                        Log::error('Failed to process media from URL: ' . $url . ' - ' . $e->getMessage());
                    }
                }
            }
        }

        if (!$request->hasFile('media') && !$request->filled('media_urls')) {
            FetchOgImage::dispatch($product);
        }

        $admins = User::getAdmins();
        Notification::send($admins, new ProductSubmitted($product));

        // Send notification to the user who submitted the product
        $user = User::find($product->user_id);
        if ($user) {
            $user->notify(new \App\Notifications\ProductSubmissionConfirmation($product));
        }

        // For badge submissions, dispatch verification job on launch day (2 hours after publish time)
        if ($validated['submission_type'] === 'badge') {
            $verifyAt = $product->published_at->copy()->addHours(2);
            \App\Jobs\VerifyBadgePlacement::dispatch($product)->delay($verifyAt);
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
        $totalSpots = $settings['premium_product_spots'] ?? 6;
        $spotsTaken = PremiumProduct::where('expires_at', '>', now())->count();
        $spotsAvailable = $totalSpots - $spotsTaken;

        // Badge-specific data for the success page
        $badgeSnippet = null;
        $launchDate = null;
        $launchDateFormatted = null;

        if ($product->submission_type === 'badge') {
            $badgeSnippet = $this->badgeService->generateSnippet($product);
            $launchDate = $product->published_at;
            $launchDateFormatted = $this->badgeService->getLaunchDateFormatted($product->published_at);
        }

        return view('products.submission_success', compact(
            'product',
            'daysToLive',
            'spotsAvailable',
            'badgeSnippet',
            'launchDate',
            'launchDateFormatted'
        ));
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

        $product->load(['categories', 'proposedCategories', 'techStacks', 'media']);

        $oldInput = session()->getOldInput();

        if ($product->approved && $product->has_pending_edits) {
            // When there are pending edits, use proposed values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'logo' => $product->proposed_logo_path ?? $product->logo,
                'logo_url' => ($product->proposed_logo_path ? \Illuminate\Support\Facades\Storage::url($product->proposed_logo_path) : $product->logo_url),
                'tagline' => $product->proposed_tagline ?? $product->tagline,
                'product_page_tagline' => $oldInput['product_page_tagline'] ?? $product->proposed_product_page_tagline ?? $product->product_page_tagline,
                'description' => $product->proposed_description ?? $product->description,
                'current_categories' => $product->proposedCategories->pluck('id')->toArray(),
                'current_tech_stacks' => $oldInput['tech_stacks'] ?? $product->techStacks->pluck('id')->toArray(),
                'maker_links' => $oldInput['maker_links'] ?? $product->maker_links,
                'sell_product' => $oldInput['sell_product'] ?? $product->sell_product,
                'asking_price' => $oldInput['asking_price'] ?? $product->asking_price,
                'x_account' => $oldInput['x_account'] ?? $product->x_account,
                'id' => $product->id,
                'logos' => $product->media->where('type', 'image')->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
                'gallery' => $product->media->where('type', 'image')->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
            ];
        } else {
            // When no pending edits, use original values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'logo' => $product->logo,
                'logo_url' => $product->logo_url,
                'tagline' => $oldInput['tagline'] ?? $product->tagline,
                'product_page_tagline' => $oldInput['product_page_tagline'] ?? $product->product_page_tagline,
                'description' => $oldInput['description'] ?? $product->description,
                'current_categories' => $oldInput['categories'] ?? $product->categories->pluck('id')->toArray(),
                'current_tech_stacks' => old('tech_stacks', $product->techStacks->pluck('id')->toArray()),
                'maker_links' => $oldInput['maker_links'] ?? $product->maker_links,
                'sell_product' => $oldInput['sell_product'] ?? $product->sell_product,
                'asking_price' => $oldInput['asking_price'] ?? $product->asking_price,
                'x_account' => $oldInput['x_account'] ?? $product->x_account,
                'id' => $product->id,
                'logos' => $product->media->where('type', 'image')->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
                'gallery' => $product->media->where('type', 'image')->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
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
            'tagline' => 'required|string|max:140', // Max 140 chars
            'product_page_tagline' => 'required|string|max:255',
            'description' => 'required|string|max:5000', // Max 5000 chars
            // 'link' is not editable by users directly in this form
            'categories' => [
                function ($attribute, $value, $fail) use ($request) {
                    $hasExisting = is_array($value) && count($value) > 0;
                    $hasCustom = $request->has('custom_categories') && is_array($request->input('custom_categories')) && count($request->input('custom_categories')) > 0;

                    if (!$hasExisting && !$hasCustom) {
                        $fail('The categories field is required.');
                    }
                },
            ],
            'categories.*' => 'nullable|exists:categories,id',
            'custom_categories' => 'nullable|array|max:3',
            'custom_categories.*.name' => 'required|string|max:100',
            'custom_categories.*.type' => 'required|in:category,best_for',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:2048', // File upload for logo
            'remove_logo' => 'nullable|boolean', // For removing existing logo
            'video_url' => 'nullable|string|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
            'maker_links' => 'nullable|array',
            'maker_links.*' => 'url|max:2048',
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
        ]);

        // Category validation (ensure at least one from each required type is selected)
        // This logic can be kept or adjusted based on whether proposed edits should also adhere to it.
        // For simplicity, we'll assume it applies.
        $pricingType = Type::where('name', 'Pricing')->with('categories')->first();
        $softwareType = Type::where('name', 'Software Categories')->with('categories')->first(); // Assuming this type name
        $bestForType = Type::where('id', 3)->with('categories')->first();
        $submittedCategories = is_array($request->input('categories')) ? $request->input('categories') : [];
        $selected = collect($submittedCategories)->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id') : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id') : collect();
        $bestForIds = $bestForType ? $bestForType->categories->pluck('id') : collect();

        $customCategories = $request->input('custom_categories', []);
        $hasCustomPricing = collect($customCategories)->contains('type', 'pricing');
        $hasCustomSoftware = collect($customCategories)->contains('type', 'category');
        $hasCustomBestFor = collect($customCategories)->contains('type', 'best_for');

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty() && !$hasCustomPricing) {
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
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty() && !$hasCustomSoftware) {
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
        // bestFor is optional — no validation needed

        // Prepare data for update
        $updateData = [
            'tagline' => $validated['tagline'],
            'product_page_tagline' => $validated['product_page_tagline'],
            'description' => $this->ensureProperParagraphStructure($this->addNofollowToLinks($validated['description'])),
            'video_url' => $validated['video_url'] ?? null,
            'maker_links' => $validated['maker_links'] ?? [],
            'sell_product' => $validated['sell_product'] ?? false,
            'asking_price' => $validated['asking_price'] ?? null,
        ];

        $xAccount = $validated['x_account'] ?? null;
        if ($xAccount && (str_contains($xAccount, 'x.com/') || str_contains($xAccount, 'twitter.com/'))) {
            $xAccount = basename(parse_url($xAccount, PHP_URL_PATH));
        }
        $updateData['x_account'] = $xAccount;

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

        $product->last_edited_by_id = Auth::id();

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
            $product->proposed_product_page_tagline = $updateData['product_page_tagline'];
            $product->proposed_description = $this->ensureProperParagraphStructure($updateData['description']);
            $product->proposed_video_url = $updateData['video_url'] ?? null;
            $product->proposedCategories()->sync($newCategories);
            $product->proposedTechStacks()->sync($newTechStacks);
            $product->has_pending_edits = true;
            $product->save();

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
            $product->proposed_name = null;
            $product->proposed_link = null;
            $product->proposed_video_url = null;
            $product->proposed_x_account = null;
            $product->proposed_sell_product = null;
            $product->proposed_asking_price = null;
            $product->proposed_maker_links = null;
            $product->proposed_product_page_tagline = null;
            $product->proposedCategories()->detach();
            $product->proposedTechStacks()->detach();
            $product->has_pending_edits = false;

            // Update main product fields
            $product->update($updateData);
            $product->categories()->sync($newCategories);
            $product->techStacks()->sync($newTechStacks);

            // Handle custom categories if any
            if ($request->has('custom_categories')) {
                foreach ($request->input('custom_categories') as $customCategory) {
                    \App\Models\CustomCategorySubmission::create([
                        'product_id' => $product->id,
                        'type' => $customCategory['type'],
                        'name' => $customCategory['name'],
                        'status' => 'pending'
                    ]);
                }
            }

            // Handle custom tech stacks if any
            if ($request->has('custom_tech_stacks')) {
                foreach ($request->input('custom_tech_stacks') as $customTechStack) {
                    \App\Models\CustomCategorySubmission::create([
                        'product_id' => $product->id,
                        'type' => 'tech_stack',
                        'name' => $customTechStack['name'],
                        'status' => 'pending'
                    ]);
                }
            }

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
        $title = "The Best " . strip_tags($category->name) . " Apps of " . $currentYear;
        $meta_title = "Best " . strip_tags($category->name) . " Tools & Software (" . $currentYear . ") | Software on the Web";
        $isCategoryPage = true;
        $meta_description = $category->meta_description ?: $category->description;

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
            'meta_description',
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

        $now = Carbon::now();
        if ($date->year > $now->year + 1) {
            abort(404);
        }

        $isFuture = $date->isFuture();

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

        $shuffledRegularProductIds = $isFuture ? collect() : $this->getShuffledProductIds($baseRegularProductsQuery, 'date_' . $date->toDateString());

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

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'activeDates', 'dayOfYear', 'fullDate', 'nextLaunchTime', 'isFuture'));
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
        if ($year > $now->year + 1) {
            abort(404);
        }

        if (!$isHomepage && $year == $now->year && $week == $now->weekOfYear) {
            return redirect()->route('home');
        }

        $startOfWeek = Carbon::now()->setISODate($year, $week)->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SUNDAY);

        $isFuture = $startOfWeek->isFuture();

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

        $shuffledRegularProductIds = $isFuture ? collect() : $this->getShuffledProductIds($baseRegularProductsQuery, 'week_' . $year . '_' . $week);

        // Only check for missing products if this is not called from the home page
        // This prevents double redirects when home() method already handled the redirect
        if ($shuffledRegularProductIds->isEmpty() && !$isHomepage && !$isFuture) {
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

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'weekOfYear', 'year', 'activeWeeks', 'startOfWeek', 'endOfWeek', 'isFuture'));
    }

    public function productsByMonth(Request $request, $year, $month)
    {
        $now = Carbon::now();
        if ($year > $now->year + 1) {
            abort(404);
        }

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $isFuture = $startOfMonth->isFuture();

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

        $shuffledRegularProductIds = $isFuture ? collect() : $this->getShuffledProductIds($baseRegularProductsQuery, 'month_' . $year . '_' . $month);

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

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'isFuture'));
    }

    public function productsByYear(Request $request, $year)
    {
        $now = Carbon::now();
        if ($year > $now->year + 1) {
            abort(404);
        }

        $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
        $endOfYear = $startOfYear->copy()->endOfYear();

        $isFuture = $startOfYear->isFuture();

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

        $shuffledRegularProductIds = $isFuture ? collect() : $this->getShuffledProductIds($baseRegularProductsQuery, 'year_' . $year);

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

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'isFuture'));
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

        $product->load('categories.types', 'user', 'userUpvotes', 'techStacks');

        // Record impression
        $product->increment('impressions');

        $pricingCategory = $product->categories->first(function ($category) {
            return $category->types->contains('name', 'Pricing');
        });

        $bestForCategories = $product->categories->filter(function ($category) {
            return $category->types->contains('name', 'Best for');
        });

        // Only use functional categories for similarity matching.
        // Exclude meta-category types like "Pricing" (Free, Paid…) and "Best for"
        // so that products are compared by what they *do*, not by their pricing model.
        $categoryIds = $product->categories
            ->filter(fn($cat) => $cat->types->doesntContain('name', 'Pricing')
                && $cat->types->doesntContain('name', 'Best for'))
            ->pluck('id');

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
    // Ensure content has proper paragraph structure
    public function ensureProperParagraphStructure($html)
    {
        if (empty($html)) {
            return $html;
        }

        // Check if content has any block-level elements
        // If not, wrap the content in paragraph tags
        if (!preg_match('/<(p|div|h[1-6]|ul|ol|blockquote|pre|hr)/i', $html)) {
            // Split by newlines and wrap each line in a paragraph
            $paragraphs = array_filter(explode("\n", $html));
            if (count($paragraphs) > 1) {
                $wrappedParagraphs = array_map(function ($p) {
                    $p = trim($p);
                    return $p ? "<p>{$p}</p>" : '';
                }, $paragraphs);
                $html = implode('', $wrappedParagraphs);
            } else {
                // If it's a single block of text, wrap in one paragraph
                $html = "<p>" . trim(strip_tags($html)) . "</p>";
            }
        }

        return $html;
    }

    public function addNofollowToLinks($html)
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

            // Extract Logos using the dedicated service
            $logos = $this->logoExtractor->extract($url, $html);

            if (!empty($logos)) {
                $ogImage = $logos[0];
            } else {
                // Last ditch fallback to Google Favicon API
                $logos[] = 'https://www.google.com/s2/favicons?sz=128&domain_url=' . urlencode($url);
            }


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
            $response = Http::timeout(5)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);
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
                'name' => $this->nameExtractor->extract(trim($title), $url),
                'tagline' => trim($description),
                'description' => '',
                'favicon' => $faviconUrl,
            ]);
        } catch (\Exception $e) {
            Log::warning('Basic metadata fetch timed out or blocked: ' . $e->getMessage(), ['url' => $url]);
            return response()->json([
                'name' => '',
                'tagline' => '',
                'description' => '',
                'favicon' => null,
            ], 200);
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
        // Increase maximum execution time for scraping
        set_time_limit(120);

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
            $response = Http::timeout(5)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);
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
            'favicon' => $metadata['favicon'],
            'screenshot_url' => $this->screenshotService->capture($url),
        ];

        // Smart assignment: short tagline ≤ 140 chars, detailed ≤ 160 chars
        $metaTagline = $metadata['tagline'] ?? '';
        $headingTagline = $taglineDetailed;

        // If both are available, the shorter one is the tagline, longer is detailed
        if (!empty($metaTagline) && !empty($headingTagline) && $metaTagline !== $headingTagline) {
            if (strlen($metaTagline) <= strlen($headingTagline)) {
                $responseData['tagline'] = Str::limit($metaTagline, 140, '...');
                $responseData['tagline_detailed'] = Str::limit($headingTagline, 160, '...');
            } else {
                $responseData['tagline'] = Str::limit($headingTagline, 140, '...');
                $responseData['tagline_detailed'] = Str::limit($metaTagline, 160, '...');
            }
        } else {
            // Only one available — use it for whichever field it fits
            $availableTagline = !empty($metaTagline) ? $metaTagline : $headingTagline;
            if (strlen($availableTagline) <= 140) {
                $responseData['tagline'] = $availableTagline;
                $responseData['tagline_detailed'] = '';
            } else {
                $responseData['tagline'] = Str::limit($availableTagline, 140, '...');
                $responseData['tagline_detailed'] = Str::limit($availableTagline, 160, '...');
            }
        }

        Log::info('Fetched initial metadata', ['url' => $url, 'data' => $responseData]);
        return response()->json($responseData);
    }

    public function processUrlStream(Request $request)
    {
        set_time_limit(120);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($request) {
            $sendUpdate = function ($message, $progress, $data = null) {
                echo json_encode(['message' => $message, 'progress' => $progress, 'data' => $data]) . "\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $url = $request->input('url');
            if (!$url) {
                $sendUpdate('URL is required.', 0, ['error' => 'URL is required.']);
                return;
            }

            $name = $request->input('name');
            $tagline = $request->input('tagline');
            $fetchContent = $request->input('fetch_content', true);

            $description = '';
            $extractedTagline = '';
            $extractedTaglineDetailed = '';

            try {
                $sendUpdate('Connecting to website...', 5);
                $htmlResponse = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->timeout(15)->get($url);

                if (!$htmlResponse->successful()) {
                    $sendUpdate('Failed to fetch website.', 100, [
                        'description' => $description,
                        'logos' => [],
                        'tagline' => $tagline,
                        'tagline_detailed' => '',
                        'categories' => [],
                        'bestFor' => [],
                        'pricing' => [],
                        'pricing_page_url' => null,
                    ]);
                    return;
                }
                $htmlContent = $htmlResponse->body();
                $sendUpdate('Website fetched successfully...', 15);

                if ($fetchContent) {
                    $sendUpdate('Analyzing page structure...', 20);
                    $doc = new \DOMDocument();
                    @$doc->loadHTML($htmlContent);

                    $titleNode = $doc->getElementsByTagName('title')->item(0);
                    $title = $titleNode ? $titleNode->nodeValue : '';

                    $descriptionContent = '';
                    $metas = $doc->getElementsByTagName('meta');
                    for ($i = 0; $i < $metas->length; $i++) {
                        $meta = $metas->item($i);
                        if (strtolower($meta->getAttribute('name')) == 'description') {
                            $descriptionContent = $meta->getAttribute('content');
                            break;
                        }
                    }

                    $potentialTaglines = [];
                    foreach (['h1', 'h2', 'h3'] as $tag) {
                        foreach ($doc->getElementsByTagName($tag) as $heading) {
                            $potentialTaglines[] = $heading->nodeValue;
                        }
                    }

                    $xpath = new \DOMXPath($doc);
                    $elements = $xpath->query("//div[contains(@class, 'tagline') or contains(@class, 'slogan') or contains(@class, 'subtitle') or contains(@class, 'description') or contains(@class, 'headline')]");
                    foreach ($elements as $element) {
                        $potentialTaglines[] = $element->nodeValue;
                    }

                    $description = $descriptionContent;

                    $cleanDoc = clone $doc;
                    $cleanXpath = new \DOMXPath($cleanDoc);
                    // Remove common noise: layout elements, scripts, and third-party widgets
                    // (video embeds, iframes, cookie banners, chat widgets can pollute the AI context)
                    $noiseQuery = '//nav | //header | //footer | //script | //style | //noscript | //aside'
                        . ' | //video | //iframe | //form | //figure | //picture | //template'
                        . ' | //*[contains(@class,"cookie") or contains(@class,"banner") or contains(@class,"intercom") or contains(@class,"chat") or contains(@class,"widget")]';
                    $noise = $cleanXpath->query($noiseQuery);
                    foreach ($noise as $node) {
                        $node->parentNode?->removeChild($node);
                    }

                    $textContent = "Title: {$title}\n";
                    if (!empty($descriptionContent)) {
                        $textContent .= "Meta Description: {$descriptionContent}\n";
                    }
                    foreach (['h1', 'h2', 'h3'] as $tag) {
                        foreach ($cleanDoc->getElementsByTagName($tag) as $node) {
                            $textContent .= "\n" . strtoupper($tag) . ": " . trim($node->textContent);
                        }
                    }
                    // Cap body content to avoid drowning out product metadata in the AI context window
                    $rawBodyText = trim($cleanDoc->getElementsByTagName('body')->item(0)?->textContent ?? '');
                    $textContent .= "\n\nBODY CONTENT:\n" . mb_substr($rawBodyText, 0, 4000);

                    $productNameForAI = $name ?: ($title ?: 'this product');

                    $sendUpdate('Generating AI taglines...', 40);
                    try {
                        $taglineRewriter = new \App\Services\TaglineRewriterService();
                        $rawDescForTagline = $descriptionContent ?: implode('. ', array_filter(array_map('trim', array_slice($potentialTaglines, 0, 3))));
                        $aiTaglines = $taglineRewriter->rewrite($productNameForAI, $rawDescForTagline, $textContent);

                        if ($aiTaglines) {
                            $extractedTagline = $aiTaglines['tagline'];
                            $extractedTaglineDetailed = $aiTaglines['product_page_tagline'];
                        }
                    } catch (\Exception $e) {
                        // fallback
                    }

                    if (empty($extractedTagline) || empty($extractedTaglineDetailed)) {
                        $allCandidates = array_filter(array_map('trim', array_merge([$descriptionContent, trim($title)], $potentialTaglines)));
                        $allCandidates = array_unique($allCandidates);
                        $allCandidates = array_filter($allCandidates, function ($c) use ($name) {
                            return !empty($c) && strtolower(trim($c)) !== strtolower(trim($name ?? ''));
                        });
                        usort($allCandidates, fn($a, $b) => strlen($a) - strlen($b));

                        if (empty($extractedTagline)) {
                            foreach ($allCandidates as $candidate) {
                                if (strlen($candidate) <= 140 && strlen($candidate) > 5) {
                                    $extractedTagline = $candidate;
                                    break;
                                }
                            }
                            if (empty($extractedTagline) && !empty($allCandidates))
                                $extractedTagline = \Illuminate\Support\Str::limit(reset($allCandidates), 140, '...');
                        }

                        if (empty($extractedTaglineDetailed)) {
                            $reversed = array_reverse($allCandidates);
                            foreach ($reversed as $candidate) {
                                if ($candidate !== $extractedTagline && strlen($candidate) > 10) {
                                    $extractedTaglineDetailed = \Illuminate\Support\Str::limit($candidate, 160, '...');
                                    break;
                                }
                            }
                            if (empty($extractedTaglineDetailed) && !empty($allCandidates))
                                $extractedTaglineDetailed = \Illuminate\Support\Str::limit(end($allCandidates), 160, '...');
                        }
                    }

                    $extractedTagline = \Illuminate\Support\Str::limit($extractedTagline, 140, '...');
                    $extractedTaglineDetailed = \Illuminate\Support\Str::limit($extractedTaglineDetailed, 160, '...');

                    $sendUpdate('Writing product description...', 65);
                    $rawDescForRewrite = $descriptionContent;
                    if (empty($rawDescForRewrite)) {
                        $rawDescForRewrite = implode('. ', array_filter(array_map('trim', array_slice($potentialTaglines, 0, 5))));
                    }

                    if (!empty($rawDescForRewrite) || !empty(trim($textContent))) {
                        $descRewriter = new \App\Services\DescriptionRewriterService();
                        $rewritten = $descRewriter->rewrite($productNameForAI, $rawDescForRewrite ?: 'No meta description available', $textContent);
                        if ($rewritten) {
                            $description = $rewritten;
                        }
                    }
                }

                $sendUpdate('Extracting pricing page and logos...', 85);
                
                $pricingPageUrl = null;
                $links = $doc->getElementsByTagName('a');
                foreach ($links as $link) {
                    if (!$link->hasAttribute('href')) continue;
                    $href = $link->getAttribute('href');
                    if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) continue;
                    $text = strtolower(trim($link->textContent));
                    if (str_contains(strtolower($href), 'pricing') || str_contains($text, 'pricing') || str_contains($text, 'plans')) {
                        if (!preg_match('~^(?:f|ht)tps?://~i', $href)) {
                            $parsedUrl = parse_url($url);
                            $base = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                            $pricingPageUrl = $base . '/' . ltrim($href, '/');
                        } else {
                            $pricingPageUrl = $href;
                        }
                        break;
                    }
                }

                $logos = $this->logoExtractor->extract($url, $htmlContent);

                $sendUpdate('Classifying features and categories...', 95);
                $classificationResult = $this->categoryClassifier->classify($htmlContent);
                $categories = $classificationResult['categories'] ?? [];
                $bestFor = $classificationResult['best_for'] ?? [];
                $pricing = $classificationResult['pricing'] ?? [];

                $categoryIds = !empty($categories) ? \App\Models\Category::whereIn('name', $categories)->pluck('id')->toArray() : [];
                $bestForIds = !empty($bestFor) ? \App\Models\Category::whereIn('name', $bestFor)->pluck('id')->toArray() : [];
                $pricingIds = !empty($pricing) ? \App\Models\Category::whereIn('name', $pricing)->pluck('id')->toArray() : [];

                // Find category names the classifier suggested but that don't exist in DB
                $matchedCategoryNames = !empty($categories) ? \App\Models\Category::whereIn('name', $categories)->pluck('name')->toArray() : [];
                $unmatchedCategories = array_values(array_diff($categories, $matchedCategoryNames));

                $responseData = [
                    'description' => $description,
                    'logos' => $logos,
                    'tagline' => $extractedTagline ?: $tagline,
                    'tagline_detailed' => $extractedTaglineDetailed,
                    'categories' => $categoryIds,
                    'bestFor' => $bestForIds,
                    'pricing' => $pricingIds,
                    'suggestedCategories' => $unmatchedCategories,
                    'screenshot_url' => $this->screenshotService->capture($url),
                    'pricing_page_url' => $pricingPageUrl,
                ];

                $sendUpdate('Done!', 100, $responseData);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error in processUrlStream: ' . $e->getMessage());
                $sendUpdate('An error occurred during processing.', 100, [
                    'description' => $description,
                    'logos' => [],
                    'tagline' => $tagline,
                    'tagline_detailed' => '',
                    'categories' => [],
                    'bestFor' => [],
                    'pricing' => [],
                    'pricing_page_url' => null,
                ]);
            }
        });

        $response->headers->set('Content-Type', 'application/x-ndjson');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    public function processUrl(Request $request)
    {
        set_time_limit(120);

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
            $htmlResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->timeout(15)->get($url);
            if (!$htmlResponse->successful()) {
                return response()->json([
                    'description' => $description,
                    'logos' => [],
                    'tagline' => $tagline,
                    'tagline_detailed' => '',
                    'categories' => [],
                    'bestFor' => [],
                    'pricing' => [],
                    'pricing_page_url' => null,
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

                // --- Gather raw material for taglines and description ---
                $description = $descriptionContent; // meta description as raw material

                // Gather page text for additional context (cleaned of noise)
                $cleanDoc = clone $doc;
                $cleanXpath = new \DOMXPath($cleanDoc);
                $noise = $cleanXpath->query('//nav | //header | //footer | //script | //style | //noscript | //aside');
                foreach ($noise as $node) {
                    $node->parentNode?->removeChild($node);
                }

                $textContent = "Title: {$title}\n";
                if (!empty($descriptionContent)) {
                    $textContent .= "Meta Description: {$descriptionContent}\n";
                }
                foreach (['h1', 'h2', 'h3'] as $tag) {
                    foreach ($cleanDoc->getElementsByTagName($tag) as $node) {
                        $textContent .= "\n" . strtoupper($tag) . ": " . trim($node->textContent);
                    }
                }
                $textContent .= "\n\nBODY CONTENT:\n" . trim($cleanDoc->getElementsByTagName('body')->item(0)?->textContent ?? '');

                $productNameForAI = $name ?: ($title ?: 'this product');

                // --- AI Tagline Generation (primary source) ---
                try {
                    $taglineRewriter = new \App\Services\TaglineRewriterService();
                    $rawDescForTagline = $descriptionContent ?: implode('. ', array_filter(array_map('trim', array_slice($potentialTaglines, 0, 3))));
                    $aiTaglines = $taglineRewriter->rewrite($productNameForAI, $rawDescForTagline, $textContent);

                    if ($aiTaglines) {
                        $extractedTagline = $aiTaglines['tagline'];
                        $extractedTaglineDetailed = $aiTaglines['product_page_tagline'];
                        Log::info('TaglineRewriterService: AI-generated taglines', [
                            'tagline' => $extractedTagline,
                            'product_page_tagline' => $extractedTaglineDetailed,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('TaglineRewriterService failed, falling back to heuristic extraction', [
                        'error' => $e->getMessage(),
                    ]);
                }

                // --- Heuristic fallback if AI didn't produce taglines ---
                if (empty($extractedTagline) || empty($extractedTaglineDetailed)) {
                    // Collect all candidate strings: meta description, title, headings
                    $allCandidates = array_filter(array_map('trim', array_merge(
                        [$descriptionContent, trim($title)],
                        $potentialTaglines
                    )));

                    // Remove duplicates and the product name itself
                    $allCandidates = array_unique($allCandidates);
                    $allCandidates = array_filter($allCandidates, function ($c) use ($name) {
                        return !empty($c) && strtolower(trim($c)) !== strtolower(trim($name ?? ''));
                    });

                    // Sort by length: shortest first
                    usort($allCandidates, fn($a, $b) => strlen($a) - strlen($b));

                    if (empty($extractedTagline)) {
                        // Pick the shortest candidate that's ≤ 60 chars for short tagline
                        foreach ($allCandidates as $candidate) {
                            if (strlen($candidate) <= 140 && strlen($candidate) > 5) {
                                $extractedTagline = $candidate;
                                break;
                            }
                        }
                        // If nothing ≤ 60, truncate the shortest candidate
                        if (empty($extractedTagline) && !empty($allCandidates)) {
                            $extractedTagline = Str::limit(reset($allCandidates), 140, '...');
                        }
                    }

                    if (empty($extractedTaglineDetailed)) {
                        // Pick the longest candidate that differs from $extractedTagline
                        $reversed = array_reverse($allCandidates);
                        foreach ($reversed as $candidate) {
                            if ($candidate !== $extractedTagline && strlen($candidate) > 10) {
                                $extractedTaglineDetailed = Str::limit($candidate, 160, '...');
                                break;
                            }
                        }
                        // If nothing differs, use the same one truncated to 160
                        if (empty($extractedTaglineDetailed) && !empty($allCandidates)) {
                            $extractedTaglineDetailed = Str::limit(end($allCandidates), 160, '...');
                        }
                    }
                }

                // Final length enforcement
                $extractedTagline = Str::limit($extractedTagline, 140, '...');
                $extractedTaglineDetailed = Str::limit($extractedTaglineDetailed, 160, '...');

                // --- AI Description Rewrite ---
                // Generate description from page body text even if meta description is empty
                $rawDescForRewrite = $descriptionContent;
                if (empty($rawDescForRewrite)) {
                    // Build a description from headings and first body paragraphs
                    $rawDescForRewrite = implode('. ', array_filter(array_map('trim', array_slice($potentialTaglines, 0, 5))));
                }

                if (!empty($rawDescForRewrite) || !empty(trim($textContent))) {
                    $descRewriter = new DescriptionRewriterService();
                    $rewritten = $descRewriter->rewrite($productNameForAI, $rawDescForRewrite ?: 'No meta description available', $textContent);
                    if ($rewritten) {
                        $description = $rewritten;
                    }
                }
            }

            // Extract Pricing Page URL
            $pricingPageUrl = null;
            $links = $doc->getElementsByTagName('a');
            foreach ($links as $link) {
                if (!$link->hasAttribute('href')) continue;
                $href = $link->getAttribute('href');
                if (str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) continue;
                $text = strtolower(trim($link->textContent));
                if (str_contains(strtolower($href), 'pricing') || str_contains($text, 'pricing') || str_contains($text, 'plans')) {
                    if (!preg_match('~^(?:f|ht)tps?://~i', $href)) {
                        $parsedUrl = parse_url($url);
                        $base = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? '');
                        $pricingPageUrl = $base . '/' . ltrim($href, '/');
                    } else {
                        $pricingPageUrl = $href;
                    }
                    break;
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
            $matchedCategoryNames = [];
            if (!empty($categories)) {
                $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();
                $matchedCategoryNames = Category::whereIn('name', $categories)->pluck('name')->toArray();
            }
            $unmatchedCategories = array_values(array_diff($categories, $matchedCategoryNames));

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
                'suggestedCategories' => $unmatchedCategories,
                'screenshot_url' => $this->screenshotService->capture($url),
                'pricing_page_url' => $pricingPageUrl,
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
                'pricing_page_url' => null,
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

    protected function processMediaItem($product, $file, $manager, $isExternalPath = false)
    {
        if ($isExternalPath) {
            $path = 'product_media/' . basename($file);
            // $file is already the absolute path to the downloaded image
            $absolutePath = $file;
            $mimeType = mime_content_type($file);
        } else {
            $path = $file->store('product_media', 'public');
            $absolutePath = Storage::disk('public')->path($path);
            $mimeType = $file->getMimeType();
        }

        $type = Str::startsWith($mimeType, 'image') ? 'image' : 'video';
        $pathThumb = null;
        $pathMedium = null;

        if ($type === 'image') {
            try {
                $filename = basename($path);
                $directory = dirname($path);

                // Generate Thumbnail (300px width)
                $imageThumb = $manager->read($absolutePath);
                $imageThumb->scale(width: 300);
                $thumbFilename = 'thumb_' . $filename;
                $pathThumb = $directory . '/' . $thumbFilename;
                Storage::disk('public')->put($pathThumb, (string) $imageThumb->encode());

                // Generate Medium (800px width)
                $imageMedium = $manager->read($absolutePath);
                $imageMedium->scale(width: 800);
                $mediumFilename = 'medium_' . $filename;
                $pathMedium = $directory . '/' . $mediumFilename;
                Storage::disk('public')->put($pathMedium, (string) $imageMedium->encode());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Image resizing failed: ' . $e->getMessage());
            }
        }

        $product->media()->create([
            'path' => $path,
            'path_thumb' => $pathThumb,
            'path_medium' => $pathMedium,
            'alt_text' => $product->name . ' media',
            'type' => $type,
        ]);
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
}
