<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductClaim;
use App\Notifications\ProductClaimApproved;
use App\Notifications\ProductClaimRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductClaimController extends Controller
{
    public function index(): View
    {
        return view('admin.product_claims.index', [
            'pendingClaims' => ProductClaim::query()
                ->with(['product.user', 'user'])
                ->where('status', ProductClaim::STATUS_PENDING)
                ->latest()
                ->get(),
            'recentlyReviewedClaims' => ProductClaim::query()
                ->with(['product', 'user', 'reviewer'])
                ->whereIn('status', [ProductClaim::STATUS_APPROVED, ProductClaim::STATUS_REJECTED, ProductClaim::STATUS_CANCELLED])
                ->latest('reviewed_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function approve(ProductClaim $productClaim): RedirectResponse
    {
        if (!$productClaim->isPending()) {
            return back()->with('error', 'This claim has already been reviewed.');
        }

        DB::transaction(function () use ($productClaim) {
            $productClaim->loadMissing(['product', 'user']);

            $productClaim->update([
                'status' => ProductClaim::STATUS_APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $product = $productClaim->product;
            $product->user_id = $productClaim->user_id;
            $product->save();

            ProductClaim::query()
                ->where('product_id', $productClaim->product_id)
                ->where('id', '!=', $productClaim->id)
                ->where('status', ProductClaim::STATUS_PENDING)
                ->get()
                ->each(function (ProductClaim $claim) {
                    $claim->update([
                        'status' => ProductClaim::STATUS_REJECTED,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                        'admin_note' => 'Another claim for this product was approved.',
                    ]);

                    $claim->user->notify(new ProductClaimRejected(
                        $claim,
                        'Your claim for ' . $claim->product->name . ' was closed because another claim was approved.'
                    ));
                });
        });

        $productClaim->refresh()->load(['product', 'user']);
        $productClaim->user->notify(new ProductClaimApproved($productClaim));

        return back()->with('success', 'Product claim approved and ownership reassigned.');
    }

    public function reject(ProductClaim $productClaim): RedirectResponse
    {
        if (!$productClaim->isPending()) {
            return back()->with('error', 'This claim has already been reviewed.');
        }

        $productClaim->update([
            'status' => ProductClaim::STATUS_REJECTED,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $productClaim->loadMissing(['product', 'user']);
        $productClaim->user->notify(new ProductClaimRejected($productClaim));

        return back()->with('success', 'Product claim rejected.');
    }
}
