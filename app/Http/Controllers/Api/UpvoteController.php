<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\UserProductUpvote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Added for logging
use Illuminate\Database\QueryException;

class UpvoteController extends Controller
{
    /**
     * Store a newly created upvote in storage.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $startTime = microtime(true);
        $user = Auth::user();
        // Use $product directly as it's resolved by route model binding
        Log::info("Upvote store: Start for Product ID {$product->id}, User ID " . ($user ? $user->id : 'Guest/Unknown'));

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // $product variable is already the resolved model from route binding.
        // No need to initialize or re-fetch it unless for locking.

        Log::info("Upvote store: Before beginTransaction. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $product->id]);
        DB::beginTransaction();
        try {
            // Lock the product row for update to prevent race conditions
            // Re-fetch the product to apply lockForUpdate. $product from binding is not locked.
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();
            Log::info("Upvote store: Product locked. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);

            // Check if already upvoted
            $existingUpvote = UserProductUpvote::where('user_id', $user->id)
                ->where('product_id', $lockedProduct->id)
                ->first();
            Log::info("Upvote store: After existingUpvote check. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id, 'found' => !!$existingUpvote]);

            if ($existingUpvote) {
                DB::rollBack();
                Log::warning("Upvote store: Product already upvoted. Rolling back. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id, 'user_id' => $user->id]);
                return response()->json(['message' => 'Product already upvoted.', 'votes_count' => $lockedProduct->votes_count], 409);
            }

            UserProductUpvote::create([
                'user_id' => $user->id,
                'product_id' => $lockedProduct->id,
                'created_at' => now(), // Ensure created_at is set
            ]);
            Log::info("Upvote store: After UserProductUpvote::create. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);

            $lockedProduct->increment('votes_count');
            // No need to Log::info for product->increment here, as votes_count is logged at the end of successful transaction.
            // $lockedProduct->refresh(); // Refresh to get the latest votes_count if needed before commit, but increment handles it.

            DB::commit();
            Log::info("Upvote store: After DB::commit. New count: {$lockedProduct->votes_count}. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);


            $finalTime = microtime(true) - $startTime;
            Log::info("Upvote store: End. Total time: " . $finalTime . "s", ['product_id' => $lockedProduct->id]);
            return response()->json([
                'message' => 'Product upvoted successfully.',
                'votes_count' => $lockedProduct->votes_count // Use the locked and updated product's count
            ], 201);

        } catch (QueryException $e) {
            DB::rollBack();
            $errorTime = microtime(true) - $startTime;
            // Check for unique constraint violation (MySQL error code for duplicate entry is 23000)
            // Use $product->id for logging if $lockedProduct is not set due to an early error.
            $productIdForLog = isset($lockedProduct) ? $lockedProduct->id : $product->id;
            if ($e->errorInfo[1] == 1062 || str_contains(strtolower($e->getMessage()), 'duplicate entry')) { // 1062 is MySQL specific for duplicate entry
                 Log::warning("Upvote store: Attempted to create duplicate upvote (race condition or bypassed initial check). SQLSTATE: {$e->errorInfo[0]}. Error Code: {$e->errorInfo[1]}. Time elapsed: " . $errorTime . "s", [
                    'product_id' => $productIdForLog,
                    'user_id' => $user->id,
                    'exception_message' => $e->getMessage()
                ]);
                // Fetch current votes_count if possible, otherwise use the initial product's count
                $currentVotesCount = isset($lockedProduct) ? $lockedProduct->votes_count : Product::find($product->id)?->votes_count ?? $product->votes_count;
                return response()->json(['message' => 'Product already upvoted.', 'votes_count' => $currentVotesCount], 409);
            }
            Log::error('Upvote store QueryException: ' . $e->getMessage() . ". Total time before error: " . $errorTime . "s", [
                'product_id' => $productIdForLog,
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to upvote product due to a database error. Please try again.'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorTime = microtime(true) - $startTime;
            $productIdForLog = isset($lockedProduct) ? $lockedProduct->id : $product->id;
            Log::error('Upvote store general error: ' . $e->getMessage() . ". Total time before error: " . $errorTime . "s", [
                'product_id' => $productIdForLog,
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to upvote product. Please try again.'], 500);
        }
    }

    /**
     * Remove the specified upvote from storage.
     */
    public function destroy(Product $product): JsonResponse // Changed $productRouteBinding to $product
    {
        $startTime = microtime(true);
        $user = Auth::user();
        Log::info("Upvote destroy: Start for Product ID {$product->id}, User ID " . ($user ? $user->id : 'Guest/Unknown'));

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // $product variable is already the resolved model.
        // No need to initialize or re-fetch it unless for locking.

        Log::info("Upvote destroy: Before beginTransaction. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $product->id]);
        DB::beginTransaction();
        try {
            // Lock the product row for update
            // Re-fetch the product to apply lockForUpdate.
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();
            Log::info("Upvote destroy: Product locked. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);

            $upvote = UserProductUpvote::where('user_id', $user->id)
                ->where('product_id', $lockedProduct->id)
                ->first();
            Log::info("Upvote destroy: After upvote check. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id, 'found' => !!$upvote]);

            if (!$upvote) {
                DB::rollBack();
                Log::warning("Upvote destroy: Product not upvoted by user. Rolling back. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id, 'user_id' => $user->id]);
                return response()->json(['message' => 'Product not upvoted by this user.', 'votes_count' => $lockedProduct->votes_count], 404);
            }

            $upvote->delete();
            Log::info("Upvote destroy: After upvote->delete. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);

            // Decrement votes_count, ensuring it doesn't go below zero
            if ($lockedProduct->votes_count > 0) {
                $lockedProduct->decrement('votes_count');
            } else {
                // This case should ideally not be reached if data is consistent,
                // but as a safeguard, ensure votes_count is not negative.
                // No need to save if it's already 0 and we are not decrementing.
                if ($lockedProduct->votes_count < 0) { // Only update if it's somehow negative
                    $lockedProduct->votes_count = 0;
                    $lockedProduct->save();
                    Log::warning("Upvote destroy: Product votes_count was negative. Reset to 0.", ['product_id' => $lockedProduct->id, 'original_votes_count' => $lockedProduct->getOriginal('votes_count')]);
                }
            }
            // $lockedProduct->refresh(); // Refresh to get the latest votes_count if needed before commit

            DB::commit();
            Log::info("Upvote destroy: After DB::commit. New count: {$lockedProduct->votes_count}. Time elapsed: " . (microtime(true) - $startTime) . "s", ['product_id' => $lockedProduct->id]);


            $finalTime = microtime(true) - $startTime;
            Log::info("Upvote destroy: End. Total time: " . $finalTime . "s", ['product_id' => $lockedProduct->id]);
            return response()->json([
                'message' => 'Upvote removed successfully.',
                'votes_count' => $lockedProduct->votes_count // Use the locked and updated product's count
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            $errorTime = microtime(true) - $startTime;
            $productIdForLog = isset($lockedProduct) ? $lockedProduct->id : $product->id;
            Log::error('Upvote destroy error: ' . $e->getMessage() . ". Total time before error: " . $errorTime . "s", [
                'product_id' => $productIdForLog,
                'user_id' => $user->id,
                'exception' => $e
            ]);
            return response()->json(['message' => 'Failed to remove upvote. Please try again.'], 500);
        }
    }
}
