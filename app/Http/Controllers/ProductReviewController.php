<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ProductReviewController extends Controller
{
    public function index()
    {
        $productReviews = ProductReview::latest()->paginate(20);

        return view('admin.product-reviews.index', compact('productReviews'));
    }

    public function create()
    {
        return view('product-reviews.create');
    }

    public function update(Request $request, ProductReview $productReview)
    {
        $validated = $request->validate([
            'is_done' => 'sometimes|boolean',
            'review_url' => 'nullable|url',
        ]);

        $productReview->update([
            'is_done' => $request->has('is_done'),
            'review_url' => $validated['review_url'] ?? null,
        ]);

        return back()->with('success', 'Product review updated successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_url' => 'required|url',
            'product_creator' => 'required|string',
            'email' => 'required|email',
            'access_instructions' => 'nullable|string',
            'other_instructions' => 'nullable|string',
        ]);

        $productReview = ProductReview::create($validated);

        // Mail to admin
        Mail::to(config('mail.from.address'))->send(new \App\Mail\ProductReviewSubmitted($productReview));

        return redirect()->route('product-reviews.create')->with('success', 'Thank you for your submission!');
    }
}
