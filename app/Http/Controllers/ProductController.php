<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Type;
use App\Models\PremiumProduct;
use App\Models\TechStack;
use App\Models\ProductClaim;
use App\Models\ProductCollection;
use App\Models\ProductSubmissionDraft;
use App\Models\UserProductUpvote; // Added for upvote checking
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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\FaviconExtractorService;
use App\Services\SlugService;
use App\Services\TechStackDetectorService;
use App\Services\NameExtractorService;
use App\Services\LogoExtractorService;
use App\Services\DescriptionRewriterService;
use App\Services\ProductLimitationResearchService;
use App\Services\ProductLogoStorageService;
use App\Services\ProductLogoResolver;
use App\Services\ScreenshotService;
use App\Services\BadgeService;
use App\Services\AdDeliveryService;
use App\Services\RelatedProductService;
use App\Services\ProductEditorialContentService;
use App\Jobs\FetchOgImage;
use App\Support\CategoryTypeRegistry;
use App\Support\FreeLaunchQueueSettings;
use App\Support\PremiumLaunchPricing;
use App\Support\SocialLinkValidator;
use App\Support\PublicUrlGuard;
use App\Support\ProductMediaSeo;
use App\Support\ProductPublishSchedule;
use DOMDocument;
use DOMXPath;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    protected FaviconExtractorService $faviconExtractor;
    protected SlugService $slugService;
    protected TechStackDetectorService $techStackDetector;
    protected NameExtractorService $nameExtractor;
    protected LogoExtractorService $logoExtractor;
    protected ProductLogoResolver $productLogoResolver;
    protected \App\Services\CategoryClassifier $categoryClassifier;
    protected ScreenshotService $screenshotService;
    protected BadgeService $badgeService;
    protected RelatedProductService $relatedProductService;

    public function __construct(FaviconExtractorService $faviconExtractor, SlugService $slugService, TechStackDetectorService $techStackDetector, NameExtractorService $nameExtractor, LogoExtractorService $logoExtractor, \App\Services\CategoryClassifier $categoryClassifier, ScreenshotService $screenshotService, BadgeService $badgeService, RelatedProductService $relatedProductService, ProductLogoResolver $productLogoResolver)
    {
        $this->faviconExtractor = $faviconExtractor;
        $this->slugService = $slugService;
        $this->techStackDetector = $techStackDetector;
        $this->nameExtractor = $nameExtractor;
        $this->logoExtractor = $logoExtractor;
        $this->productLogoResolver = $productLogoResolver;
        $this->categoryClassifier = $categoryClassifier;
        $this->screenshotService = $screenshotService;
        $this->badgeService = $badgeService;
        $this->relatedProductService = $relatedProductService;
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
        $adminSandboxEnabled = $this->isAdminAddProductSandboxEnabled();

        [
            'regularCategories' => $regularCategories,
            'useCaseCategories' => $useCaseCategories,
            'pricingCategories' => $pricingCategories,
            'bestForCategories' => $bestForCategories,
            'platformCategories' => $platformCategories,
        ] = $this->loadProductCategoryGroups();

        $oldInput = session()->getOldInput();
        $displayData = [
            'name' => $oldInput['name'] ?? '',
            'slug' => $oldInput['slug'] ?? '',
            'link' => $oldInput['link'] ?? '',
            'additional_resources' => $oldInput['additional_resources'] ?? '',
            'tagline' => $oldInput['tagline'] ?? '',
            'product_page_tagline' => $oldInput['product_page_tagline'] ?? '',
            'description' => $oldInput['description'] ?? '',
            'maker_links' => $oldInput['maker_links'] ?? [],
            'sell_product' => $oldInput['sell_product'] ?? false,
            'asking_price' => $oldInput['asking_price'] ?? null,
            'pricing_page_url' => $oldInput['pricing_page_url'] ?? null,
            'x_account' => $oldInput['x_account'] ?? null,
            'logo' => null,
            'video_url' => null,
            'current_categories' => $oldInput['categories'] ?? [],
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? [],
        ];

        $types = Type::with('categories')->get();
        $submissionBgUrl = config('theme.submission_bg_url') ? Storage::url(config('theme.submission_bg_url')) : asset('images/submission-pattern.png');
        $submissionBgUrl = config('theme.submission_bg_url') ? Storage::url(config('theme.submission_bg_url')) : asset('images/submission-pattern.png');
        $premiumLaunchPriceCents = PremiumLaunchPricing::cents();
        $freeLaunchQueueMonths = FreeLaunchQueueSettings::months();
        return view('products.create', compact(
            'displayData',
            'regularCategories',
            'useCaseCategories',
            'bestForCategories',
            'pricingCategories',
            'platformCategories',
            'allTechStacksData',
            'types',
            'adminSandboxEnabled',
            'premiumLaunchPriceCents',
            'freeLaunchQueueMonths'
        ));
    }

    public function createSubmission(Request $request)
    {
        $allTechStacks = TechStack::orderBy('name')->get();
        $allTechStacksData = $allTechStacks->map(fn($ts) => ['id' => $ts->id, 'name' => $ts->name]);
        $adminSandboxEnabled = $this->isAdminAddProductSandboxEnabled();

        [
            'regularCategories' => $regularCategories,
            'useCaseCategories' => $useCaseCategories,
            'pricingCategories' => $pricingCategories,
            'bestForCategories' => $bestForCategories,
            'platformCategories' => $platformCategories,
        ] = $this->loadProductCategoryGroups();

        $oldInput = session()->getOldInput();
        $activeDraft = $this->resolveRequestedSubmissionDraft($request);
        $draftDisplayData = $activeDraft ? $this->mapSubmissionDraftToDisplayData($activeDraft) : [];
        $displayData = [
            'name' => $oldInput['name'] ?? ($draftDisplayData['name'] ?? ''),
            'slug' => $oldInput['slug'] ?? ($draftDisplayData['slug'] ?? ''),
            'link' => $oldInput['link'] ?? ($draftDisplayData['link'] ?? ''),
            'additional_resources' => $oldInput['additional_resources'] ?? ($draftDisplayData['additional_resources'] ?? ''),
            'tagline' => $oldInput['tagline'] ?? ($draftDisplayData['tagline'] ?? ''),
            'product_page_tagline' => $oldInput['product_page_tagline'] ?? ($draftDisplayData['product_page_tagline'] ?? ''),
            'description' => $oldInput['description'] ?? ($draftDisplayData['description'] ?? ''),
            'logo' => $draftDisplayData['logo'] ?? null,
            'logo_url' => $draftDisplayData['logo_url'] ?? null,
            'video_url' => $draftDisplayData['video_url'] ?? null,
            'current_categories' => $oldInput['categories'] ?? ($draftDisplayData['current_categories'] ?? []),
            'current_tech_stacks' => $oldInput['tech_stacks'] ?? ($draftDisplayData['current_tech_stacks'] ?? []),
            'maker_links' => $oldInput['maker_links'] ?? ($draftDisplayData['maker_links'] ?? []),
            'sell_product' => $oldInput['sell_product'] ?? ($draftDisplayData['sell_product'] ?? false),
            'asking_price' => $oldInput['asking_price'] ?? ($draftDisplayData['asking_price'] ?? null),
            'pricing_page_url' => $oldInput['pricing_page_url'] ?? ($draftDisplayData['pricing_page_url'] ?? ''),
            'x_account' => $oldInput['x_account'] ?? ($draftDisplayData['x_account'] ?? ''),
            'logos' => $draftDisplayData['logos'] ?? [],
            'gallery' => $draftDisplayData['gallery'] ?? [],
            'categories_custom' => $draftDisplayData['categories_custom'] ?? [],
            'useCases_custom' => $draftDisplayData['useCases_custom'] ?? [],
            'platforms_custom' => $draftDisplayData['platforms_custom'] ?? [],
            'bestFor_custom' => $draftDisplayData['bestFor_custom'] ?? [],
            'tech_stack_custom' => $draftDisplayData['tech_stack_custom'] ?? [],
            'draft_uuid' => $draftDisplayData['draft_uuid'] ?? null,
        ];

        $types = Type::with('categories')->get();
        $submissionBgUrl = config('theme.submission_bg_url') ? Storage::url(config('theme.submission_bg_url')) : asset('images/submission-pattern.png');
        $premiumLaunchPriceCents = PremiumLaunchPricing::cents();
        $freeLaunchQueueMonths = FreeLaunchQueueSettings::months();
        $submissionDrafts = $request->user()
            ? ProductSubmissionDraft::query()
                ->forUser($request->user())
                ->latest('updated_at')
                ->get()
                ->map(fn (ProductSubmissionDraft $draft) => $draft->toSummaryArray())
                ->values()
                ->all()
            : [];
        $activeDraftId = $activeDraft?->uuid;

        return view('products.create', compact(
            'displayData',
            'regularCategories',
            'useCaseCategories',
            'bestForCategories',
            'pricingCategories',
            'platformCategories',
            'allTechStacksData',
            'types',
            'submissionBgUrl',
            'adminSandboxEnabled',
            'premiumLaunchPriceCents',
            'freeLaunchQueueMonths',
            'submissionDrafts',
            'activeDraftId'
        ));
    }

    public function verifyBadgePlacement(Request $request)
    {
        $allowedSubmissionTypes = $isAdmin ? ['free', 'badge', 'paid'] : ['free', 'badge'];

        $validated = $request->validate([
            'url' => 'required|url|max:2048',
        ]);

        $checkedUrl = Product::normalizeLink($validated['url']);
        $verification = $this->badgeService->verifyPlacementUrl($checkedUrl);

        if (!$verification['verified']) {
            return response()->json([
                'verified' => false,
                'checked_url' => $checkedUrl,
                'message' => $verification['message'],
            ], 422);
        }

        return response()->json([
            'verified' => true,
            'checked_url' => $checkedUrl,
            'message' => $verification['message'],
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $requestedSandboxMode = $request->boolean('sandbox_mode');
        $adminSandboxEnabled = $this->isAdminAddProductSandboxEnabled();

        if ($isAdmin && $requestedSandboxMode && ! $adminSandboxEnabled) {
            $message = 'Sandbox mode is disabled in admin settings.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors(['sandbox_mode' => $message])->withInput();
        }

        $isAdminSandbox = $isAdmin && $adminSandboxEnabled && $requestedSandboxMode;

        if ($isAdminSandbox) {
            $message = 'Sandbox submission complete. No product was saved to the database.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'sandbox' => true,
                    'message' => $message,
                ]);
            }

            return back()->with('status', $message);
        }

        $allowedSubmissionTypes = $isAdmin ? ['free', 'badge', 'paid'] : ['free', 'badge'];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'maker_links' => 'nullable|array',
            'maker_links.*' => [
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!SocialLinkValidator::isAllowedMakerLinkUrl($value)) {
                        $fail(SocialLinkValidator::allowedMakerLinkMessage());
                    }
                },
            ],
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
            'submission_type' => ['nullable', Rule::in($allowedSubmissionTypes)],
            'badge_placement_url' => 'nullable|url|max:2048',
            'badge_week_start' => 'nullable|date_format:Y-m-d',
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
            'custom_categories' => 'nullable|array|max:14',
            'custom_categories.*.name' => 'required|string|max:100',
            'custom_categories.*.type' => 'required|in:category,use_case,best_for,platform',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:5120',
            'logo_url' => 'nullable|string', // Relaxed for base64 support
            'video_url' => 'nullable|string|max:2048',
            'media' => 'nullable|array|max:1',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'media_urls' => 'nullable|array|max:1',
            'media_urls.*' => 'nullable|string|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
            'draft_uuid' => 'nullable|string|size:36',
        ]);

        $validated['link'] = Product::normalizeLink($validated['link']);
        $validated['product_page_tagline'] = filled(trim((string) ($validated['product_page_tagline'] ?? '')))
            ? trim((string) $validated['product_page_tagline'])
            : trim((string) $validated['tagline']);
        if (!empty($validated['pricing_page_url'])) {
            $validated['pricing_page_url'] = Product::normalizeLink($validated['pricing_page_url']);
        }
        if (!empty($validated['badge_placement_url'])) {
            $validated['badge_placement_url'] = Product::normalizeLink($validated['badge_placement_url']);
        }

        // Check if a product with this URL already exists
        $existingProduct = Product::where('link', $validated['link'])->first();
        if ($existingProduct) {
            return back()->withErrors(['link' => 'A product with this URL already exists. You cannot add the same product twice.'])->withInput();
        }

        $existsCheck = function ($slug) {
            return Product::where('slug', $slug)->exists();
        };

        $validated['slug'] = $this->slugService->generateUniqueSlug($validated['name'], $existsCheck);

        $pricingType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING))->with('categories')->first();
        $softwareType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))->with('categories')->first();
        $useCaseType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->with('categories')->first();
        $submittedCategories = is_array($request->input('categories')) ? $request->input('categories') : [];
        $selected = collect($submittedCategories)->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id') : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id') : collect();
        $useCaseIds = $useCaseType ? $useCaseType->categories->pluck('id') : collect();

        $customCategories = $request->input('custom_categories', []);
        $hasCustomPricing = collect($customCategories)->contains('type', 'pricing'); // Note: Assuming users don't submit custom pricing types, but theoretically could
        $hasCustomSoftware = collect($customCategories)->contains('type', 'category');
        $hasCustomUseCase = collect($customCategories)->contains(function ($category) {
            return ($category['type'] ?? null) === 'use_case' && filled(trim((string) ($category['name'] ?? '')));
        });

        if ($pricingIds->count() && $selected->intersect($pricingIds)->isEmpty() && !$hasCustomPricing) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Pricing group.'])->withInput();
        }
        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty() && !$hasCustomSoftware) {
            return back()->withErrors(['categories' => 'Please select at least one category from the Software Categories group.'])->withInput();
        }
        if ($useCaseIds->count() && $selected->intersect($useCaseIds)->isEmpty() && !$hasCustomUseCase) {
            return back()->withErrors(['categories' => 'Please select at least one use case.'])->withInput();
        }
        // bestFor remains optional

        $validated['user_id'] = Auth::id();
        $validated['votes_count'] = 1;

        $submissionError = function (string $field, string $message) use ($request) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => [$field => [$message]],
                ], 422);
            }

            return back()->withErrors([$field => $message])->withInput();
        };

        // Handle submission type: 'badge' submissions get instant approval
        $submissionType = $request->input('submission_type', 'free');
        if ($isAdmin && !in_array($submissionType, ['free', 'badge'], true)) {
            $submissionType = 'free';
        }
        $validated['submission_type'] = in_array($submissionType, ['free', 'badge']) ? $submissionType : 'free';

        if ($submissionType === 'badge') {
            $badgePlacementUrl = $validated['badge_placement_url'] ?? $validated['link'];
            $selectedWeekStart = $request->input('badge_week_start');

            if (empty($badgePlacementUrl)) {
                return $submissionError('badge_placement_url', 'Add the page URL where your badge is placed.');
            }

            if (empty($selectedWeekStart)) {
                return $submissionError('badge_week_start', 'Choose a launch week after badge verification.');
            }

            try {
                $launchDate = $this->badgeService->resolveBadgeLaunchDate($selectedWeekStart);
            } catch (\InvalidArgumentException $exception) {
                return $submissionError('badge_week_start', $exception->getMessage());
            }

            $verification = $this->badgeService->verifyPlacementUrl($badgePlacementUrl);
            if (!$verification['verified']) {
                return $submissionError('badge_placement_url', $verification['message']);
            }

            $validated['approved'] = true;
            $validated['is_published'] = false; // Scheduled, not instant
            $validated['published_at'] = $launchDate;
            $validated['badge_placement_url'] = $badgePlacementUrl;
            $validated['badge_verified'] = true;
            $validated['badge_verified_at'] = now();
            $validated['badge_consecutive_failures'] = 0;
            $validated['badge_warning_sent_at'] = null;
        } else {
            $validated['approved'] = false;
        }
        unset($validated['badge_week_start']);
        $validated['description'] = $this->ensureProperParagraphStructure($this->addNofollowToLinks($request->input('description')));

        // Handle optional fields
        $validated['maker_links'] = $request->input('maker_links', []);
        $validated['sell_product'] = $request->boolean('sell_product', false);
        $validated['asking_price'] = $request->input('asking_price');

        $validated['x_account'] = Product::normalizeXAccount($request->input('x_account'));

        if ($request->hasFile('logo')) {
            $validated['logo'] = app(ProductLogoStorageService::class)
                ->storeUploadedFile($request->file('logo'));
        } elseif ($request->filled('logo_url')) {
            $logoUrl = $request->input('logo_url');

            // Handle Base64/Data URL
            if (Str::startsWith($logoUrl, 'data:image')) {
                try {
                    $storedLogo = app(ProductLogoStorageService::class)->storeDataUrl($logoUrl);

                    if ($storedLogo) {
                        $validated['logo'] = $storedLogo;
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to save base64 logo: ' . $e->getMessage());
                    // Fallback to URL if saving fails (though it might still fail validation later)
                    $validated['logo'] = $logoUrl;
                }
            } else {
                $resolvedLogoUrl = $this->productLogoResolver->resolvePreferredLogoUrl($request->input('link'), $logoUrl);

                if ($resolvedLogoUrl) {
                    try {
                        $validated['logo'] = app(ProductLogoStorageService::class)->storeRemoteUrl($resolvedLogoUrl) ?? $resolvedLogoUrl;
                    } catch (\Throwable $throwable) {
                        Log::warning('Failed to localize remote product logo; keeping external URL.', [
                            'url' => $resolvedLogoUrl,
                            'error' => $throwable->getMessage(),
                        ]);
                        $validated['logo'] = $resolvedLogoUrl;
                    }
                }
            }
        }
        unset($validated['logo_url']);

        $product = DB::transaction(function () use ($validated, $request) {
            $product = Product::create($validated);
            $product->categories()->sync($validated['categories']);
            if (isset($validated['tech_stacks'])) {
                $product->techStacks()->sync($validated['tech_stacks']);
            }

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

            return $product;
        });

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

        if ($user && filled($request->input('draft_uuid'))) {
            ProductSubmissionDraft::query()
                ->forUser($user)
                ->where('uuid', $request->input('draft_uuid'))
                ->delete();
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

    protected function resolveRequestedSubmissionDraft(Request $request): ?ProductSubmissionDraft
    {
        $draftUuid = trim((string) $request->query('draft', ''));

        if ($draftUuid === '' || ! $request->user()) {
            return null;
        }

        return ProductSubmissionDraft::query()
            ->forUser($request->user())
            ->where('uuid', $draftUuid)
            ->firstOrFail();
    }

    protected function mapSubmissionDraftToDisplayData(ProductSubmissionDraft $draft): array
    {
        $payload = $draft->payload ?? [];
        $logoPreview = $payload['logoPreview'] ?? ($payload['favicon'] ?? null);
        $gallery = array_values(array_filter((array) ($payload['galleryPreviews'] ?? []), fn ($value) => filled($value)));

        return [
            'name' => $payload['name'] ?? '',
            'slug' => $payload['slug'] ?? '',
            'link' => $payload['link'] ?? '',
            'additional_resources' => $payload['additional_resources'] ?? '',
            'tagline' => $payload['tagline'] ?? '',
            'product_page_tagline' => $payload['tagline_detailed'] ?? ($payload['tagline'] ?? ''),
            'description' => $payload['description'] ?? '',
            'logo' => $payload['logo'] ?? null,
            'logo_url' => $logoPreview,
            'video_url' => $payload['video_url'] ?? null,
            'current_categories' => array_values(array_merge(
                (array) ($payload['categories'] ?? []),
                (array) ($payload['useCases'] ?? []),
                (array) ($payload['platforms'] ?? []),
                (array) ($payload['bestFor'] ?? []),
                (array) ($payload['pricing'] ?? [])
            )),
            'current_tech_stacks' => array_values((array) ($payload['tech_stack'] ?? [])),
            'maker_links' => array_values((array) ($payload['maker_links'] ?? [])),
            'sell_product' => (bool) ($payload['sell_product'] ?? false),
            'asking_price' => $payload['asking_price'] ?? null,
            'pricing_page_url' => $payload['pricing_page_url'] ?? '',
            'x_account' => $payload['x_account'] ?? '',
            'logos' => array_values((array) ($payload['logos'] ?? [])),
            'gallery' => $gallery,
            'categories_custom' => array_values((array) ($payload['categories_custom'] ?? [])),
            'useCases_custom' => array_values((array) ($payload['useCases_custom'] ?? [])),
            'platforms_custom' => array_values((array) ($payload['platforms_custom'] ?? [])),
            'bestFor_custom' => array_values((array) ($payload['bestFor_custom'] ?? [])),
            'tech_stack_custom' => array_values((array) ($payload['tech_stack_custom'] ?? [])),
            'draft_uuid' => $draft->uuid,
        ];
    }

    public function showSubmissionSuccess(Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (Auth::id() !== $product->user_id && !(Auth::check() && $user && $user->hasRole('admin'))) {
            abort(403, 'Unauthorized action.');
        }

        $submissionDate = Carbon::now();
        $tentativeLiveDate = $submissionDate->copy()->addWeeks(10);
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

        [
            'regularCategories' => $regularCategories,
            'useCaseCategories' => $useCaseCategories,
            'pricingCategories' => $pricingCategories,
            'bestForCategories' => $bestForCategories,
            'platformCategories' => $platformCategories,
        ] = $this->loadProductCategoryGroups();

        $product->load(['categories', 'proposedCategories', 'techStacks', 'media', 'customCategorySubmissions']);

        $pendingCustomSubmissions = $product->customCategorySubmissions
            ->where('status', 'pending')
            ->values();

        $pendingCustomCategories = $pendingCustomSubmissions
            ->where('type', 'category')
            ->map(fn ($submission) => [
                'id' => 'pending-category-' . $submission->id,
                'name' => $submission->name,
                'is_custom' => true,
            ])
            ->values()
            ->all();

        $pendingCustomUseCases = $pendingCustomSubmissions
            ->where('type', 'use_case')
            ->map(fn ($submission) => [
                'id' => 'pending-use-case-' . $submission->id,
                'name' => $submission->name,
                'is_custom' => true,
            ])
            ->values()
            ->all();

        $pendingCustomPlatforms = $pendingCustomSubmissions
            ->where('type', 'platform')
            ->map(fn ($submission) => [
                'id' => 'pending-platform-' . $submission->id,
                'name' => $submission->name,
                'is_custom' => true,
            ])
            ->values()
            ->all();

        $pendingCustomBestFor = $pendingCustomSubmissions
            ->where('type', 'best_for')
            ->map(fn ($submission) => [
                'id' => 'pending-best-for-' . $submission->id,
                'name' => $submission->name,
                'is_custom' => true,
            ])
            ->values()
            ->all();

        $pendingCustomTechStacks = $pendingCustomSubmissions
            ->where('type', 'tech_stack')
            ->map(fn ($submission) => [
                'id' => 'pending-tech-stack-' . $submission->id,
                'name' => $submission->name,
                'is_custom' => true,
            ])
            ->values()
            ->all();

        $liveGallery = $product->media
            ->whereIn('type', ['image', 'screenshot'])
            ->take(1)
            ->pluck('path')
            ->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))
            ->toArray();

        $proposedGallery = $product->proposed_screenshot_path
            ? [\Illuminate\Support\Facades\Storage::url($product->proposed_screenshot_path)]
            : $liveGallery;

        $oldInput = session()->getOldInput();

        if ($product->approved && $product->has_pending_edits) {
            // When there are pending edits, use proposed values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'additional_resources' => $oldInput['additional_resources'] ?? '',
                'logo' => $product->proposed_logo_path ?? $product->logo,
                'logo_url' => ($product->proposed_logo_path ? \Illuminate\Support\Facades\Storage::url($product->proposed_logo_path) : $product->logo_url),
                'tagline' => $product->proposed_tagline ?? $product->tagline,
                'product_page_tagline' => $oldInput['product_page_tagline'] ?? $product->proposed_product_page_tagline ?? $product->product_page_tagline,
                'description' => $product->proposed_description ?? $product->description,
                'current_categories' => $product->proposedCategories->pluck('id')->toArray(),
                'current_tech_stacks' => $oldInput['tech_stacks'] ?? $product->techStacks->pluck('id')->toArray(),
                'maker_links' => $oldInput['maker_links'] ?? ($product->proposed_maker_links ?? $product->maker_links),
                'sell_product' => $oldInput['sell_product'] ?? (!is_null($product->proposed_sell_product) ? $product->proposed_sell_product : $product->sell_product),
                'asking_price' => $oldInput['asking_price'] ?? (!is_null($product->proposed_asking_price) ? $product->proposed_asking_price : $product->asking_price),
                'pricing_page_url' => $oldInput['pricing_page_url'] ?? ($product->proposed_pricing_page_url ?? $product->pricing_page_url),
                'x_account' => $oldInput['x_account'] ?? ($product->proposed_x_account ?? $product->x_account),
                'id' => $product->id,
                'logos' => $product->media->whereIn('type', ['image', 'screenshot'])->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
                'gallery' => $proposedGallery,
                'categories_custom' => $pendingCustomCategories,
                'useCases_custom' => $pendingCustomUseCases,
                'platforms_custom' => $pendingCustomPlatforms,
                'bestFor_custom' => $pendingCustomBestFor,
                'tech_stack_custom' => $pendingCustomTechStacks,
            ];
        } else {
            // When no pending edits, use original values
            $displayData = [
                'name' => $oldInput['name'] ?? $product->name,
                'slug' => $oldInput['slug'] ?? $product->slug,
                'link' => $oldInput['link'] ?? $product->link,
                'additional_resources' => $oldInput['additional_resources'] ?? '',
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
                'pricing_page_url' => $oldInput['pricing_page_url'] ?? $product->pricing_page_url,
                'x_account' => $oldInput['x_account'] ?? $product->x_account,
                'id' => $product->id,
                'logos' => $product->media->whereIn('type', ['image', 'screenshot'])->pluck('path')->map(fn($path) => \Illuminate\Support\Facades\Storage::url($path))->toArray(),
                'gallery' => $liveGallery,
                'categories_custom' => $pendingCustomCategories,
                'useCases_custom' => $pendingCustomUseCases,
                'platforms_custom' => $pendingCustomPlatforms,
                'bestFor_custom' => $pendingCustomBestFor,
                'tech_stack_custom' => $pendingCustomTechStacks,
            ];
        }

        $types = Type::with('categories')->get();

        // Get the selected bestFor categories to pass to the JavaScript component
        $useProposedCategories = $product->approved && $product->has_pending_edits;
        $selectedBestForCategories = $this->selectedCategoryIdsForType($product, CategoryTypeRegistry::BEST_FOR, $useProposedCategories);
        $selectedUseCaseCategories = $this->selectedCategoryIdsForType($product, CategoryTypeRegistry::USE_CASE, $useProposedCategories);
        $selectedPlatformCategories = $this->selectedCategoryIdsForType($product, CategoryTypeRegistry::PLATFORM, $useProposedCategories);

        // Debug: Log the display data to see what's being passed
        \Log::info('Product edit displayData', [
            'product_id' => $product->id,
            'display_data' => $displayData,
            'selected_best_for_categories' => $selectedBestForCategories,
            'categories_count' => count($displayData['current_categories'] ?? [])
        ]);

        $submissionBgUrl = config('theme.submission_bg_url') ? Storage::url(config('theme.submission_bg_url')) : asset('images/submission-pattern.png');
        $adminSandboxEnabled = $this->isAdminAddProductSandboxEnabled();
        $premiumLaunchPriceCents = PremiumLaunchPricing::cents();
        $freeLaunchQueueMonths = FreeLaunchQueueSettings::months();

        return view('products.create', compact(
            'product',
            'displayData',
            'regularCategories',
            'useCaseCategories',
            'bestForCategories',
            'pricingCategories',
            'platformCategories',
            'allTechStacksData',
            'types',
            'selectedUseCaseCategories',
            'selectedBestForCategories',
            'selectedPlatformCategories',
            'submissionBgUrl',
            'adminSandboxEnabled',
            'premiumLaunchPriceCents',
            'freeLaunchQueueMonths'
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
            'custom_categories' => 'nullable|array|max:14',
            'custom_categories.*.name' => 'required|string|max:100',
            'custom_categories.*.type' => 'required|in:category,use_case,best_for,platform',
            'logo' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif|max:5120', // File upload for logo
            'logo_url' => 'nullable|string',
            'remove_logo' => 'nullable|boolean', // For removing existing logo
            'video_url' => 'nullable|string|max:2048',
            'media' => 'nullable|array|max:1',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'media_urls' => 'nullable|array|max:1',
            'media_urls.*' => 'nullable|string|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
            'maker_links' => 'nullable|array',
            'maker_links.*' => [
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if (!SocialLinkValidator::isAllowedMakerLinkUrl($value)) {
                        $fail(SocialLinkValidator::allowedMakerLinkMessage());
                    }
                },
            ],
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'pricing_page_url' => 'nullable|url|max:2048',
            'x_account' => 'nullable|string|max:255',
        ]);

        if (!empty($validated['pricing_page_url'])) {
            $validated['pricing_page_url'] = Product::normalizeLink($validated['pricing_page_url']);
        }

        // Category validation (ensure at least one from each required type is selected)
        // This logic can be kept or adjusted based on whether proposed edits should also adhere to it.
        // For simplicity, we'll assume it applies.
        $pricingType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING))->with('categories')->first();
        $softwareType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))->with('categories')->first();
        $useCaseType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->with('categories')->first();
        $submittedCategories = is_array($request->input('categories')) ? $request->input('categories') : [];
        $selected = collect($submittedCategories)->map(fn($id) => (int) $id);
        $pricingIds = $pricingType ? $pricingType->categories->pluck('id') : collect();
        $softwareIds = $softwareType ? $softwareType->categories->pluck('id') : collect();
        $useCaseIds = $useCaseType ? $useCaseType->categories->pluck('id') : collect();

        $customCategories = $request->input('custom_categories', []);
        $hasCustomPricing = collect($customCategories)->contains('type', 'pricing');
        $hasCustomSoftware = collect($customCategories)->contains('type', 'category');
        $hasCustomUseCase = collect($customCategories)->contains(function ($category) {
            return ($category['type'] ?? null) === 'use_case' && filled(trim((string) ($category['name'] ?? '')));
        });

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
        if ($useCaseIds->count() && $selected->intersect($useCaseIds)->isEmpty() && !$hasCustomUseCase) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select at least one use case.',
                    'errors' => ['categories' => ['Please select at least one use case.']]
                ], 422);
            }
            return back()->withErrors(['categories' => 'Please select at least one use case.'])->withInput();
        }
        // bestFor remains optional

        // Prepare data for update
        $updateData = [
            'tagline' => $validated['tagline'],
            'product_page_tagline' => $validated['product_page_tagline'],
            'description' => $this->ensureProperParagraphStructure($this->addNofollowToLinks($validated['description'])),
            'video_url' => $validated['video_url'] ?? null,
            'maker_links' => $validated['maker_links'] ?? [],
            'sell_product' => $validated['sell_product'] ?? false,
            'asking_price' => $validated['asking_price'] ?? null,
            'pricing_page_url' => $validated['pricing_page_url'] ?? null,
        ];

        $updateData['x_account'] = Product::normalizeXAccount($validated['x_account'] ?? null);

        $newCategories = $validated['categories'] ?? [];
        $newTechStacks = $validated['tech_stacks'] ?? [];
        $logoPath = null;

        // Handle logo upload
        if ($request->boolean('remove_logo')) {
            $logoPath = null; // Explicitly set to null for removal
        } elseif ($request->hasFile('logo')) {
            $logoPath = app(ProductLogoStorageService::class)
                ->storeUploadedFile($request->file('logo'));
        } elseif ($request->filled('logo_url')) {
            $logoPath = $this->resolveLogoPathFromInput((string) $request->input('logo_url'), (string) $request->input('link', $product->link));
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

            $product->proposed_tagline = $updateData['tagline'];
            $product->proposed_product_page_tagline = $updateData['product_page_tagline'];
            $product->proposed_description = $this->ensureProperParagraphStructure($updateData['description']);
            $product->proposed_video_url = $updateData['video_url'] ?? null;
            $product->proposed_x_account = $updateData['x_account'] ?? null;
            $product->proposed_sell_product = $updateData['sell_product'];
            $product->proposed_asking_price = $updateData['asking_price'];
            $product->proposed_maker_links = $updateData['maker_links'];
            $product->proposed_pricing_page_url = $updateData['pricing_page_url'];
            $product->proposedCategories()->sync($newCategories);
            $product->proposedTechStacks()->sync($newTechStacks);
            $this->syncPendingCustomSubmissions($product, $request);
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
            $product->proposed_pricing_page_url = null;
            $product->proposedCategories()->detach();
            $product->proposedTechStacks()->detach();
            $this->deleteProposedScreenshotFiles($product);
            $product->proposed_screenshot_path = null;
            $product->proposed_screenshot_thumb_path = null;
            $product->proposed_screenshot_medium_path = null;
            $product->has_pending_edits = false;

            // Update main product fields
            $product->update($updateData);
            $product->categories()->sync($newCategories);
            $product->techStacks()->sync($newTechStacks);
            $this->syncPendingCustomSubmissions($product, $request);

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
        $url = Product::normalizeLink($request->input('url'));
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
            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            $canEdit = Auth::check() && $user && ($user->id === $product->user_id || $user->hasRole('admin'));

            return response()->json([
                'exists' => true,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'can_edit' => $canEdit,
                    'edit_url' => $canEdit ? route('products.edit', $product) : null,
                    'view_url' => route('products.show', [
                        'product' => $product->slug,
                        'return_to' => route('products.create'),
                        'return_label' => 'Submit a Project',
                    ]),
                ],
            ]);
        }

        \Log::info('URL does not exist in database');
        return response()->json(['exists' => false]);
    }

    public function categoryProducts(Request $request, Category $category, AdDeliveryService $adDeliveryService)
    {
        $currentPage = max(1, (int) $request->query('page', 1));
        $perPage = 48;

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

        $promotedProducts = $currentPage === 1
            ? $promotedProductsQuery->get()
            : collect();

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

        $regularProducts = $regularProductsQuery
            ->orderByRaw('(votes_count + impressions) DESC')
            ->orderBy('name', 'asc')
            ->paginate($perPage)
            ->withQueryString();

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

        $adContext = $adDeliveryService->contextFromRequest($request, [
            'category_id' => $category->id,
            'page_type' => 'category',
        ]);
        $headerAd = $adDeliveryService->oneForZone('header-above-calendar', $adContext);
        $sidebarTopAds = $adDeliveryService->forZone('sidebar-top', $adContext);
        $belowProductListingPlacement = $adDeliveryService->placementForZone('below-product-listing', $adContext);
        $belowProductListingAd = $belowProductListingPlacement['ads']->first();
        $belowProductListingAdPosition = $belowProductListingPlacement['position'];

        $categories = Category::withCount([
            'products' => function ($query) {
                $query->where('approved', true)
                    ->where('is_published', true);
            }
        ])->orderBy('name')->get();

        $currentYear = Carbon::now()->year;
        $pageSuffix = $regularProducts->currentPage() > 1 ? ' - Page ' . $regularProducts->currentPage() : '';
        $title = "The Best " . strip_tags($category->name) . " Apps of " . $currentYear . $pageSuffix;
        $meta_title = "Best " . strip_tags($category->name) . " Tools & Software (" . $currentYear . ")" . $pageSuffix . " | Software on the Web";
        $isCategoryPage = true;
        $metaDescriptionBase = trim((string) ($category->meta_description ?: $category->description));
        if ($metaDescriptionBase === '') {
            $metaDescriptionBase = "Browse curated {$category->name} tools, ranked by the community on Software on the Web.";
        }
        $meta_description = $regularProducts->currentPage() > 1
            ? trim($metaDescriptionBase . ' Page ' . $regularProducts->currentPage() . '.')
            : $metaDescriptionBase;

        $premiumProducts = PremiumProduct::with('product.categories.types', 'product.user', 'product.userUpvotes')
            ->where('expires_at', '>', now())
            ->get()
            ->pluck('product')
            ->shuffle();

        $nextLaunchTime = $this->getNextLaunchTimeIso();

        return view('home', compact(
            'category',
            'categories',
            'types',
            'promotedProducts',
            'regularProducts',
            'premiumProducts',
            'headerAd',
            'sidebarTopAds',
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

        return redirect()->route('products.byWeek', [
            'year' => $date->year,
            'week' => $date->weekOfYear,
        ], 301);
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
        $year = (int) $year;
        $week = (int) $week;

        $this->ensureWeekArchiveRequestIsInRange($request, $year, $week);

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
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');

        $rankedRegularProductIds = $isFuture
            ? collect()
            : app(\App\Services\ProductRankingService::class)->organicWeekProductIds($startOfWeek, $endOfWeek);

        // Only check for missing products if this is not called from the home page
        // This prevents double redirects when home() method already handled the redirect
        if ($rankedRegularProductIds->isEmpty() && !$isHomepage && !$isFuture) {
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
                    return redirect()->route('products.byWeek', [
                        'year' => $lastAvailableWeek->year,
                        'week' => $lastAvailableWeek->weekOfYear,
                    ]);
                }

                abort(404);
            }
        }

        $totalProductsCount = $rankedRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } else {
                if (isset($rankedRegularProductIds[$regularProductIndex])) {
                    $finalProductOrder[] = $rankedRegularProductIds[$regularProductIndex];
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
            if (DB::connection()->getDriverName() === 'mysql') {
                $placeholders = implode(',', array_fill(0, count($regularProductIdsOnPage), '?'));
                $regularProductsOnPageQuery->orderByRaw("FIELD(id, $placeholders)", $regularProductIdsOnPage->all());
            } else {
                $caseStatements = $regularProductIdsOnPage
                    ->values()
                    ->map(fn ($id, $index) => "WHEN {$id} THEN {$index}")
                    ->implode(' ');

                $regularProductsOnPageQuery->orderByRaw("CASE id {$caseStatements} END");
            }
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
        $pageTitle = 'Best of Week ' . $week . ' of ' . $year . ' | ' . config('app.name', 'Software on the Web'); // For <title> tag
        $meta_title = $isHomepage
            ? 'Discover New SaaS Tools Every Week | ' . config('app.name', 'Software on the Web')
            : $pageTitle;
        $metaDescription = $isHomepage
            ? 'Discover new SaaS tools and software launches every week. Browse curated products across AI, productivity, design, and developer tools on Software on the Web.'
            : $this->buildArchiveMetaDescription('week', $startOfWeek, $endOfWeek, $combinedProducts->count());
        $shouldNoindexArchive = !$isHomepage;

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine

        $nextLaunchTime = $this->getNextLaunchTimeIso();

        $weekOfYear = $week;

        $weekNavigationItems = $this->buildWeekNavigationItems($year, $week);

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'meta_title', 'nextLaunchTime', 'weekOfYear', 'year', 'weekNavigationItems', 'startOfWeek', 'endOfWeek', 'isFuture', 'metaDescription', 'shouldNoindexArchive'));
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

        $promotedProducts = $this->promotedProductsForPeriod($startOfMonth, $endOfMonth);
        $rankedRegularProductIds = $isFuture
            ? collect()
            : app(\App\Services\ProductRankingService::class)->organicMonthProductIds($startOfMonth, $endOfMonth);

        [$regularProducts, $combinedProducts] = $this->buildPeriodPaginator($rankedRegularProductIds, $promotedProducts);

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfMonth->toDateString();
        $title = 'on ' . $startOfMonth->format('F Y'); // For potential in-page display
        $pageTitle = 'Best of ' . $startOfMonth->format('F Y') . ' | ' . config('app.name', 'Software on the Web'); // For <title> tag
        $metaDescription = $this->buildArchiveMetaDescription('month', $startOfMonth, $endOfMonth, $combinedProducts->count());

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine

        $nextLaunchTime = $this->getNextLaunchTimeIso();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'isFuture', 'metaDescription'));
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

        $promotedProducts = $this->promotedProductsForPeriod($startOfYear, $endOfYear);
        $rankedRegularProductIds = $isFuture
            ? collect()
            : app(\App\Services\ProductRankingService::class)->organicYearProductIds($startOfYear, $endOfYear);

        [$regularProducts, $combinedProducts] = $this->buildPeriodPaginator($rankedRegularProductIds, $promotedProducts);

        $categories = Category::all();
        $types = Type::with('categories')->get();
        $serverTodayDateString = Carbon::today()->toDateString();
        $displayDateString = $startOfYear->toDateString();
        $title = 'in ' . $year; // For potential in-page display
        $pageTitle = 'Best of ' . $year . ' | ' . config('app.name', 'Software on the Web'); // For <title> tag
        $metaDescription = $this->buildArchiveMetaDescription('year', $startOfYear, $endOfYear, $combinedProducts->count());

        $allProducts = $combinedProducts; // Use the combined and ordered list for Alpine

        $nextLaunchTime = $this->getNextLaunchTimeIso();

        return view('home', compact('regularProducts', 'categories', 'types', 'serverTodayDateString', 'displayDateString', 'title', 'pageTitle', 'nextLaunchTime', 'isFuture', 'metaDescription'));
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
        if (!$product->approved) {
            abort(404);
        }

        $isUnpublishedProduct = !$product->is_published;

        $product->load([
            'categories.types',
            'user',
            'techStacks',
            'media',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->whereRaw('1 = 0');
                }
            },
        ]);

        $pricingTypeNames = collect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING))
            ->map(fn(string $name) => Str::lower($name));
        $bestForTypeNames = collect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR))
            ->map(fn(string $name) => Str::lower($name));
        $useCaseTypeNames = collect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))
            ->map(fn(string $name) => Str::lower($name));
        $platformTypeNames = collect(CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM))
            ->map(fn(string $name) => Str::lower($name));

        $pricingCategory = $product->categories->first(function ($category) use ($pricingTypeNames) {
            return $category->types
                ->pluck('name')
                ->map(fn($typeName) => Str::lower((string) $typeName))
                ->intersect($pricingTypeNames)
                ->isNotEmpty();
        });
        $primaryBreadcrumbCategory = $product->primaryBreadcrumbCategory();

        $bestForCategories = $product->categories->filter(function ($category) use ($bestForTypeNames) {
            return $category->types
                ->pluck('name')
                ->map(fn($typeName) => Str::lower((string) $typeName))
                ->intersect($bestForTypeNames)
                ->isNotEmpty();
        });

        $useCaseCategories = $product->categories->filter(function ($category) use ($useCaseTypeNames) {
            return $category->types
                ->pluck('name')
                ->map(fn($typeName) => Str::lower((string) $typeName))
                ->intersect($useCaseTypeNames)
                ->isNotEmpty();
        });

        $platformCategories = $product->categories->filter(function ($category) use ($platformTypeNames) {
            return $category->types
                ->pluck('name')
                ->map(fn($typeName) => Str::lower((string) $typeName))
                ->intersect($platformTypeNames)
                ->isNotEmpty();
        });

        $productEditorialService = app(ProductEditorialContentService::class);
        $productEditorial = $productEditorialService->extract($product);
        $descriptionContent = $this->splitDescriptionForOverview(
            $product->description,
            $product->product_page_tagline,
            $product->tagline
        );
        $descriptionContent['details_html'] = $this->normalizeProductDetailHeadings($descriptionContent['details_html'] ?? null);
        $alternativeProducts = $this->relatedProductService->getAlternatives($product, 3)
            ->map(fn(Product $alternative) => $this->decorateProductDetailAlternative($alternative, $productEditorialService))
            ->values();
        $hasEditorialSections = $this->productEditorialHasSections($productEditorial);

        $title = $product->name;
        $pageTitle = $this->buildProductPageTitle($product, $primaryBreadcrumbCategory, $useCaseCategories, $bestForCategories);
        $metaDescription = $this->buildProductMetaDescription($product, $primaryBreadcrumbCategory, $useCaseCategories, $bestForCategories);
        $breadcrumbs = $this->buildProductBreadcrumbs($product, $primaryBreadcrumbCategory, request(), $isUnpublishedProduct);

        $allCategories = request()->routeIs('admin.*')
            ? Category::orderBy('name')->get()
            : collect();
        $currentUserClaim = null;
        $canClaimProduct = false;
        $productCollectionOptions = [];
        $isSavedByCurrentUser = false;

        if (Auth::check()) {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            $canClaimProduct = $currentUser->id !== $product->user_id && !$currentUser->hasRole('admin');

            $userCollections = $currentUser->productCollections()
                ->with(['items' => fn ($query) => $query->where('product_id', $product->id)])
                ->orderBy('name')
                ->get();

            if ($userCollections->isEmpty()) {
                $productCollectionOptions = collect(ProductCollection::defaultNames())
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
            } else {
                $productCollectionOptions = $userCollections->map(function (ProductCollection $collection) {
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

            $isSavedByCurrentUser = collect($productCollectionOptions)
                ->contains(fn (array $collection) => $collection['selected']);

            if ($canClaimProduct) {
                $currentUserClaim = ProductClaim::query()
                    ->where('product_id', $product->id)
                    ->where('user_id', $currentUser->id)
                    ->latest()
                    ->first();
            }
        }

        $response = response()->view('products.show', compact(
            'product',
            'title',
            'pageTitle',
            'pricingCategory',
            'breadcrumbs',
            'primaryBreadcrumbCategory',
            'metaDescription',
            'bestForCategories',
            'useCaseCategories',
            'platformCategories',
            'allCategories',
            'currentUserClaim',
            'canClaimProduct',
            'productCollectionOptions',
            'isSavedByCurrentUser',
            'productEditorial',
            'descriptionContent',
            'alternativeProducts',
            'hasEditorialSections',
            'isUnpublishedProduct'
        ));

        if ($isUnpublishedProduct) {
            $response->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }

    protected function buildProductBreadcrumbs(Product $product, ?Category $primaryBreadcrumbCategory, Request $request, bool $isUnpublishedProduct): array
    {
        if ($isUnpublishedProduct) {
            $returnBreadcrumb = $this->resolveUnpublishedProductReturnBreadcrumb($request, $product);

            if ($returnBreadcrumb !== null) {
                return [
                    $returnBreadcrumb,
                    ['label' => $product->name],
                ];
            }
        }

        $breadcrumbs = [];

        if ($primaryBreadcrumbCategory) {
            $breadcrumbs[] = [
                'label' => $primaryBreadcrumbCategory->name,
                'link' => route('categories.show', $primaryBreadcrumbCategory->slug),
            ];
        }

        $breadcrumbs[] = ['label' => $product->name];

        return $breadcrumbs;
    }

    protected function resolveUnpublishedProductReturnBreadcrumb(Request $request, Product $product): ?array
    {
        $returnUrl = $this->normalizeInternalBreadcrumbUrl(
            $request->query('return_to'),
            $request,
            $product
        );

        if ($returnUrl === null) {
            $returnUrl = $this->normalizeInternalBreadcrumbUrl(
                $request->headers->get('referer'),
                $request,
                $product
            );
        }

        if ($returnUrl === null) {
            return null;
        }

        $returnLabel = trim((string) $request->query('return_label'));
        $returnLabel = $returnLabel !== ''
            ? Str::limit(strip_tags($returnLabel), 60)
            : $this->resolveBreadcrumbLabelForUrl($returnUrl);

        return [
            'label' => $returnLabel !== '' ? $returnLabel : 'Back',
            'link' => $returnUrl,
        ];
    }

    protected function normalizeInternalBreadcrumbUrl(?string $url, Request $request, Product $product): ?string
    {
        if (!filled($url)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);
        $normalizedUrl = $path . ($query ? '?' . $query : '');
        $currentPath = route('products.show', ['product' => $product->slug], false);

        if ($path === $currentPath) {
            return null;
        }

        return $normalizedUrl;
    }

    protected function resolveBreadcrumbLabelForUrl(string $url): string
    {
        try {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            $query = parse_url($url, PHP_URL_QUERY);
            $routeRequest = Request::create($path . ($query ? '?' . $query : ''), 'GET');
            $route = app('router')->getRoutes()->match($routeRequest);

            return match ($route->getName()) {
                'products.my' => 'My Products',
                'products.create' => 'Submit a Project',
                'stripe.paid-submission.confirmation' => 'Payment Confirmation',
                default => 'Back',
            };
        } catch (\Throwable $exception) {
            return 'Back';
        }
    }

    protected function decorateProductDetailAlternative(Product $alternative, ProductEditorialContentService $productEditorialContentService): Product
    {
        $alternative->loadMissing('categories.types', 'techStacks');

        $editorial = $productEditorialContentService->extract($alternative);
        $softwareCategories = $this->categoryNamesForTypes($alternative, ['Software', 'Software Categories', 'Category']);
        $bestForCategories = $this->categoryNamesForTypes($alternative, ['Best for']);
        $pricingCategories = $this->categoryNamesForTypes($alternative, ['Pricing']);

        $fallbackTake = trim(strip_tags((string) ($alternative->product_page_tagline ?: $alternative->tagline ?: $alternative->description)));

        $alternative->setAttribute('primary_category_label', $softwareCategories[0] ?? null);
        $alternative->setAttribute('best_for_label', !empty($bestForCategories) ? implode(', ', array_slice($bestForCategories, 0, 2)) : null);
        $alternative->setAttribute('pricing_label', !empty($pricingCategories) ? implode(', ', array_slice($pricingCategories, 0, 2)) : 'Pricing not listed');
        $alternative->setAttribute('feature_highlights', array_slice($editorial['key_features'] ?? [], 0, 3));
        $alternative->setAttribute('editorial_take', $editorial['summary'] ?? $editorial['headline'] ?? Str::limit($fallbackTake, 160));

        return $alternative;
    }

    protected function productEditorialHasSections(array $productEditorial): bool
    {
        foreach (['key_features', 'ideal_for', 'top_use_cases', 'integrations', 'pros', 'limitations', 'faq'] as $key) {
            if (!empty($productEditorial[$key])) {
                return true;
            }
        }

        return false;
    }

    protected function splitDescriptionForOverview(?string $description, ?string $productPageTagline = null, ?string $tagline = null): array
    {
        $html = trim((string) $description);

        if ($html === '') {
            return [
                'overview_blocks' => [],
                'details_html' => null,
            ];
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="product-description-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        $root = $dom->getElementById('product-description-root');

        if (!$root) {
            return [
                'overview_blocks' => [],
                'details_html' => $html,
            ];
        }

        $contentRoot = $this->resolveDescriptionContentRoot($root);
        $children = collect(iterator_to_array($contentRoot->childNodes))
            ->filter(function ($node) {
                if ($node instanceof \DOMText) {
                    return trim((string) $node->textContent) !== '';
                }

                return true;
            })
            ->values()
            ->all();

        $overviewBlocks = [];
        $splitIndex = null;

        foreach ($children as $index => $node) {
            $text = $this->normalizeDescriptionComparisonText((string) $node->textContent);

            if ($text === '') {
                continue;
            }

            if ($this->descriptionTextMatchesAny($text, [$productPageTagline, $tagline])) {
                continue;
            }

            if (!$this->isOverviewDescriptionNode($node)) {
                continue;
            }

            $overviewBlocks[] = $dom->saveHTML($node);
            $splitIndex = $index;

            if (count($overviewBlocks) >= 2) {
                break;
            }
        }

        if (empty($overviewBlocks) || $splitIndex === null) {
            return [
                'overview_blocks' => [],
                'details_html' => $html,
            ];
        }

        $detailsHtml = '';

        for ($i = $splitIndex + 1; $i < count($children); $i++) {
            $detailsHtml .= $dom->saveHTML($children[$i]);
        }

        return [
            'overview_blocks' => $overviewBlocks,
            'details_html' => trim($detailsHtml) !== '' ? $detailsHtml : null,
        ];
    }

    protected function normalizeProductDetailHeadings(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="product-detail-heading-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        $root = $dom->getElementById('product-detail-heading-root');

        if (!$root) {
            return $html;
        }

        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('.//h1 | .//h2', $root) ?: [] as $headingNode) {
            if (!$headingNode instanceof \DOMElement || !$headingNode->parentNode) {
                continue;
            }

            $replacement = $dom->createElement('h3');

            foreach (iterator_to_array($headingNode->attributes ?? []) as $attribute) {
                $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue ?? '');
            }

            while ($headingNode->firstChild) {
                $replacement->appendChild($headingNode->firstChild);
            }

            $headingNode->parentNode->replaceChild($replacement, $headingNode);
        }

        $normalizedHtml = '';

        foreach (iterator_to_array($root->childNodes) as $childNode) {
            $normalizedHtml .= $dom->saveHTML($childNode);
        }

        return trim($normalizedHtml) !== '' ? trim($normalizedHtml) : null;
    }

    protected function buildProductPageTitle(
        Product $product,
        $primaryBreadcrumbCategory,
        \Illuminate\Support\Collection $useCaseCategories,
        \Illuminate\Support\Collection $bestForCategories
    ): string {
        $brandSuffix = ' | Software on the Web';
        $maxLength = 68;
        $name = $this->cleanSeoText($product->name);
        $tagline = $this->cleanSeoText($product->product_page_tagline ?: $product->tagline);
        $fullTitle = $tagline !== '' ? $name . ': ' . $tagline : $name;

        if (Str::length($fullTitle . $brandSuffix) <= $maxLength) {
            return $fullTitle . $brandSuffix;
        }

        if (Str::length($fullTitle) <= $maxLength) {
            return $fullTitle;
        }

        if ($tagline !== '') {
            $trimmedTitle = $name . ': ' . $this->trimSeoTextToLength(
                $tagline,
                max(0, $maxLength - Str::length($name) - 2)
            );

            if (Str::length($trimmedTitle) <= $maxLength) {
                return $trimmedTitle;
            }
        }

        if (Str::length($name . $brandSuffix) <= $maxLength) {
            return $name . $brandSuffix;
        }

        return $this->trimSeoTextToLength($name, $maxLength);
    }

    protected function buildProductMetaDescription(
        Product $product,
        $primaryBreadcrumbCategory,
        \Illuminate\Support\Collection $useCaseCategories,
        \Illuminate\Support\Collection $bestForCategories
    ): string {
        $maxLength = 155;
        $descriptionText = $this->cleanSeoText(strip_tags((string) $product->description));
        $taglineText = $this->cleanSeoText($product->product_page_tagline ?: $product->tagline);
        $name = $this->cleanSeoText($product->name);
        $topic = $this->firstProductSeoTopic($primaryBreadcrumbCategory, $useCaseCategories, $bestForCategories);

        $intro = $this->extractFirstSeoSentence($descriptionText, 110);

        if ($intro === '' && $taglineText !== '') {
            $intro = preg_match('/^' . preg_quote($name, '/') . '\b/i', $taglineText)
                ? $taglineText
                : $name . ': ' . $taglineText;
        }

        if ($intro === '') {
            $intro = $name . ' on Software on the Web.';
        }

        $intro = $this->ensureSentenceEnding($intro);
        $secondary = $topic !== '' ? 'Best for ' . $topic . '.' : 'See features, pricing, and alternatives.';
        $description = $intro;

        $shouldAppendSecondary = $secondary !== ''
            && ($topic === '' || !Str::contains(Str::lower($intro), Str::lower($topic)));

        if ($shouldAppendSecondary) {
            $candidate = trim($intro . ' ' . $secondary);

            if (Str::length($candidate) <= $maxLength) {
                return $candidate;
            }
        }

        return $this->ensureSentenceEnding($this->trimSeoTextToLength($description, $maxLength));
    }

    protected function firstProductSeoTopic(
        $primaryBreadcrumbCategory,
        \Illuminate\Support\Collection $useCaseCategories,
        \Illuminate\Support\Collection $bestForCategories
    ): string {
        $topic = $primaryBreadcrumbCategory?->name
            ?: $useCaseCategories->first()?->name
            ?: $bestForCategories->first()?->name
            ?: '';

        return $this->cleanSeoText($topic);
    }

    protected function cleanSeoText(?string $text): string
    {
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text ?? '');

        return trim((string) $text, " \t\n\r\0\x0B-–—:;,.");
    }

    protected function trimSeoTextToLength(string $text, int $maxLength): string
    {
        $text = $this->cleanSeoText($text);

        if ($maxLength <= 0 || $text === '') {
            return '';
        }

        if (Str::length($text) <= $maxLength) {
            return $text;
        }

        $slice = Str::substr($text, 0, $maxLength + 1);
        $trimmed = preg_replace('/\s+\S*$/u', '', $slice);
        $trimmed = $trimmed !== null && trim($trimmed) !== '' ? $trimmed : Str::substr($text, 0, $maxLength);

        return rtrim($trimmed, " \t\n\r\0\x0B-–—:;,.");
    }

    protected function extractFirstSeoSentence(string $text, int $maxLength = 110): string
    {
        $text = $this->cleanSeoText($text);

        if ($text === '') {
            return '';
        }

        if (preg_match('/^(.+?[.!?])(\s|$)/u', $text, $matches) === 1) {
            $sentence = $this->cleanSeoText($matches[1]);

            if ($sentence !== '') {
                return $this->trimSeoTextToLength($sentence, $maxLength);
            }
        }

        return $this->trimSeoTextToLength($text, $maxLength);
    }

    protected function ensureSentenceEnding(string $text): string
    {
        $text = rtrim($this->cleanSeoText($text));

        if ($text === '') {
            return '';
        }

        return preg_match('/[.!?]$/u', $text) ? $text : $text . '.';
    }

    protected function resolveDescriptionContentRoot(\DOMElement $root): \DOMElement
    {
        $elementChildren = collect(iterator_to_array($root->childNodes))
            ->filter(fn($node) => $node instanceof \DOMElement)
            ->values();

        if ($elementChildren->count() !== 1) {
            return $root;
        }

        /** @var \DOMElement $onlyChild */
        $onlyChild = $elementChildren->first();
        $childTag = strtolower($onlyChild->tagName);

        if (in_array($childTag, ['div', 'section', 'article'], true) && $this->elementHasMultipleRenderableChildren($onlyChild)) {
            return $onlyChild;
        }

        return $root;
    }

    protected function elementHasMultipleRenderableChildren(\DOMElement $element): bool
    {
        return collect(iterator_to_array($element->childNodes))
            ->filter(function ($node) {
                if ($node instanceof \DOMText) {
                    return trim((string) $node->textContent) !== '';
                }

                return $node instanceof \DOMElement;
            })
            ->count() > 1;
    }

    protected function isOverviewDescriptionNode($node): bool
    {
        if ($node instanceof \DOMText) {
            return trim((string) $node->textContent) !== '';
        }

        if (!$node instanceof \DOMElement) {
            return false;
        }

        return in_array(strtolower($node->tagName), ['p', 'div', 'blockquote'], true);
    }

    protected function descriptionTextMatchesAny(string $text, array $candidates): bool
    {
        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeDescriptionComparisonText((string) $candidate);

            if ($normalizedCandidate !== '' && $text === $normalizedCandidate) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeDescriptionComparisonText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return Str::lower(trim((string) $text));
    }

    protected function categoryNamesForTypes(Product $product, array $typeNames): array
    {
        $normalizedTypeNames = collect($typeNames)
            ->map(fn(string $typeName) => Str::lower($typeName))
            ->values();

        return $product->categories
            ->filter(function ($category) use ($normalizedTypeNames) {
                return $category->types
                    ->pluck('name')
                    ->map(fn($typeName) => Str::lower((string) $typeName))
                    ->intersect($normalizedTypeNames)
                    ->isNotEmpty();
            })
            ->pluck('name')
            ->values()
            ->all();
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

    protected function buildArchiveMetaDescription(string $period, Carbon $start, Carbon $end, int $productCount): string
    {
        $productLabel = $productCount === 1 ? '1 curated product' : number_format(max(0, $productCount)) . ' curated products';
        $dateRange = match ($period) {
            'day' => $start->format('F j, Y'),
            'week' => $start->format('M j') . '-' . $end->format('j, Y'),
            'month' => $start->format('F Y'),
            'year' => $start->format('Y'),
            default => $start->toDateString(),
        };

        $prefix = match ($period) {
            'day' => 'Discover the top software launches for ' . $dateRange . ' on Software on the Web.',
            'week' => 'Explore the best software from Week ' . $start->weekOfYear . ' of ' . $start->year . ' on Software on the Web.',
            'month' => 'Browse the best software launches from ' . $dateRange . ' on Software on the Web.',
            'year' => 'Browse the best software launches from ' . $dateRange . ' on Software on the Web.',
            default => 'Discover curated software launches on Software on the Web.',
        };

        $suffix = 'Review ' . $productLabel . ' across AI, productivity, and developer tools.';

        return $this->ensureSentenceEnding(
            $this->trimSeoTextToLength(trim($prefix . ' ' . $suffix), 155)
        );
    }

    protected function promotedProductsForPeriod(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        return Product::with([
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
            ->whereBetween(DB::raw('COALESCE(DATE(published_at), DATE(created_at))'), [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->orderBy('promoted_position', 'asc')
            ->get()
            ->keyBy('promoted_position');
    }

    protected function buildPeriodPaginator($rankedRegularProductIds, \Illuminate\Support\Collection $promotedProducts, int $perPage = 15): array
    {
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $offset = ($currentPage - 1) * $perPage;
        $totalProductsCount = $rankedRegularProductIds->count() + $promotedProducts->count();
        $finalProductOrder = [];
        $regularProductIndex = 0;

        for ($i = 1; $i <= $totalProductsCount; $i++) {
            if ($promotedProducts->has($i)) {
                $finalProductOrder[] = $promotedProducts->get($i);
            } elseif (isset($rankedRegularProductIds[$regularProductIndex])) {
                $finalProductOrder[] = $rankedRegularProductIds[$regularProductIndex];
                $regularProductIndex++;
            }
        }

        $currentPageItems = array_slice($finalProductOrder, $offset, $perPage);
        $regularProductIdsOnPage = collect($currentPageItems)->filter(fn ($item) => is_numeric($item))->values();

        $regularProductsOnPageQuery = Product::with([
            'categories.types',
            'user',
            'userUpvotes' => function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                }
            }
        ])->whereIn('id', $regularProductIdsOnPage);

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

        return [$regularProducts, $combinedProducts];
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

    private function ensureWeekArchiveRequestIsInRange(Request $request, int $year, int $week): void
    {
        $now = Carbon::now();

        if ($week < 1 || $week > 53) {
            $this->abortInvalidWeekArchiveRequest($request, $year, $week, 'invalid_week_number');
        }

        if ($year > $now->year + 1) {
            $this->abortInvalidWeekArchiveRequest($request, $year, $week, 'too_far_in_future');
        }

        $bounds = $this->publishedWeekArchiveBounds();

        if (!$bounds) {
            return;
        }

        if ($this->compareWeekArchives($year, $week, $bounds['earliest_year'], $bounds['earliest_week']) < 0) {
            $this->abortInvalidWeekArchiveRequest($request, $year, $week, 'before_first_published_week');
        }
    }

    private function publishedWeekArchiveBounds(): ?array
    {
        $dateExpressions = $this->productDateExpressions();

        $baseQuery = Product::approvedAndPublished()
            ->selectRaw($dateExpressions['year'] . ' as year, ' . $dateExpressions['week'] . ' as week');

        $earliestWeek = (clone $baseQuery)
            ->orderBy('year')
            ->orderBy('week')
            ->first();

        $latestWeek = (clone $baseQuery)
            ->orderByDesc('year')
            ->orderByDesc('week')
            ->first();

        if (!$earliestWeek || !$latestWeek) {
            return null;
        }

        return [
            'earliest_year' => (int) $earliestWeek->year,
            'earliest_week' => (int) $earliestWeek->week,
            'latest_year' => (int) $latestWeek->year,
            'latest_week' => (int) $latestWeek->week,
        ];
    }

    private function compareWeekArchives(int $leftYear, int $leftWeek, int $rightYear, int $rightWeek): int
    {
        if ($leftYear === $rightYear) {
            return $leftWeek <=> $rightWeek;
        }

        return $leftYear <=> $rightYear;
    }

    private function abortInvalidWeekArchiveRequest(Request $request, int $year, int $week, string $reason): never
    {
        Log::notice('Blocked week archive request outside the published range.', [
            'reason' => $reason,
            'year' => $year,
            'week' => $week,
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            'referer' => $request->headers->get('referer'),
        ]);

        throw new NotFoundHttpException();
    }

    private function buildWeekNavigationItems(int $selectedYear, int $selectedWeek): array
    {
        $now = Carbon::now();
        $dateExpressions = $this->productDateExpressions();

        return Product::approvedAndPublished()
            ->selectRaw($dateExpressions['year'] . ' as year, ' . $dateExpressions['week'] . ' as week')
            ->groupBy('year', 'week')
            ->orderBy('year')
            ->orderBy('week')
            ->get()
            ->map(function ($activeWeek) use ($selectedYear, $selectedWeek, $now) {
                $year = (int) $activeWeek->year;
                $week = (int) $activeWeek->week;

                return [
                    'year' => $year,
                    'week' => $week,
                    'url' => route('products.byWeek', ['year' => $year, 'week' => $week]),
                    'label' => 'Week ' . $week,
                    'isSelected' => $year === $selectedYear && $week === $selectedWeek,
                    'isCurrent' => $year === (int) $now->year && $week === (int) $now->weekOfYear,
                ];
            })
            ->values()
            ->all();
    }

    private function productDateExpressions(): array
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return [
                'year' => 'YEAR(COALESCE(published_at, created_at))',
                'week' => 'WEEK(COALESCE(published_at, created_at), 3)',
                'month' => 'MONTH(COALESCE(published_at, created_at))',
            ];
        }

        return [
            'year' => "CAST(strftime('%Y', COALESCE(published_at, created_at)) AS INTEGER)",
            'week' => "CAST(strftime('%W', COALESCE(published_at, created_at)) AS INTEGER)",
            'month' => "CAST(strftime('%m', COALESCE(published_at, created_at)) AS INTEGER)",
        ];
    }

    public function fetchUrlData(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
                $fallbackLogo = $this->productLogoResolver->discoverReplacementLogoUrl($url);

                if ($fallbackLogo) {
                    $logos[] = $fallbackLogo;
                }
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
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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

            $faviconUrl = $this->productLogoResolver->discoverReplacementLogoUrl($url);

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
            [
                'regularCategories' => $regularCategories,
                'useCaseCategories' => $useCaseCategories,
                'pricingCategories' => $pricingCategories,
                'bestForCategories' => $bestForCategories,
                'platformCategories' => $platformCategories,
            ] = $this->loadProductCategoryGroups(['id', 'name']);

        return response()->json([
            'categories' => $regularCategories,
            'useCases' => $useCaseCategories,
            'bestFor' => $bestForCategories,
            'pricing' => $pricingCategories,
            'platforms' => $platformCategories,
        ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch categories for API: ' . $e->getMessage());
            return response()->json(['error' => 'Could not retrieve categories.'], 500);
        }
    }

    protected function extractPotentialTaglinesFromDocument(DOMDocument $doc, ?string $productName = null): array
    {
        $cleanDoc = clone $doc;
        $xpath = new DOMXPath($cleanDoc);
        $noiseQuery = '//footer | //nav | //script | //style | //noscript | //aside'
            . ' | //video | //iframe | //form | //figure | //picture | //template'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "footer")]'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "cookie")]'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "banner")]'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "intercom")]'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "chat")]'
            . ' | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "widget")]'
            . ' | //*[contains(translate(@id, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "footer")]';

        foreach ($xpath->query($noiseQuery) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $candidates = [];

        foreach (['h1', 'h2', 'h3'] as $tag) {
            foreach ($cleanDoc->getElementsByTagName($tag) as $heading) {
                $value = $this->normalizeAutofillCandidateText($heading->nodeValue);

                if ($this->isWeakAutofillTaglineCandidate($value, $productName)) {
                    continue;
                }

                $candidates[] = $value;
            }
        }

        $elements = $xpath->query("//*[contains(@class, 'tagline') or contains(@class, 'slogan') or contains(@class, 'subtitle') or contains(@class, 'description') or contains(@class, 'headline') or contains(@class, 'hero')]");
        foreach ($elements as $element) {
            $value = $this->normalizeAutofillCandidateText($element->textContent);

            if ($this->isWeakAutofillTaglineCandidate($value, $productName)) {
                continue;
            }

            $candidates[] = $value;
        }

        return array_slice(array_values(array_unique($candidates)), 0, 20);
    }

    protected function buildHeuristicTaglines(?string $descriptionContent, string $title, array $potentialTaglines, ?string $productName = null): array
    {
        $allCandidates = array_filter(array_map(
            fn ($candidate) => $this->normalizeAutofillCandidateText((string) $candidate),
            array_merge([(string) $descriptionContent, trim($title)], $potentialTaglines)
        ));

        $allCandidates = array_values(array_filter(array_unique($allCandidates), function ($candidate) use ($productName) {
            return !$this->isWeakAutofillTaglineCandidate($candidate, $productName);
        }));

        $tagline = $this->selectBestAutofillTaglineCandidate($allCandidates, $productName, 140, true) ?? '';
        $remaining = array_values(array_filter($allCandidates, fn ($candidate) => $candidate !== $tagline));
        $detailed = $this->selectBestAutofillTaglineCandidate($remaining !== [] ? $remaining : $allCandidates, $productName, 160, false) ?? '';

        return [
            'tagline' => $tagline,
            'tagline_detailed' => $detailed,
        ];
    }

    protected function selectBestAutofillTaglineCandidate(array $candidates, ?string $productName = null, int $maxLength = 160, bool $preferShort = false): ?string
    {
        $scored = [];

        foreach ($candidates as $candidate) {
            $candidate = $this->normalizeAutofillCandidateText((string) $candidate);

            if ($this->isWeakAutofillTaglineCandidate($candidate, $productName)) {
                continue;
            }

            $scored[] = [
                'text' => Str::limit($candidate, $maxLength, '...'),
                'score' => $this->scoreAutofillTaglineCandidate($candidate, $preferShort),
            ];
        }

        if ($scored === []) {
            return null;
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $scored[0]['text'] ?? null;
    }

    protected function scoreAutofillTaglineCandidate(string $candidate, bool $preferShort = false): int
    {
        $score = 0;
        $length = mb_strlen($candidate);
        $wordCount = str_word_count($candidate);

        $targetLength = $preferShort ? 70 : 105;
        $score -= abs($length - $targetLength);

        if ($wordCount >= 5 && $wordCount <= 14) {
            $score += 35;
        } elseif ($wordCount >= 3 && $wordCount <= 18) {
            $score += 15;
        } else {
            $score -= 25;
        }

        if (preg_match('/\b(build|plan|track|manage|generate|execute|write|debug|ship|deploy|estimate|collaborate|code|launch|review|organize|automate)\b/i', $candidate)) {
            $score += 30;
        }

        if (preg_match('/[.!?]$/', $candidate)) {
            $score += 5;
        }

        if (preg_match('/\b(no credit card|required|setup in|deploy instantly|the future of)\b/i', $candidate)) {
            $score -= 20;
        }

        return $score;
    }

    protected function isWeakAutofillTaglineCandidate(?string $candidate, ?string $productName = null): bool
    {
        $candidate = $this->normalizeAutofillCandidateText((string) $candidate);
        $normalized = Str::lower($candidate);

        if ($candidate === '' || mb_strlen($candidate) < 12 || mb_strlen($candidate) > 220) {
            return true;
        }

        if (str_word_count($candidate) < 2) {
            return true;
        }

        if ($productName && Str::lower(trim($productName)) === $normalized) {
            return true;
        }

        if (preg_match('/[$%]|^\d+$/', $candidate)) {
            return true;
        }

        return in_array($normalized, [
            'platform',
            'agile',
            'community',
            'pricing',
            'legal',
            'solutions',
            'founders',
            'agencies',
            'marketing',
            'enterprise',
            'project managers',
            'designers',
            'dashboard',
            'backlog',
            'board',
            'roadmap',
            'reports',
            'qa mode',
            'site wide links',
            'site-wide links',
            'ready to build',
            'live preview',
            'start building',
            'get started',
            'try it free',
            'status',
            'terms',
            'privacy',
            'contact sales',
        ], true);
    }

    protected function normalizeAutofillCandidateText(?string $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return trim($value, " \t\n\r\0\x0B-");
    }

    protected function buildAiAutofillNotice(array $failures, string $fieldLabel, bool $isAdmin): ?string
    {
        if ($failures === []) {
            return null;
        }

        if (!$isAdmin) {
            return 'AI autofill is unavailable right now, so we used a structured fallback. You can continue editing manually.';
        }

        $parts = [];

        foreach ($failures as $failure) {
            $provider = match (strtolower((string) ($failure['provider'] ?? ''))) {
                'groq' => 'Groq',
                'gemini' => 'Gemini',
                'openrouter' => 'OpenRouter',
                default => 'AI provider',
            };

            $status = $failure['status'] ?? null;
            $body = (string) ($failure['body'] ?? '');
            $normalized = Str::lower($body);
            $part = null;

            if (str_contains($normalized, 'no ai provider key is set')) {
                $part = 'No AI provider key is configured.';
            } elseif ($status === 402 || $status === 429 || str_contains($normalized, 'rate_limit_exceeded') || str_contains($normalized, 'quota exceeded') || str_contains($normalized, 'resource_exhausted') || str_contains($normalized, 'credits')) {
                $part = $provider . ' quota or rate limit was reached';
                $retryAt = $this->extractAiRetryAt($body);

                if ($retryAt) {
                    $retryLabel = $retryAt->isSameDay(Carbon::now())
                        ? $retryAt->format('g:i A')
                        : $retryAt->format('M j, g:i A');
                    $part .= ' and may reset around ' . $retryLabel;
                }

                $part .= '.';
            } elseif ($status === 400) {
                $part = $provider . ' rejected the request with a 400 response.';
            } elseif ($status) {
                $part = $provider . ' returned HTTP ' . $status . '.';
            } elseif ($body !== '') {
                $part = $provider . ' error: ' . Str::limit($body, 140, '...');
            }

            if ($part) {
                $parts[] = $part;
            }
        }

        $parts = array_values(array_unique($parts));

        if ($parts === []) {
            return 'AI ' . $fieldLabel . ' generation failed, so fallback content was used.';
        }

        return 'AI ' . $fieldLabel . ' generation failed. ' . implode(' ', $parts) . ' Using fallback content for now.';
    }

    protected function extractAiRetryAt(string $body): ?Carbon
    {
        $seconds = $this->extractAiRetryDelaySeconds($body);

        if ($seconds === null) {
            return null;
        }

        return Carbon::now()->addSeconds($seconds);
    }

    protected function extractAiRetryDelaySeconds(string $body): ?int
    {
        if (preg_match('/"retryDelay"\s*:\s*"([^"]+)"/', $body, $matches) === 1) {
            return $this->parseRetryDelayToSeconds($matches[1]);
        }

        if (preg_match('/Please try again in ([0-9hms.\s]+)/i', $body, $matches) === 1) {
            return $this->parseRetryDelayToSeconds($matches[1]);
        }

        if (preg_match('/Please retry in ([0-9hms.\s]+)/i', $body, $matches) === 1) {
            return $this->parseRetryDelayToSeconds($matches[1]);
        }

        return null;
    }

    protected function parseRetryDelayToSeconds(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        preg_match_all('/(\d+(?:\.\d+)?)([hms])/i', $value, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        $seconds = 0.0;

        foreach ($matches as $match) {
            $amount = (float) $match[1];
            $unit = strtolower($match[2]);

            $seconds += match ($unit) {
                'h' => $amount * 3600,
                'm' => $amount * 60,
                default => $amount,
            };
        }

        return (int) ceil($seconds);
    }

    public function fetchInitialMetadata(Request $request)
    {
        // Increase maximum execution time for scraping
        set_time_limit(120);

        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $metadataResponse = $this->fetchMetadata($request);
        if ($metadataResponse->getStatusCode() !== 200) {
            return $metadataResponse;
        }
        $metadata = json_decode($metadataResponse->getContent(), true);

        // Extract additional information from the URL content
        $taglineDetailed = '';
        $autofillLinks = [
            'pricing_page_url' => null,
            'x_account' => null,
            'maker_links' => [],
        ];
        try {
            $response = Http::timeout(5)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->get($url);
            $html = $response->body();

            $doc = new DOMDocument();
            @$doc->loadHTML($html);
            $autofillLinks = $this->extractAutofillLinksFromDocument($doc, $url);

            $potentialTaglines = $this->extractPotentialTaglinesFromDocument($doc, $metadata['name'] ?? '');
            $taglineDetailed = $this->selectBestAutofillTaglineCandidate(
                $potentialTaglines,
                $metadata['name'] ?? '',
                160,
                false
            ) ?? '';

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
            'pricing_page_url' => $autofillLinks['pricing_page_url'],
            'x_account' => $autofillLinks['x_account'],
            'maker_links' => $autofillLinks['maker_links'],
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

    protected function extractAutofillLinksFromDocument(DOMDocument $doc, string $pageUrl): array
    {
        $pricingPageUrl = null;
        $xAccount = null;
        $makerLinks = [];
        $seenMakerLinks = [];
        $seenCandidates = [];

        foreach ($this->collectAutofillLinkCandidates($doc) as $candidate) {
            $resolvedUrl = $this->resolveAutofillUrl($pageUrl, $candidate['href']);
            if (!$resolvedUrl || isset($seenCandidates[$resolvedUrl])) {
                continue;
            }

            $seenCandidates[$resolvedUrl] = true;
            $text = $candidate['text'];

            if (!$pricingPageUrl && $this->isPricingLinkCandidate($resolvedUrl, $text)) {
                $pricingPageUrl = $resolvedUrl;
                continue;
            }

            if (!$xAccount && $this->isXProfileUrl($resolvedUrl)) {
                $xHandle = Product::normalizeXAccount($resolvedUrl);
                $xAccount = $xHandle ? (Product::xProfileUrl($xHandle) ?? $xHandle) : null;
                continue;
            }

            if ($this->isResourceLinkCandidate($pageUrl, $resolvedUrl, $text)) {
                if (!isset($seenMakerLinks[$resolvedUrl])) {
                    $makerLinks[] = $resolvedUrl;
                    $seenMakerLinks[$resolvedUrl] = true;
                }
            }
        }

        return [
            'pricing_page_url' => $pricingPageUrl,
            'x_account' => $xAccount,
            'maker_links' => array_slice($makerLinks, 0, 10),
        ];
    }

    protected function collectAutofillLinkCandidates(DOMDocument $doc): array
    {
        $xpath = new \DOMXPath($doc);
        $queries = [
            '//footer//a[@href] | //*[contains(translate(@class, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "footer")]//a[@href] | //*[contains(translate(@id, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz"), "footer")]//a[@href]',
            '//a[@href]',
        ];

        $candidates = [];

        foreach ($queries as $query) {
            foreach ($xpath->query($query) as $linkNode) {
                $href = trim((string) $linkNode->getAttribute('href'));
                if ($href === '') {
                    continue;
                }

                $text = trim(preg_replace('/\s+/', ' ', $linkNode->textContent ?? ''));
                $candidates[] = [
                    'href' => $href,
                    'text' => $text,
                ];
            }
        }

        return $candidates;
    }

    protected function resolveAutofillUrl(string $pageUrl, string $href): ?string
    {
        $href = trim($href);
        if ($href === '') {
            return null;
        }

        $lowerHref = strtolower($href);
        if (
            str_starts_with($lowerHref, '#') ||
            str_starts_with($lowerHref, 'javascript:') ||
            str_starts_with($lowerHref, 'mailto:') ||
            str_starts_with($lowerHref, 'tel:')
        ) {
            return null;
        }

        $pageParts = parse_url($pageUrl);
        if ($pageParts === false || empty($pageParts['host'])) {
            return null;
        }

        $scheme = $pageParts['scheme'] ?? 'https';
        $host = $pageParts['host'];
        $port = isset($pageParts['port']) ? ':' . $pageParts['port'] : '';
        $origin = "{$scheme}://{$host}{$port}";

        if (preg_match('~^https?://~i', $href)) {
            $absoluteUrl = $href;
        } elseif (str_starts_with($href, '//')) {
            $absoluteUrl = "{$scheme}:{$href}";
        } elseif (str_starts_with($href, '/')) {
            $absoluteUrl = $origin . $href;
        } else {
            $basePath = $pageParts['path'] ?? '/';
            $directory = str_ends_with($basePath, '/') ? $basePath : rtrim(dirname($basePath), '.');
            $directory = $directory === '/' ? '' : trim($directory, '/');
            $absoluteUrl = $origin . '/' . ltrim(($directory ? "{$directory}/" : '') . $href, '/');
        }

        $normalized = Product::normalizeLink($absoluteUrl);
        if (!is_string($normalized) || $normalized === '') {
            return null;
        }

        return explode('#', $normalized, 2)[0];
    }

    protected function isPricingLinkCandidate(string $url, string $text): bool
    {
        $haystack = strtolower($url . ' ' . $text);

        return str_contains($haystack, 'pricing')
            || str_contains($haystack, 'plans')
            || preg_match('/\bplan\b/', $haystack) === 1;
    }

    protected function isXProfileUrl(string $url): bool
    {
        return preg_match('~^https?://(?:www\.)?(?:x\.com|twitter\.com)/@?[A-Za-z0-9_]{1,15}(?:/)?$~i', $url) === 1;
    }

    protected function isResourceLinkCandidate(string $pageUrl, string $candidateUrl, string $text): bool
    {
        return SocialLinkValidator::isAllowedMakerLinkUrl($candidateUrl);
    }

    protected function normalizeAutofillHost(string $host): string
    {
        $host = strtolower(trim($host));

        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    protected function parseAdditionalResources(?string $rawInput): array
    {
        if (!is_string($rawInput) || trim($rawInput) === '') {
            return [
                'notes' => [],
                'urls' => [],
            ];
        }

        $notes = [];
        $urls = [];
        $seenEntries = [];

        foreach (preg_split('/\r\n|\r|\n/', $rawInput) ?: [] as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }

            $entryKey = Str::lower($entry);
            if (isset($seenEntries[$entryKey])) {
                continue;
            }

            $seenEntries[$entryKey] = true;
            $candidateUrl = $entry;

            if (!preg_match('~^https?://~i', $candidateUrl) && preg_match('/^[A-Za-z0-9.-]+\.[A-Za-z]{2,}(?:[\/?#].*)?$/', $candidateUrl)) {
                $candidateUrl = 'https://' . $candidateUrl;
            }

            if (filter_var($candidateUrl, FILTER_VALIDATE_URL)) {
                try {
                    $urls[] = PublicUrlGuard::sanitizePublicHttpUrl($candidateUrl);
                    continue;
                } catch (\InvalidArgumentException) {
                    // Fall through and keep the raw line as a note.
                }
            }

            $notes[] = $entry;
        }

        return [
            'notes' => array_slice($notes, 0, 8),
            'urls' => array_slice(array_values(array_unique($urls)), 0, 3),
        ];
    }

    protected function buildDocumentTextSnippet(DOMDocument $doc, int $bodyLimit = 1800): string
    {
        $parts = [];

        $titleNode = $doc->getElementsByTagName('title')->item(0);
        $title = trim((string) ($titleNode?->nodeValue ?? ''));
        if ($title !== '') {
            $parts[] = 'Title: ' . $title;
        }

        $descriptionContent = '';
        foreach ($doc->getElementsByTagName('meta') as $meta) {
            if (strtolower((string) $meta->getAttribute('name')) === 'description') {
                $descriptionContent = trim((string) $meta->getAttribute('content'));
                break;
            }
        }

        if ($descriptionContent !== '') {
            $parts[] = 'Meta Description: ' . $descriptionContent;
        }

        $cleanDoc = clone $doc;
        $cleanXpath = new DOMXPath($cleanDoc);
        $noiseQuery = '//nav | //header | //footer | //script | //style | //noscript | //aside'
            . ' | //video | //iframe | //form | //figure | //picture | //template'
            . ' | //*[contains(@class,"cookie") or contains(@class,"banner") or contains(@class,"intercom") or contains(@class,"chat") or contains(@class,"widget")]';

        foreach ($cleanXpath->query($noiseQuery) as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach (['h1', 'h2', 'h3'] as $tag) {
            foreach ($cleanDoc->getElementsByTagName($tag) as $node) {
                $text = trim((string) $node->textContent);
                if ($text !== '') {
                    $parts[] = strtoupper($tag) . ': ' . $text;
                }
            }
        }

        $bodyText = trim((string) ($cleanDoc->getElementsByTagName('body')->item(0)?->textContent ?? ''));
        if ($bodyText !== '') {
            $parts[] = 'Body: ' . mb_substr($bodyText, 0, $bodyLimit);
        }

        return implode("\n", $parts);
    }

    protected function buildAdditionalResourcesContext(?string $rawInput): string
    {
        $resources = $this->parseAdditionalResources($rawInput);
        if (empty($resources['notes']) && empty($resources['urls'])) {
            return '';
        }

        $sections = [];

        if (!empty($resources['notes'])) {
            $sections[] = "Admin notes:\n- " . implode("\n- ", $resources['notes']);
        }

        foreach ($resources['urls'] as $resourceUrl) {
            try {
                $response = Http::timeout(8)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ])->get($resourceUrl);

                if (!$response->successful()) {
                    continue;
                }

                $doc = new DOMDocument();
                @$doc->loadHTML($response->body());

                $snippet = $this->buildDocumentTextSnippet($doc);
                $sections[] = $snippet !== ''
                    ? "Additional resource URL: {$resourceUrl}\n{$snippet}"
                    : "Additional resource URL: {$resourceUrl}";
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch additional resource for add-product AI context.', [
                    'url' => $resourceUrl,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return implode("\n\n", $sections);
    }

    protected function appendLimitationResearchContext(string $context, string $productName, string $productUrl): string
    {
        try {
            $researchContext = app(ProductLimitationResearchService::class)->buildContext($productName, $productUrl);
        } catch (\Throwable $e) {
            Log::warning('Failed to build limitation research context for add-product AI flow.', [
                'product_name' => $productName,
                'url' => $productUrl,
                'error' => $e->getMessage(),
            ]);

            return $context;
        }

        if ($researchContext === '') {
            return $context;
        }

        return $context . "\n\nLIMITATION RESEARCH:\n" . $researchContext;
    }

    public function processUrlStream(Request $request)
    {
        set_time_limit(120);
        $isAdmin = (bool) ($request->user() && $request->user()->hasRole('admin'));

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($request, $isAdmin) {
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

            try {
                $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
            } catch (\InvalidArgumentException $e) {
                $sendUpdate($e->getMessage(), 0, ['error' => $e->getMessage()]);
                return;
            }

            $name = $request->input('name');
            $tagline = $request->input('tagline');
            $fetchContent = $request->input('fetch_content', true);
            $additionalResourcesContext = $this->buildAdditionalResourcesContext($request->input('additional_resources'));

            $description = '';
            $extractedTagline = '';
            $extractedTaglineDetailed = '';
            $taglineNotice = null;
            $descriptionNotice = null;

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
                        'useCases' => [],
                        'bestFor' => [],
                        'pricing' => [],
                        'platforms' => [],
                        'tech_stacks' => [],
                        'pricing_page_url' => null,
                        'x_account' => null,
                        'maker_links' => [],
                    ]);
                    return;
                }
                $htmlContent = $htmlResponse->body();
                $doc = new DOMDocument();
                @$doc->loadHTML($htmlContent);
                $autofillLinks = $this->extractAutofillLinksFromDocument($doc, $url);
                $sendUpdate('Website fetched successfully...', 15);

                if ($fetchContent) {
                    $sendUpdate('Analyzing page structure...', 20);

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

                    $potentialTaglines = $this->extractPotentialTaglinesFromDocument($doc, $name);

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
                    if ($additionalResourcesContext !== '') {
                        $textContent .= "\n\nADDITIONAL RESOURCES:\n" . $additionalResourcesContext;
                    }

                    $productNameForAI = trim((string) $name) !== ''
                        ? $name
                        : ($this->nameExtractor->extract($title ?: '', $url) ?: 'this product');
                    $descriptionContext = $this->appendLimitationResearchContext($textContent, $productNameForAI, $url);

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
                        $heuristicTaglines = $this->buildHeuristicTaglines($descriptionContent, trim($title), $potentialTaglines, $name);

                        if (empty($extractedTagline)) {
                            $extractedTagline = $heuristicTaglines['tagline'] ?? '';
                        }

                        if (empty($extractedTaglineDetailed)) {
                            $extractedTaglineDetailed = $heuristicTaglines['tagline_detailed'] ?? '';
                        }

                        if (isset($taglineRewriter) && $taglineRewriter instanceof \App\Services\TaglineRewriterService) {
                            $taglineNotice = $this->buildAiAutofillNotice($taglineRewriter->getFailures(), 'tagline', $isAdmin);
                        }
                    }

                    $extractedTagline = \Illuminate\Support\Str::limit($extractedTagline, 140, '...');
                    $extractedTaglineDetailed = \Illuminate\Support\Str::limit($extractedTaglineDetailed, 160, '...');

                    $sendUpdate('Taglines ready. Writing product description...', 55, [
                        'tagline' => $extractedTagline ?: $tagline,
                        'tagline_detailed' => $extractedTaglineDetailed,
                        'tagline_notice' => $taglineNotice,
                    ]);

                    $sendUpdate('Writing product description...', 65);
                    $rawDescForRewrite = $descriptionContent;
                    if (empty($rawDescForRewrite)) {
                        $rawDescForRewrite = implode('. ', array_filter(array_map('trim', array_slice($potentialTaglines, 0, 5))));
                    }

                    if (!empty($rawDescForRewrite) || !empty(trim($textContent))) {
                        $descRewriter = new \App\Services\DescriptionRewriterService();
                        $rewritten = $descRewriter->rewrite($productNameForAI, $rawDescForRewrite ?: 'No meta description available', $descriptionContext);
                        if ($rewritten) {
                            $description = $rewritten;
                        }

                        if ($descRewriter->usedFallback()) {
                            $descriptionNotice = $this->buildAiAutofillNotice($descRewriter->getFailures(), 'description', $isAdmin);
                        }
                    }

                    $sendUpdate('Description ready. Extracting pricing page, socials, and logos...', 72, [
                        'description' => $description,
                        'description_notice' => $descriptionNotice,
                    ]);
                }

                $sendUpdate('Extracting pricing page, socials, and logos...', 85);

                $logos = $this->logoExtractor->extract($url, $htmlContent);

                $sendUpdate('Logos and links ready. Classifying features and categories...', 88, [
                    'logos' => $logos,
                    'pricing_page_url' => $autofillLinks['pricing_page_url'],
                    'x_account' => $autofillLinks['x_account'],
                    'maker_links' => $autofillLinks['maker_links'],
                ]);

                $sendUpdate('Classifying features and categories...', 95);
                $classificationSource = $htmlContent;
                if ($additionalResourcesContext !== '') {
                    $classificationSource .= "\n\nADDITIONAL RESOURCES:\n" . $additionalResourcesContext;
                }
                $classificationResult = $this->categoryClassifier->classify($classificationSource);
                $categories = $classificationResult['categories'] ?? [];
                $useCases = $classificationResult['use_cases'] ?? [];
                $bestFor = $classificationResult['best_for'] ?? [];
                $pricing = $classificationResult['pricing'] ?? [];
                $platforms = $classificationResult['platforms'] ?? [];

                $categoryIds = !empty($categories) ? \App\Models\Category::whereIn('name', $categories)->pluck('id')->toArray() : [];
                $useCaseIds = !empty($useCases) ? \App\Models\Category::whereIn('name', $useCases)->pluck('id')->toArray() : [];
                $bestForIds = !empty($bestFor) ? \App\Models\Category::whereIn('name', $bestFor)->pluck('id')->toArray() : [];
                $pricingIds = !empty($pricing) ? \App\Models\Category::whereIn('name', $pricing)->pluck('id')->toArray() : [];
                $platformIds = !empty($platforms) ? \App\Models\Category::whereIn('name', $platforms)->pluck('id')->toArray() : [];
                $techStackIds = [];

                try {
                    $techStackNames = $this->techStackDetector->detect($url);
                    $techStackIds = !empty($techStackNames)
                        ? \App\Models\TechStack::whereIn('name', $techStackNames)->pluck('id')->toArray()
                        : [];
                } catch (\Throwable $techStackError) {
                    \Illuminate\Support\Facades\Log::warning('Tech stack detection failed during processUrlStream.', [
                        'url' => $url,
                        'error' => $techStackError->getMessage(),
                    ]);
                }

                // Find category names the classifier suggested but that don't exist in DB
                $matchedCategoryNames = !empty($categories) ? \App\Models\Category::whereIn('name', $categories)->pluck('name')->toArray() : [];
                $unmatchedCategories = array_values(array_diff($categories, $matchedCategoryNames));
                $matchedUseCaseNames = !empty($useCases) ? \App\Models\Category::whereIn('name', $useCases)->pluck('name')->toArray() : [];
                $unmatchedUseCases = array_values(array_diff($useCases, $matchedUseCaseNames));

                $sendUpdate('Categories ready. Finalizing extracted data and response...', 97, [
                    'categories' => $categoryIds,
                    'useCases' => $useCaseIds,
                    'bestFor' => $bestForIds,
                    'pricing' => $pricingIds,
                    'platforms' => $platformIds,
                    'tech_stacks' => $techStackIds,
                    'suggestedCategories' => $unmatchedCategories,
                    'suggestedUseCases' => $unmatchedUseCases,
                ]);

                $responseData = [
                    'description' => $description,
                    'logos' => $logos,
                    'tagline' => $extractedTagline ?: $tagline,
                    'tagline_detailed' => $extractedTaglineDetailed,
                    'tagline_notice' => $taglineNotice,
                    'description_notice' => $descriptionNotice,
                    'categories' => $categoryIds,
                    'useCases' => $useCaseIds,
                    'bestFor' => $bestForIds,
                    'pricing' => $pricingIds,
                    'platforms' => $platformIds,
                    'tech_stacks' => $techStackIds,
                    'suggestedCategories' => $unmatchedCategories,
                    'suggestedUseCases' => $unmatchedUseCases,
                    'screenshot_url' => $this->screenshotService->capture($url),
                    'pricing_page_url' => $autofillLinks['pricing_page_url'],
                    'x_account' => $autofillLinks['x_account'],
                    'maker_links' => $autofillLinks['maker_links'],
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
                    'useCases' => [],
                    'bestFor' => [],
                    'pricing' => [],
                    'platforms' => [],
                    'tech_stacks' => [],
                    'pricing_page_url' => null,
                    'x_account' => null,
                    'maker_links' => [],
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
        $isAdmin = (bool) ($request->user() && $request->user()->hasRole('admin'));

        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl((string) $url);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $name = $request->input('name');
        $tagline = $request->input('tagline');

        $fetchContent = $request->input('fetch_content', true);
        $additionalResourcesContext = $this->buildAdditionalResourcesContext($request->input('additional_resources'));

        $description = '';
        $extractedTagline = '';
        $extractedTaglineDetailed = '';
        $taglineNotice = null;
        $descriptionNotice = null;

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
                    'useCases' => [],
                    'bestFor' => [],
                    'pricing' => [],
                    'platforms' => [],
                    'tech_stacks' => [],
                    'pricing_page_url' => null,
                    'x_account' => null,
                    'maker_links' => [],
                ]);
            }
            $htmlContent = $htmlResponse->body();
            $doc = new DOMDocument();
            @$doc->loadHTML($htmlContent);
            $autofillLinks = $this->extractAutofillLinksFromDocument($doc, $url);

            if ($fetchContent) {
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
                $potentialTaglines = $this->extractPotentialTaglinesFromDocument($doc, $name);

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
                if ($additionalResourcesContext !== '') {
                    $textContent .= "\n\nADDITIONAL RESOURCES:\n" . $additionalResourcesContext;
                }

                $productNameForAI = trim((string) $name) !== ''
                    ? $name
                    : ($this->nameExtractor->extract($title ?: '', $url) ?: 'this product');
                $descriptionContext = $this->appendLimitationResearchContext($textContent, $productNameForAI, $url);

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
                    $heuristicTaglines = $this->buildHeuristicTaglines($descriptionContent, trim($title), $potentialTaglines, $name);

                    if (empty($extractedTagline)) {
                        $extractedTagline = $heuristicTaglines['tagline'] ?? '';
                    }

                    if (empty($extractedTaglineDetailed)) {
                        $extractedTaglineDetailed = $heuristicTaglines['tagline_detailed'] ?? '';
                    }

                    if (isset($taglineRewriter) && $taglineRewriter instanceof \App\Services\TaglineRewriterService) {
                        $taglineNotice = $this->buildAiAutofillNotice($taglineRewriter->getFailures(), 'tagline', $isAdmin);
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
                    $rewritten = $descRewriter->rewrite($productNameForAI, $rawDescForRewrite ?: 'No meta description available', $descriptionContext);
                    if ($rewritten) {
                        $description = $rewritten;
                    }

                    if ($descRewriter->usedFallback()) {
                        $descriptionNotice = $this->buildAiAutofillNotice($descRewriter->getFailures(), 'description', $isAdmin);
                    }
                }
            }

            // Extract Logos
            $logos = $this->logoExtractor->extract($url, $htmlContent);

            // Classify categories and bestFor from the HTML content
            $classificationSource = $htmlContent;
            if ($additionalResourcesContext !== '') {
                $classificationSource .= "\n\nADDITIONAL RESOURCES:\n" . $additionalResourcesContext;
            }
            $classificationResult = $this->categoryClassifier->classify($classificationSource);
            $categories = $classificationResult['categories'] ?? [];
            $useCases = $classificationResult['use_cases'] ?? [];
            $bestFor = $classificationResult['best_for'] ?? [];
            $pricing = $classificationResult['pricing'] ?? [];
            $platforms = $classificationResult['platforms'] ?? [];

            // Convert category names to IDs
            $categoryIds = [];
            $matchedCategoryNames = [];
            if (!empty($categories)) {
                $categoryIds = Category::whereIn('name', $categories)->pluck('id')->toArray();
                $matchedCategoryNames = Category::whereIn('name', $categories)->pluck('name')->toArray();
            }
            $unmatchedCategories = array_values(array_diff($categories, $matchedCategoryNames));

            $useCaseIds = [];
            $matchedUseCaseNames = [];
            if (!empty($useCases)) {
                $useCaseIds = Category::whereIn('name', $useCases)->pluck('id')->toArray();
                $matchedUseCaseNames = Category::whereIn('name', $useCases)->pluck('name')->toArray();
            }
            $unmatchedUseCases = array_values(array_diff($useCases, $matchedUseCaseNames));

            $bestForIds = [];
            if (!empty($bestFor)) {
                $bestForIds = Category::whereIn('name', $bestFor)->pluck('id')->toArray();
            }

            $pricingIds = [];
            if (!empty($pricing)) {
                $pricingIds = Category::whereIn('name', $pricing)->pluck('id')->toArray();
            }

            $platformIds = [];
            if (!empty($platforms)) {
                $platformIds = Category::whereIn('name', $platforms)->pluck('id')->toArray();
            }

            $techStackIds = [];
            try {
                $techStackNames = $this->techStackDetector->detect($url);
                $techStackIds = !empty($techStackNames)
                    ? \App\Models\TechStack::whereIn('name', $techStackNames)->pluck('id')->toArray()
                    : [];
            } catch (\Throwable $techStackError) {
                Log::warning('Tech stack detection failed during processUrl.', [
                    'url' => $url,
                    'error' => $techStackError->getMessage(),
                ]);
            }

            $responseData = [
                'description' => $description,
                'logos' => $logos,
                'tagline' => $extractedTagline ?: $tagline, // Use extracted tagline, fallback to provided tagline
                'tagline_detailed' => $extractedTaglineDetailed, // Use extracted detailed tagline
                'tagline_notice' => $taglineNotice,
                'description_notice' => $descriptionNotice,
                'categories' => $categoryIds,
                'useCases' => $useCaseIds,
                'bestFor' => $bestForIds,
                'pricing' => $pricingIds,
                'platforms' => $platformIds,
                'tech_stacks' => $techStackIds,
                'suggestedCategories' => $unmatchedCategories,
                'suggestedUseCases' => $unmatchedUseCases,
                'screenshot_url' => $this->screenshotService->capture($url),
                'pricing_page_url' => $autofillLinks['pricing_page_url'],
                'x_account' => $autofillLinks['x_account'],
                'maker_links' => $autofillLinks['maker_links'],
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
                'useCases' => [],
                'bestFor' => [],
                'pricing' => [],
                'platforms' => [],
                'tech_stacks' => [],
                'pricing_page_url' => null,
                'x_account' => null,
                'maker_links' => [],
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

    public function upvote(Request $request, Product $product, \App\Services\ProductMetricsService $metricsService)
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
            if ($product->votes_count > 1) {
                Product::withoutTimestamps(function () use ($product) {
                    $product->decrement('votes_count');
                });
            } else {
                Product::withoutTimestamps(function () use ($product) {
                    $product->update(['votes_count' => 1]);
                });
            }
            $metricsService->recordManualUpvote($product, -1);
            $isUpvoted = false;
        } else {
            // Add the upvote (toggle on)
            UserProductUpvote::create([
                'user_id' => $user->id,
                'product_id' => $product->id
            ]);
            Product::withoutTimestamps(function () use ($product) {
                $product->increment('votes_count');
            });
            $metricsService->recordManualUpvote($product, 1);
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
            // $file is already the absolute path to the downloaded image
            $absolutePath = $file;
            $mimeType = mime_content_type($file);
        } else {
            $mimeType = $file->getMimeType();
        }

        $type = Str::startsWith($mimeType, 'image') ? 'image' : 'video';
        $mediaPosition = $product->media()->count() + 1;
        $extension = $isExternalPath
            ? strtolower(pathinfo((string) $file, PATHINFO_EXTENSION)) ?: ($type === 'image' ? 'png' : 'bin')
            : (strtolower($file->getClientOriginalExtension()) ?: ($type === 'image' ? 'png' : 'bin'));
        $filename = ProductMediaSeo::productMediaFilename($product, $type === 'image' && $isExternalPath ? 'screenshot' : $type, $extension, $mediaPosition);
        $path = 'product_media/' . $filename;

        if ($isExternalPath) {
            Storage::disk('public')->put($path, file_get_contents($absolutePath));
        } else {
            $path = $file->storeAs('product_media', $filename, 'public');
            $absolutePath = Storage::disk('public')->path($path);
        }

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
            } catch (\Throwable $e) {
                // Some environments allow storing AVIF files but GD cannot decode them for resizing.
                // Keep the original media and skip derivative thumbnails instead of failing submission.
                \Illuminate\Support\Facades\Log::warning('Image resizing skipped: ' . $e->getMessage());
            }
        }

        $product->media()->create([
            'path' => $path,
            'path_thumb' => $pathThumb,
            'path_medium' => $pathMedium,
            'alt_text' => ProductMediaSeo::productMediaAltText(
                $product,
                $type === 'image' && $isExternalPath ? 'screenshot' : $type,
                $mediaPosition
            ),
            'type' => $type === 'image' && $isExternalPath ? 'screenshot' : $type,
        ]);
    }

    protected function syncPendingCustomSubmissions(Product $product, Request $request): void
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

    protected function resolveLogoPathFromInput(string $logoInput, string $productLink): ?string
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

            $resolvedLogoUrl = $this->productLogoResolver->resolvePreferredLogoUrl($productLink, $logoInput);

            if ($resolvedLogoUrl) {
                try {
                    return $storageService->storeRemoteUrl($resolvedLogoUrl) ?? $resolvedLogoUrl;
                } catch (\Throwable $throwable) {
                    Log::warning('Failed to localize remote product logo during update; keeping external URL.', [
                        'url' => $resolvedLogoUrl,
                        'error' => $throwable->getMessage(),
                    ]);

                    return $resolvedLogoUrl;
                }
            }
        }

        return null;
    }

    protected function replacePrimaryScreenshotFromUrl(Product $product, string $url, ImageManager $manager): void
    {
        $downloadedPath = $this->downloadMediaUrlToTemporaryPublicPath($url);

        if (!$downloadedPath) {
            return;
        }

        $this->replacePrimaryScreenshotMedia($product, Storage::disk('public')->path($downloadedPath), $manager, true);
        Storage::disk('public')->delete($downloadedPath);
    }

    protected function replacePrimaryScreenshotMedia(Product $product, $file, ImageManager $manager, bool $isExternalPath = false): void
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

    protected function storeProposedScreenshotFromUrl(Product $product, string $url, ImageManager $manager): void
    {
        $downloadedPath = $this->downloadMediaUrlToTemporaryPublicPath($url);

        if (!$downloadedPath) {
            return;
        }

        $this->storeProposedScreenshotMedia($product, Storage::disk('public')->path($downloadedPath), $manager, true);
        Storage::disk('public')->delete($downloadedPath);
    }

    protected function storeProposedScreenshotMedia(Product $product, $file, ImageManager $manager, bool $isExternalPath = false): void
    {
        $storedMedia = $this->storeScreenshotAsset($product, $file, $manager, $isExternalPath, 'proposed-');

        if (!$storedMedia) {
            return;
        }

        $this->deleteProposedScreenshotFiles($product);

        $product->proposed_screenshot_path = $storedMedia['path'];
        $product->proposed_screenshot_thumb_path = $storedMedia['path_thumb'];
        $product->proposed_screenshot_medium_path = $storedMedia['path_medium'];
    }

    protected function storeScreenshotAsset(Product $product, $file, ImageManager $manager, bool $isExternalPath = false, string $filenamePrefix = ''): ?array
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
            Log::warning('Image resizing skipped: ' . $e->getMessage());
        }

        return [
            'path' => $path,
            'path_thumb' => $pathThumb,
            'path_medium' => $pathMedium,
        ];
    }

    protected function downloadMediaUrlToTemporaryPublicPath(string $url): ?string
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
            Log::error('Failed to process media from URL: ' . $url . ' - ' . $e->getMessage());

            return null;
        }
    }

    protected function deleteProposedScreenshotFiles(Product $product): void
    {
        $this->deleteMediaFiles(
            $product->proposed_screenshot_path,
            $product->proposed_screenshot_thumb_path,
            $product->proposed_screenshot_medium_path
        );
    }

    protected function deleteMediaFiles(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path && !Str::startsWith($path, 'http')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function productCategoryGroupQueries(): array
    {
        return [
            'regularCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE));
            })->orderBy('name'),
            'useCaseCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE));
            })->orderBy('name'),
            'pricingCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING));
            })->orderBy('name'),
            'bestForCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::BEST_FOR));
            })->orderBy('name'),
            'platformCategories' => Category::whereHas('types', function ($query) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PLATFORM));
            })->orderBy('name'),
        ];
    }

    private function loadProductCategoryGroups(array $columns = ['*']): array
    {
        return collect($this->productCategoryGroupQueries())
            ->map(fn ($query) => $query->get($columns))
            ->all();
    }

    private function selectedCategoryIdsForType(Product $product, string $bucket, bool $useProposed = false): array
    {
        $relation = $useProposed ? $product->proposedCategories() : $product->categories();

        return $relation
            ->whereHas('types', function ($query) use ($bucket) {
                $query->whereIn('name', CategoryTypeRegistry::namesFor($bucket));
            })
            ->pluck('categories.id')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    private function isAdminAddProductSandboxEnabled(): bool
    {
        if (!Storage::disk('local')->exists('settings.json')) {
            return true;
        }

        $settings = json_decode(Storage::disk('local')->get('settings.json'), true);

        if (!is_array($settings)) {
            return true;
        }

        return (bool) ($settings['admin_add_product_sandbox_enabled'] ?? true);
    }

    protected function getNextLaunchTimeIso(): string
    {
        return ProductPublishSchedule::nextLaunchTime()->toIso8601String();
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
