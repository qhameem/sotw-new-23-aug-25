<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User; // Added for type hinting if needed, though Product model has user relationship
use Illuminate\Http\Request;
use App\Events\ProductApproved; // Added event
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log; // Added for logging
use Illuminate\Support\Facades\Storage; // Added for file operations
use Illuminate\Support\Str; // Added for string operations

class ProductApprovalController extends Controller
{
    public function index(Request $request)
    {
        $pendingProducts = Product::with(['user', 'categories'])->where('approved', false)->orderByDesc('id')->get();

        // Approved Products Logic
        $perPage = $request->input('per_page', 20);
        $sortBy = $request->input('sort_by', 'published_at'); // Default sort by published_at
        $sortDirection = $request->input('sort_direction', 'desc'); // Default sort direction

        $validSortColumns = ['name', 'published_at'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'published_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $approvedProductsQuery = Product::with(['user', 'categories'])
            ->where('approved', true)
            ->where(function ($query) {
                $query->where('is_published', true)
                    ->orWhere(function ($query) {
                        $query->where('is_published', false)
                            ->whereNotNull('published_at');
                    });
            });

        // Handle cases where published_at might be null for sorting
        if ($sortBy === 'published_at') {
            // For MySQL: Order by whether published_at is null, then by the date itself
            // For PostgreSQL: NULLS LAST / NULLS FIRST can be used directly
            // Assuming MySQL for broader compatibility with a common setup:
            $approvedProductsQuery->orderByRaw("ISNULL(published_at) {$sortDirection}, published_at {$sortDirection}");
        } else {
            $approvedProductsQuery->orderBy($sortBy, $sortDirection);
        }

        $approvedProducts = $approvedProductsQuery->paginate($perPage)->withQueryString();

        $settings = ['product_publish_time' => '07:00']; // Default value
        $fileContents = Storage::get('settings.json');
        if ($fileContents) {
            $decodedSettings = json_decode($fileContents, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSettings)) {
                $settings = array_merge($settings, $decodedSettings);
            } else {
                Log::warning('ProductApprovalController: settings.json exists but is invalid JSON or not an array. Using default settings.');
            }
        } else {
            Log::info('ProductApprovalController: settings.json not found. Using default settings.');
        }

        return view('admin.product_approvals.index', compact('pendingProducts', 'approvedProducts', 'perPage', 'sortBy', 'sortDirection', 'settings'));
    }

    public function approve(Request $request, Product $product)
    {
        $product->approved = true;
        $publishOption = $request->input('publish_option', 'specific_date'); // Default to specific_date

        switch ($publishOption) {
            case 'specific_date':
                $publishDate = $request->input('published_at');
                if (!empty($publishDate)) {
                    try {
                        $parsedDate = \Carbon\Carbon::parse($publishDate)->setTime(7, 0, 0);
                        $product->published_at = $parsedDate;

                        // If the selected date is today or in the past, publish immediately
                        if ($parsedDate->lte(now()->utc()->startOfDay())) {
                            $product->is_published = true;
                        } else {
                            // Otherwise, schedule for the future
                            $product->is_published = false;
                        }
                    } catch (\Exception $e) {
                        // Fallback to immediate publish if date parsing fails
                        Log::error("ProductApprovalController: Failed to parse published_at date '{$publishDate}'. Error: {$e->getMessage()}. Publishing immediately.");
                        $product->published_at = now()->utc();
                        $product->is_published = true;
                    }
                } else {
                    // If no specific date is provided (e.g., field was empty), publish immediately
                    Log::info("ProductApprovalController: No published_at date provided. Publishing immediately.");
                    $product->published_at = now()->utc();
                    $product->is_published = true;
                }
                break;
            case 'now':
                $product->published_at = now()->utc();
                $product->is_published = true;
                break;
            case 'next_launch':
                $settings = ['product_publish_time' => '07:00']; // Default value
                $fileContents = Storage::get('settings.json');
                if ($fileContents) {
                    $decodedSettings = json_decode($fileContents, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSettings)) {
                        $settings = array_merge($settings, $decodedSettings);
                    } else {
                        Log::warning('ProductApprovalController: settings.json exists but is invalid JSON or not an array in approve method. Using default settings.');
                    }
                } else {
                    Log::info('ProductApprovalController: settings.json not found in approve method. Using default settings.');
                }
                $publishTime = $settings['product_publish_time'] ?? '07:00'; // Use product_publish_time as per context
                [$hour, $minute] = explode(':', $publishTime);
                $now = now()->utc();
                $nextLaunch = now()->utc()->setTime($hour, $minute, 0);
                if ($now->gt($nextLaunch)) {
                    $nextLaunch->addDay();
                }
                $product->published_at = $nextLaunch;
                $product->is_published = false;
                break;
        }

