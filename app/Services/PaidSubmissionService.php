<?php

namespace App\Services;

use App\Helpers\HtmlHelper;
use App\Jobs\FetchOgImage;
use App\Mail\PaidSubmissionPaidAdminNotification;
use App\Mail\PaymentConfirmation;
use App\Models\CustomCategorySubmission;
use App\Models\PaidSubmissionCheckout;
use App\Models\Product;
use App\Models\Type;
use App\Models\User;
use App\Notifications\ProductSubmitted;
use App\Support\CategoryTypeRegistry;
use App\Support\PremiumLaunchPricing;
use App\Support\ProductMediaSeo;
use App\Support\ProductPublishSchedule;
use App\Support\SocialLinkValidator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class PaidSubmissionService
{
    public const CURRENCY = 'usd';

    public function __construct(
        protected SlugService $slugService,
        protected ProductLogoResolver $productLogoResolver,
        protected ProductLogoStorageService $productLogoStorageService
    ) {
    }

    public function stageCheckoutFromRequest(Request $request, User $user): PaidSubmissionCheckout
    {
        $validated = $this->validateRequest($request);
        $validated['link'] = Product::normalizeLink($validated['link']);
        $validated['pricing_page_url'] = filled($validated['pricing_page_url'] ?? null)
            ? Product::normalizeLink($validated['pricing_page_url'])
            : null;
        $validated['product_page_tagline'] = filled(trim((string) ($validated['product_page_tagline'] ?? '')))
            ? trim((string) $validated['product_page_tagline'])
            : trim((string) $validated['tagline']);
        $validated['x_account'] = Product::normalizeXAccount($validated['x_account'] ?? null);

        if (Product::where('link', $validated['link'])->exists()) {
            throw ValidationException::withMessages([
                'link' => 'A product with this URL already exists. You cannot add the same product twice.',
            ]);
        }

        $this->assertRequiredCategoryGroups($request);

        $payload = [
            'name' => trim((string) $validated['name']),
            'tagline' => trim((string) $validated['tagline']),
            'product_page_tagline' => trim((string) $validated['product_page_tagline']),
            'description' => (string) ($validated['description'] ?? ''),
            'link' => $validated['link'],
            'categories' => array_values(array_map('intval', array_filter($request->input('categories', []), fn ($id) => filled($id)))),
            'custom_categories' => array_values($request->input('custom_categories', [])),
            'tech_stacks' => array_values(array_map('intval', array_filter($request->input('tech_stacks', []), fn ($id) => filled($id)))),
            'custom_tech_stacks' => array_values($request->input('custom_tech_stacks', [])),
            'maker_links' => array_values(array_filter($request->input('maker_links', []), fn ($link) => filled(trim((string) $link)))),
            'sell_product' => $request->boolean('sell_product', false),
            'asking_price' => $request->input('asking_price'),
            'pricing_page_url' => $validated['pricing_page_url'],
            'x_account' => $validated['x_account'],
            'additional_resources' => $request->input('additional_resources'),
            'video_url' => $request->input('video_url'),
            'paid_schedule_date' => $validated['paid_schedule_date'] ?? null,
            'stored_logo_path' => null,
            'logo_url' => null,
            'staged_media_paths' => [],
            'media_urls' => array_values(array_filter($request->input('media_urls', []))),
        ];

        if ($request->hasFile('logo')) {
            $payload['stored_logo_path'] = $this->productLogoStorageService->storeUploadedFile($request->file('logo'));
        } elseif ($request->filled('logo_url')) {
            $logoUrl = (string) $request->input('logo_url');
            $payload['logo_url'] = $this->normalizeStoredPublicAssetUrl($logoUrl) ?? $logoUrl;
        }

        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                if ($file instanceof UploadedFile) {
                    $payload['staged_media_paths'][] = $file->store('staged_paid_submission_media', 'public');
                }
            }
        }

        $checkout = PaidSubmissionCheckout::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status' => 'pending',
            'product_name' => $payload['name'],
            'product_link' => $payload['link'],
            'schedule_date' => $validated['paid_schedule_date'] ?? null,
            'amount_cents' => $this->priceInCents(),
            'currency' => self::CURRENCY,
            'submission_payload' => $payload,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        Log::info('Paid submission checkout staged.', [
            'checkout_id' => $checkout->id,
            'user_id' => $user->id,
            'product_name' => $checkout->product_name,
            'schedule_date' => $checkout->schedule_date?->toDateString(),
        ]);

        return $checkout;
    }

    public function fulfillCheckout(PaidSubmissionCheckout $checkout, ?string $paymentIntentId = null, ?string $eventId = null): array
    {
        $created = false;

        $product = DB::transaction(function () use ($checkout, $paymentIntentId, $eventId, &$created) {
            $lockedCheckout = PaidSubmissionCheckout::query()
                ->lockForUpdate()
                ->findOrFail($checkout->id);

            if ($lockedCheckout->status === 'completed' && $lockedCheckout->product_id) {
                return $lockedCheckout->product;
            }

            $payload = $lockedCheckout->submission_payload ?? [];
            $product = $this->createProductFromPayload($payload, $lockedCheckout);

            $lockedCheckout->forceFill([
                'product_id' => $product->id,
                'status' => 'completed',
                'stripe_payment_intent_id' => $paymentIntentId ?: $lockedCheckout->stripe_payment_intent_id,
                'stripe_event_id' => $eventId ?: $lockedCheckout->stripe_event_id,
                'paid_at' => $lockedCheckout->paid_at ?: now(),
                'processed_at' => now(),
                'failure_message' => null,
            ])->save();

            $created = true;

            return $product;
        });

        if ($created) {
            $admins = User::getAdmins();

            Notification::send($admins, new ProductSubmitted($product));

            $adminEmails = $admins
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($adminEmails)) {
                Mail::to($adminEmails)->send(new PaidSubmissionPaidAdminNotification($product, $checkout->fresh()));
            }

            Artisan::call('products:publish-scheduled');

            Log::info('Paid submission fulfilled.', [
                'checkout_id' => $checkout->id,
                'product_id' => $product->id,
            ]);
        }

        $this->sendBuyerReceiptIfNeeded($checkout);

        return [$product, $created];
    }

    public function markCheckoutFailed(PaidSubmissionCheckout $checkout, string $message): void
    {
        $checkout->forceFill([
            'status' => 'failed',
            'failure_message' => Str::limit($message, 1000),
        ])->save();

        Log::error('Paid submission fulfillment failed.', [
            'checkout_id' => $checkout->id,
            'message' => $message,
        ]);
    }

    public function priceInCents(): int
    {
        return PremiumLaunchPricing::cents();
    }

    public function validatePaidScheduleDate(?string $value, string $field = 'paid_schedule_date'): ?Carbon
    {
        if (!filled($value)) {
            return null;
        }

        try {
            $selectedDate = Carbon::createFromFormat('Y-m-d', (string) $value, 'UTC')->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $field => 'Please select a valid date.',
            ]);
        }

        $nextMonday = now()->startOfDay()->next(\Illuminate\Support\Carbon::MONDAY);
        $maxDate = now()->startOfDay()->addDays(60);

        if (!$selectedDate->isMonday()) {
            throw ValidationException::withMessages([
                $field => 'The premium launch date must be a Monday.',
            ]);
        }

        if ($selectedDate->lt($nextMonday)) {
            throw ValidationException::withMessages([
                $field => 'The premium launch date must be from next Monday or later.',
            ]);
        }

        if ($selectedDate->gt($maxDate)) {
            throw ValidationException::withMessages([
                $field => 'The premium launch date must be within 60 days.',
            ]);
        }

        return $selectedDate;
    }

    protected function validateRequest(Request $request): array
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tagline' => 'required|string|max:255',
            'product_page_tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'link' => 'required|url|max:255',
            'maker_links' => 'nullable|array',
            'maker_links.*' => [
                'nullable',
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
            'paid_schedule_date' => [
                'nullable',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    try {
                        $this->validatePaidScheduleDate($value, $attribute);
                    } catch (ValidationException $exception) {
                        foreach (($exception->errors()[$attribute] ?? []) as $message) {
                            $fail($message);
                        }
                    }
                },
            ],
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
            'logo_url' => 'nullable|string',
            'video_url' => 'nullable|string|max:2048',
            'media' => 'nullable|array|max:1',
            'media.*' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp,avif,mp4,mov,ogg,qt|max:20480',
            'media_urls' => 'nullable|array|max:1',
            'media_urls.*' => 'nullable|string|max:2048',
            'tech_stacks' => 'nullable|array',
            'tech_stacks.*' => 'exists:tech_stacks,id',
            'custom_tech_stacks' => 'nullable|array|max:3',
            'custom_tech_stacks.*.name' => 'required|string|max:100',
        ])->validate();
    }

    protected function assertRequiredCategoryGroups(Request $request): void
    {
        $pricingType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::PRICING))->with('categories')->first();
        $softwareType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::SOFTWARE))->with('categories')->first();
        $useCaseType = Type::whereIn('name', CategoryTypeRegistry::namesFor(CategoryTypeRegistry::USE_CASE))->with('categories')->first();
        $submittedCategories = is_array($request->input('categories')) ? $request->input('categories') : [];
        $selected = collect($submittedCategories)->map(fn ($id) => (int) $id);
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
            throw ValidationException::withMessages([
                'categories' => 'Please select at least one category from the Pricing group.',
            ]);
        }

        if ($softwareIds->count() && $selected->intersect($softwareIds)->isEmpty() && !$hasCustomSoftware) {
            throw ValidationException::withMessages([
                'categories' => 'Please select at least one category from the Software Categories group.',
            ]);
        }

        if ($useCaseIds->count() && $selected->intersect($useCaseIds)->isEmpty() && !$hasCustomUseCase) {
            throw ValidationException::withMessages([
                'categories' => 'Please select at least one use case.',
            ]);
        }
    }

    protected function createProductFromPayload(array $payload, PaidSubmissionCheckout $checkout): Product
    {
        $link = Product::normalizeLink($payload['link'] ?? null);
        if (Product::where('link', $link)->exists()) {
            throw new \RuntimeException('A product with this URL already exists.');
        }

        $existsCheck = fn ($slug) => Product::where('slug', $slug)->exists();
        $scheduleDateTime = $checkout->schedule_date
            ? ProductPublishSchedule::forDate($checkout->schedule_date)
            : null;

        $attributes = [
            'name' => $payload['name'],
            'slug' => $this->slugService->generateUniqueSlug($payload['name'], $existsCheck),
            'tagline' => $payload['tagline'],
            'product_page_tagline' => $payload['product_page_tagline'] ?: $payload['tagline'],
            'description' => $this->ensureProperParagraphStructure(HtmlHelper::addNofollowToLinks($payload['description'] ?? '')),
            'link' => $link,
            'maker_links' => $payload['maker_links'] ?? [],
            'sell_product' => (bool) ($payload['sell_product'] ?? false),
            'asking_price' => $payload['asking_price'] ?? null,
            'pricing_page_url' => filled($payload['pricing_page_url'] ?? null) ? Product::normalizeLink($payload['pricing_page_url']) : null,
            'x_account' => Product::normalizeXAccount($payload['x_account'] ?? null),
            'user_id' => $checkout->user_id,
            'votes_count' => 1,
            'approved' => true,
            'is_published' => $scheduleDateTime ? $scheduleDateTime->isPast() : false,
            'published_at' => $scheduleDateTime,
            'submission_type' => 'paid',
            'badge_verified' => false,
            'badge_verified_at' => null,
            'badge_consecutive_failures' => 0,
            'badge_placement_url' => null,
            'badge_warning_sent_at' => null,
        ];

        if (!empty($payload['stored_logo_path'])) {
            $attributes['logo'] = $payload['stored_logo_path'];
        } elseif (!empty($payload['logo_url'])) {
            $logoUrl = $this->normalizeStoredPublicAssetUrl($payload['logo_url']) ?? $payload['logo_url'];

            if (Str::startsWith((string) $logoUrl, 'data:image')) {
                $attributes['logo'] = $this->productLogoStorageService->storeDataUrl($logoUrl) ?? $logoUrl;
            } elseif (is_string($logoUrl) && filled($logoUrl)) {
                $resolvedLogoUrl = $this->productLogoResolver->resolvePreferredLogoUrl($link, $logoUrl);
                if ($resolvedLogoUrl) {
                    try {
                        $attributes['logo'] = $this->productLogoStorageService->storeRemoteUrl($resolvedLogoUrl) ?? $resolvedLogoUrl;
                    } catch (\Throwable $throwable) {
                        Log::warning('Failed to localize paid submission logo; keeping external URL.', [
                            'url' => $resolvedLogoUrl,
                            'error' => $throwable->getMessage(),
                        ]);
                        $attributes['logo'] = $resolvedLogoUrl;
                    }
                }
            }
        }

        $product = DB::transaction(function () use ($attributes, $payload) {
            $product = Product::create($attributes);
            $product->categories()->sync($payload['categories'] ?? []);

            if (!empty($payload['tech_stacks'])) {
                $product->techStacks()->sync($payload['tech_stacks']);
            }

            foreach (($payload['custom_categories'] ?? []) as $customCategory) {
                CustomCategorySubmission::create([
                    'product_id' => $product->id,
                    'type' => $customCategory['type'],
                    'name' => $customCategory['name'],
                    'status' => 'pending',
                ]);
            }

            foreach (($payload['custom_tech_stacks'] ?? []) as $customTechStack) {
                CustomCategorySubmission::create([
                    'product_id' => $product->id,
                    'type' => 'tech_stack',
                    'name' => $customTechStack['name'],
                    'status' => 'pending',
                ]);
            }

            return $product;
        });

        $manager = new ImageManager(new Driver());
        $hasMedia = false;

        foreach (($payload['staged_media_paths'] ?? []) as $stagedPath) {
            if (Storage::disk('public')->exists($stagedPath)) {
                $hasMedia = true;
                $this->processMediaItem($product, Storage::disk('public')->path($stagedPath), $manager, true);
            }
        }

        foreach (($payload['media_urls'] ?? []) as $url) {
            if (!$url) {
                continue;
            }

            $hasMedia = true;

            try {
                $normalizedStoredUrl = $this->normalizeStoredPublicAssetUrl($url);

                if ($normalizedStoredUrl && Storage::disk('public')->exists($normalizedStoredUrl)) {
                    $this->processMediaItem($product, Storage::disk('public')->path($normalizedStoredUrl), $manager, true);
                    continue;
                }

                $imageContents = Http::get($url)->body();
                $extension = 'jpg';
                if (str_contains($url, '.png')) {
                    $extension = 'png';
                }
                if (str_contains($url, '.webp')) {
                    $extension = 'webp';
                }

                $filename = Str::uuid() . '.' . $extension;
                $path = 'staged_paid_submission_media/' . $filename;
                Storage::disk('public')->put($path, $imageContents);

                $this->processMediaItem($product, Storage::disk('public')->path($path), $manager, true);
            } catch (\Throwable $throwable) {
                Log::error('Failed to process paid submission media URL.', [
                    'url' => $url,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        if (!$hasMedia) {
            FetchOgImage::dispatch($product);
        }

        return $product;
    }

    protected function normalizeStoredPublicAssetUrl(?string $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (str_starts_with($value, '/storage/')) {
            return ltrim(substr($value, strlen('/storage/')), '/');
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($value, $appUrl . '/storage/')) {
            return ltrim(substr($value, strlen($appUrl . '/storage/')), '/');
        }

        return null;
    }

    protected function sendBuyerReceiptIfNeeded(PaidSubmissionCheckout $checkout): void
    {
        $lockedCheckout = DB::transaction(function () use ($checkout) {
            $lockedCheckout = PaidSubmissionCheckout::query()
                ->with(['product', 'user'])
                ->lockForUpdate()
                ->findOrFail($checkout->id);

            if ($lockedCheckout->receipt_sent_at || !$lockedCheckout->product || !$lockedCheckout->user?->email) {
                return null;
            }

            Mail::to($lockedCheckout->user->email)->send(
                new PaymentConfirmation($lockedCheckout->product, $lockedCheckout->user, $lockedCheckout)
            );

            $lockedCheckout->forceFill([
                'receipt_sent_at' => now(),
            ])->save();

            return $lockedCheckout;
        });

        if ($lockedCheckout) {
            Log::info('Paid submission buyer receipt queued.', [
                'checkout_id' => $lockedCheckout->id,
                'product_id' => $lockedCheckout->product_id,
                'user_id' => $lockedCheckout->user_id,
            ]);
        }
    }

    protected function ensureProperParagraphStructure(?string $html): ?string
    {
        if (empty($html)) {
            return $html;
        }

        if (!preg_match('/<(p|div|h[1-6]|ul|ol|blockquote|pre|hr)/i', $html)) {
            $paragraphs = array_filter(explode("\n", $html));
            if (count($paragraphs) > 1) {
                $wrappedParagraphs = array_map(function ($paragraph) {
                    $paragraph = trim($paragraph);
                    return $paragraph ? "<p>{$paragraph}</p>" : '';
                }, $paragraphs);
                $html = implode('', $wrappedParagraphs);
            } else {
                $html = '<p>' . trim(strip_tags($html)) . '</p>';
            }
        }

        return $html;
    }

    protected function processMediaItem(Product $product, string $filePath, ImageManager $manager, bool $isExternalPath = false): void
    {
        $mimeType = mime_content_type($filePath);
        $type = Str::startsWith($mimeType, 'image') ? 'image' : 'video';
        $mediaPosition = $product->media()->count() + 1;
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) ?: ($type === 'image' ? 'png' : 'bin');
        $filename = ProductMediaSeo::productMediaFilename($product, $type === 'image' && $isExternalPath ? 'screenshot' : $type, $extension, $mediaPosition);
        $path = 'product_media/' . $filename;

        Storage::disk('public')->put($path, file_get_contents($filePath));
        $absolutePath = Storage::disk('public')->path($path);

        $pathThumb = null;
        $pathMedium = null;

        if ($type === 'image') {
            try {
                $baseFilename = basename($path);
                $directory = dirname($path);

                $imageThumb = $manager->read($absolutePath);
                $imageThumb->scale(width: 300);
                $pathThumb = $directory . '/thumb_' . $baseFilename;
                Storage::disk('public')->put($pathThumb, (string) $imageThumb->encode());

                $imageMedium = $manager->read($absolutePath);
                $imageMedium->scale(width: 800);
                $pathMedium = $directory . '/medium_' . $baseFilename;
                Storage::disk('public')->put($pathMedium, (string) $imageMedium->encode());
            } catch (\Throwable $throwable) {
                Log::warning('Image resizing skipped for paid submission.', [
                    'error' => $throwable->getMessage(),
                    'path' => $path,
                ]);
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
}
