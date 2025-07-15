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
        $publishOption = $request->input('publish_option', 'now');

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

        if ($product->user) {
            Log::info("ProductApprovalController: Approving product ID {$product->id} for user ID {$product->user->id}. Dispatching ProductApproved event.");
            event(new ProductApproved($product, $product->user));
        } else {
            Log::warning("ProductApprovalController: Product ID {$product->id} has no associated user. Cannot dispatch ProductApproved event.");
        }

        Artisan::call('products:publish-scheduled');

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
        $publishDates = $request->input('published_at', []);

        if (!empty($productIds)) {
            $approvedCount = 0;
            foreach ($productIds as $id) {
                $product = Product::find($id);
                if ($product) {
                    $product->approved = true;
                    $publishDate = $publishDates[$id] ?? null;

                    if (!empty($publishDate)) {
                        try {
                            $product->published_at = \Carbon\Carbon::parse($publishDate)->setTime(7, 0, 0);
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
            if ($approvedCount > 0) {
                Artisan::call('products:publish-scheduled');
                return back()->with('success', ' product(s) approved.');
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

        $product->load(['user', 'categories', 'proposedCategories']);
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
        } // If proposed_logo_path is null, it means user proposed to remove logo or didn't change it.
          // If they proposed removal, proposed_logo_path would be null. If they didn't touch it, it's also null.
          // If product->logo was set and proposed_logo_path is null (due to remove_logo in user form), this means the live logo should be cleared.
          // This logic assumes that if proposed_logo_path is null, the intention is to use no logo or keep existing if no change was proposed.
          // A more explicit "remove_live_logo_on_edit_approval" flag might be better if complex scenarios arise.
          // For now, if proposed_logo_path is null, we assume the user wants to remove the logo if one was there, or didn't propose a new one.
          // If `remove_logo` was checked, `proposed_logo_path` should have been set to null by the user update logic.
        
        // If proposed_logo_path is explicitly null (meaning user wanted to remove it or didn't propose one)
        // and there was a live logo, clear it.
        if (is_null($product->proposed_logo_path) && $product->logo && !Str::startsWith($product->logo, 'http')) {
             // This case handles if user explicitly removed a proposed logo, or if they submitted edits without touching the logo but had one.
             // If the user form had "remove_logo" checked, proposed_logo_path should be null.
             // If they want to remove the live logo, proposed_logo_path should be null.
             // If proposed_logo_path is null, it means no new logo was proposed.
             // If the user wants to remove the logo, the `proposed_logo_path` should be set to an empty string or a special marker,
             // or the `ProductController` update logic should set `proposed_logo_path = null` if `remove_logo` was checked.
             // Assuming `proposed_logo_path = null` means "remove current logo if one exists, or no new logo proposed".
            if ($product->logo && !Str::startsWith($product->logo, 'http')) {
                 Storage::disk('public')->delete($product->logo);
            }
            $product->logo = null;
        }


        $product->tagline = $product->proposed_tagline ?? $product->tagline; // Fallback to current if somehow proposed is null
        $product->description = $product->proposed_description ?? $product->description; // Fallback

        // Sync categories from proposed categories
        // Explicitly select categories.id to avoid ambiguity
        $product->categories()->sync($product->proposedCategories()->pluck('categories.id')->toArray());

        // Clear proposed data
        if ($product->proposed_logo_path && $product->proposed_logo_path !== $product->logo) { // Only delete if it was a distinct proposed file that is now live
             // This check is a bit tricky. If proposed_logo_path became the new product->logo, we don't delete it.
             // The proposed_logo_path field itself will be nulled out. The file itself is now the main logo.
        }
        $product->proposed_logo_path = null;
        $product->proposed_tagline = null;
        $product->proposed_description = null;
        $product->proposedCategories()->detach();
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

        // Clear proposed data
        $product->proposed_logo_path = null;
        $product->proposed_tagline = null;
        $product->proposed_description = null;
        $product->proposedCategories()->detach();
        $product->has_pending_edits = false;

        $product->save();

        return redirect()->route('admin.products.pending-edits.index')->with('success', 'Proposed product edits rejected.');
    }
}
