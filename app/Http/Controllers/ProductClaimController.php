<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductClaim;
use App\Models\User;
use App\Notifications\ProductClaimSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductClaimController extends Controller
{
    public function create(Product $product): View
    {
        $this->ensureClaimableProduct($product);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_if($user->id === $product->user_id || $user->hasRole('admin'), 403);

        $existingClaim = ProductClaim::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        $autoEmailDomainMatch = ProductClaim::emailDomainMatchesProduct($user->email, $product->link);

        return view('products.claim', [
            'product' => $product,
            'existingClaim' => $existingClaim,
            'proofTypes' => ProductClaim::PROOF_TYPES,
            'autoEmailDomainMatch' => $autoEmailDomainMatch,
            'emailDomain' => ProductClaim::extractEmailDomain($user->email),
            'productHost' => ProductClaim::extractProductHost($product->link),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->ensureClaimableProduct($product);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        abort_if($user->id === $product->user_id || $user->hasRole('admin'), 403);

        $hasPendingClaim = ProductClaim::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->where('status', ProductClaim::STATUS_PENDING)
            ->exists();

        if ($hasPendingClaim) {
            return redirect()
                ->route('products.claim.create', $product)
                ->with('error', 'You already have a pending claim for this product.');
        }

        $validated = $request->validate([
            'proof_type' => ['required', Rule::in(array_keys(ProductClaim::PROOF_TYPES))],
            'proof_value' => ['nullable', 'string', 'max:5000'],
            'message_to_admin' => ['nullable', 'string', 'max:5000'],
        ]);

        $claim = ProductClaim::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'status' => ProductClaim::STATUS_PENDING,
            'proof_type' => $validated['proof_type'],
            'proof_value' => $validated['proof_value'] ?? null,
            'message_to_admin' => $validated['message_to_admin'] ?? null,
            'auto_email_domain_match' => ProductClaim::emailDomainMatchesProduct($user->email, $product->link),
        ]);

        $claim->load(['product', 'user']);

        $admins = User::getAdmins();
        foreach ($admins as $admin) {
            $admin->notify(new ProductClaimSubmitted($claim));
        }

        return redirect()
            ->route('products.claim.create', $product)
            ->with('success', 'Your claim has been submitted for admin review.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureClaimableProduct($product);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $claim = ProductClaim::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->where('status', ProductClaim::STATUS_PENDING)
            ->latest()
            ->firstOrFail();

        $claim->update([
            'status' => ProductClaim::STATUS_CANCELLED,
        ]);

        return redirect()
            ->route('products.claim.create', $product)
            ->with('success', 'Your pending claim was cancelled.');
    }

    private function ensureClaimableProduct(Product $product): void
    {
        abort_unless($product->approved && $product->is_published, 404);
    }
}
