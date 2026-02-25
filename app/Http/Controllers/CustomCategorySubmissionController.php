<?php

namespace App\Http\Controllers;

use App\Models\CustomCategorySubmission;
use App\Models\Product;
use App\Models\Category;
use App\Models\TechStack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CustomCategorySubmissionController extends Controller
{
    public function submit(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:category,best_for,tech_stack',
            'name' => 'required|string|max:255',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check if user owns the product
        if ($product->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if the same custom category submission already exists for this product
        $existingSubmission = CustomCategorySubmission::where('product_id', $request->product_id)
            ->where('type', $request->type)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->where('status', 'pending')
            ->first();

        if ($existingSubmission) {
            return response()->json(['error' => 'This custom category is already pending for this product'], 400);
        }

        // Check if the same category already exists in the database (case-insensitive, trimmed)
        $existingCategory = null;
        if ($request->type === 'category' || $request->type === 'best_for') {
            $existingCategory = Category::whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])->first();
        } elseif ($request->type === 'tech_stack') {
            $existingCategory = TechStack::whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])->first();
        }

        if ($existingCategory) {
            return response()->json(['error' => 'A category with this name already exists'], 400);
        }

        // Check if user has already submitted 3 custom categories of this type for this product
        $existingCount = CustomCategorySubmission::where([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'status' => 'pending'
        ])->count();

        if ($existingCount >= 3) {
            return response()->json(['error' => 'Maximum 3 custom categories per type per product'], 400);
        }

        $submission = CustomCategorySubmission::create([
            'product_id' => $request->product_id,
            'type' => $request->type,
            'name' => $request->name,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'submission' => $submission
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'submission_id' => 'required|exists:custom_category_submissions,id',
        ]);

        $submission = CustomCategorySubmission::findOrFail($request->submission_id);

        // Check if user owns the associated product
        if ($submission->product->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($submission->status !== 'pending') {
            return response()->json(['error' => 'Cannot remove a category that is not pending'], 400);
        }

        $submission->delete();

        return response()->json(['success' => true]);
    }

    public function getPendingForProduct($productId)
    {
        $product = Product::findOrFail($productId);

        // Check if user owns the product
        if ($product->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pendingSubmissions = CustomCategorySubmission::where([
            'product_id' => $productId,
            'status' => 'pending'
        ])->get();

        return response()->json([
            'success' => true,
            'submissions' => $pendingSubmissions
        ]);
    }
}