        $product->save();

        // Clear homepage-related caches when a product is approved and published
        if ($product->is_published && $product->published_at) {
            // Clear the specific cache keys that would be affected by a new product
            \Illuminate\Support\Facades\Cache::forget('promoted_products_homepage');
            \Illuminate\Support\Facades\Cache::forget('all_categories_homepage');
            \Illuminate\Support\Facades\Cache::forget('all_types_homepage');
            \Illuminate\Support\Facades\Cache::forget('active_weeks_homepage');
            \Illuminate\Support\Facades\Cache::forget('all_categories');

            // Clear the general product list cache for this week and date to ensure new products appear
            $generalProductListCacheKey = 'product_list_week_' . $product->published_at->year . '_' . $product->published_at->weekOfYear . '_' . now()->toDateString();
            \Illuminate\Support\Facades\Cache::forget($generalProductListCacheKey);
        }

        if ($product->user) {
            Log::info("ProductApprovalController: Approving product ID {$product->id} for user ID {$product->user->id}. Dispatching ProductApproved event.");
            event(new ProductApproved($product, $product->user));
        } else {
            Log::warning("ProductApprovalController: Product ID {$product->id} has no associated user. Cannot dispatch ProductApproved event.");
        }

        return back()->with('success', 'Product approved.');
    }

    public function disapprove(Product $product)
    {
        $product->approved = false;
        $product->save();
        return back()->with('success', 'Product disapproved.');
    }

    public function bulkApprove(Request $request)
    {
        $productIds = $request->input('products', []);
        $bulkPublishDate = $request->input('bulk_published_at');

        if (!empty($productIds)) {
            $approvedCount = 0;
            $weeksToClear = []; // Track weeks that need cache clearing

            foreach ($productIds as $id) {
                $product = Product::find($id);
                if ($product) {
                    $product->approved = true;

                    if (!empty($bulkPublishDate)) {
                        try {
                            $product->published_at = \Carbon\Carbon::parse($bulkPublishDate)->setTime(7, 0, 0);
                            $product->is_published = false; // Scheduled
                        } catch (\Exception $e) {
                            $product->published_at = now()->utc();
                            $product->is_published = true; // Publish now on error
                        }
                    } else {
                        $product->published_at = now()->utc();
                        $product->is_published = true; // Publish now if no date
                    }
                    $product->save();

                    // Collect weeks that need cache clearing
                    if ($product->is_published && $product->published_at) {
                        $weekKey = $product->published_at->year . '_' . $product->published_at->weekOfYear;
                        $weeksToClear[$weekKey] = true;
                    }

                    // Dispatch event
                    if ($product->user) { // Ensure product has an associated user
                        Log::info("ProductApprovalController (Bulk): Approving product ID {$product->id} for user ID {$product->user->id}. Dispatching ProductApproved event.");
                        event(new ProductApproved($product, $product->user));
                    } else {
                        Log::warning("ProductApprovalController (Bulk): Product ID {$product->id} has no associated user. Cannot dispatch ProductApproved event.");
                    }
                    $approvedCount++;
                }
            }

            // Clear caches for affected weeks after all products are processed
            if (!empty($weeksToClear)) {
                \Illuminate\Support\Facades\Cache::forget('promoted_products_homepage');
                \Illuminate\Support\Facades\Cache::forget('all_categories_homepage');
                \Illuminate\Support\Facades\Cache::forget('all_types_homepage');
                \Illuminate\Support\Facades\Cache::forget('active_weeks_homepage');
                \Illuminate\Support\Facades\Cache::forget('all_categories');

                // Clear the general product list cache for each affected week
                foreach (array_keys($weeksToClear) as $weekKey) {
                    $generalProductListCacheKey = 'product_list_week_' . $weekKey . '_' . now()->toDateString();
                    \Illuminate\Support\Facades\Cache::forget($generalProductListCacheKey);
                }
            }

            if ($approvedCount > 0) {
                return back()->with('success', $approvedCount . ' product(s) approved.');
            }
        }
        return back()->with('success', 'No products selected or found for approval.');
    }

    public function pendingEditsIndex()
    {
        $productsWithPendingEdits = Product::with(['user', 'categories', 'proposedCategories'])
            ->where('approved', true) // Only approved products can have pending *edits*
            ->where('has_pending_edits', true)
            ->orderByDesc('updated_at') // Show most recently edited first
            ->get();

        return view('admin.product_approvals.pending_edits_index', compact('productsWithPendingEdits'));
    }

    public function showEditDiff(Product $product)
    {
        // Ensure the product actually has pending edits and is approved
        if (!$product->approved || !$product->has_pending_edits) {
            return redirect()->route('admin.products.pending-edits.index')->with('error', 'Product does not have pending edits or is not approved.');
        }

        $product->load(['user', 'categories', 'proposedCategories', 'lastEditor', 'techStacks', 'proposedTechStacks', 'media']);
        return view('admin.product_approvals.show_edit_diff', compact('product'));
    }

    public function approveEdits(Product $product)
    {
        if (!$product->approved || !$product->has_pending_edits) {
            return back()->with('error', 'Product does not have pending edits to approve or is not an approved product.');
        }

        // Update live data from proposed data
        if ($product->proposed_logo_path) {
            // Delete old live logo if it exists and is a stored file
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                Storage::disk('public')->delete($product->logo);
            }
            $product->logo = $product->proposed_logo_path;
        } elseif (isset($product->proposed_logo_path) && is_null($product->proposed_logo_path) && $product->logo && !Str::startsWith($product->logo, 'http')) {
            // Handle explicit removal
            Storage::disk('public')->delete($product->logo);
            $product->logo = null;
        }


        // Handle Name/Link changes and Slug regeneration
        if ($product->proposed_name || $product->proposed_link) {
            $nameForSlug = $product->proposed_name ?? $product->name;
            $linkForSlug = $product->proposed_link ?? $product->link;

            $product->name = $product->proposed_name ?? $product->name;
            $product->link = $product->proposed_link ?? $product->link;

            $existsCheck = function ($slug) use ($product) {
                return Product::where('slug', $slug)->where('id', '!=', $product->id)->exists();
            };

            // If name changed, regenerate slug from name. If only link changed, maybe regenerate from link?
            // Let's stick to the logic used in InlineUpdateController: Name takes precedence.
            $textForSlug = $nameForSlug;
            if ($product->proposed_link && !$product->proposed_name) {
                $textForSlug = $this->extractNameFromUrl($linkForSlug);
            }

            $product->slug = $this->slugService->generateUniqueSlug($textForSlug, $existsCheck);
        }

        $product->tagline = $product->proposed_tagline ?? $product->tagline;
        $product->product_page_tagline = $product->proposed_product_page_tagline ?? $product->product_page_tagline;
        $product->description = $product->proposed_description ?? $product->description;
        $product->video_url = $product->proposed_video_url ?? $product->video_url;
        $product->x_account = $product->proposed_x_account ?? $product->x_account;
        $product->sell_product = !is_null($product->proposed_sell_product) ? $product->proposed_sell_product : $product->sell_product;
        $product->asking_price = !is_null($product->proposed_asking_price) ? $product->proposed_asking_price : $product->asking_price;
        $product->maker_links = $product->proposed_maker_links ?? $product->maker_links;

        // Sync categories and tech stacks
        $product->categories()->sync($product->proposedCategories()->pluck('categories.id')->toArray());
        $product->techStacks()->sync($product->proposedTechStacks()->pluck('tech_stacks.id')->toArray());

        // Clear all proposed data
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

        $product->save();

        return redirect()->route('admin.products.pending-edits.index')->with('success', 'Product edits approved and applied.');
    }

    public function rejectEdits(Product $product)
    {
        if (!$product->approved || !$product->has_pending_edits) {
            return back()->with('error', 'Product does not have pending edits to reject.');
        }

        // Delete the proposed logo file if it exists
        if ($product->proposed_logo_path) {
            Storage::disk('public')->delete($product->proposed_logo_path);
        }

        // Clear all proposed data
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

        $product->save();

        return redirect()->route('admin.products.pending-edits.index')->with('success', 'Proposed product edits rejected.');
    }

    protected $slugService;

    public function __construct(\App\Services\SlugService $slugService)
    {
        $this->slugService = $slugService;
    }

    private function extractNameFromUrl($url)
    {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if (!$host)
                return $url;

            $name = str_replace('www.', '', $host);
            $parts = explode('.', $name);
            return $parts[0];
        } catch (\Exception $e) {
            return $url;
        }
    }
}
