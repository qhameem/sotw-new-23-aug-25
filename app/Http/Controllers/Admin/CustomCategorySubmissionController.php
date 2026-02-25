<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomCategorySubmission;
use App\Models\Category;
use App\Models\TechStack;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomCategorySubmissionController extends Controller
{
    public function index()
    {
        $submissions = CustomCategorySubmission::with('product.user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.custom_category_submissions.index', compact('submissions'));
    }

    public function approve(Request $request, CustomCategorySubmission $submission)
    {
        $request->validate([
            'slug' => 'required|string|unique:categories,slug,' . ($submission->type === 'tech_stack' ? 'tech_stacks' : ''),
            'description' => 'required|string',
            'meta_description' => 'required|string',
        ]);

        if ($submission->type === 'category' || $submission->type === 'best_for') {
            // Create the actual category in the database
            $newCategory = Category::create([
                'name' => $submission->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'meta_description' => $request->meta_description,
            ]);

            // If it's a best_for type, we need to assign it to the correct type
            if ($submission->type === 'best_for') {
                $bestForTypeId = 3; // Assuming Best For type ID is 3
                $type = \App\Models\Type::find($bestForTypeId);
                if ($type) {
                    $newCategory->types()->attach($type->id);
                }
            } elseif ($submission->type === 'category') {
                $categoryId = 1; // Assuming Category type ID is 1
                $type = \App\Models\Type::find($categoryId);
                if ($type) {
                    $newCategory->types()->attach($type->id);
                }
            }

            // Associate the new category with the product
            $submission->product->categories()->attach($newCategory->id);

            // Auto-approve identical pending submissions
            $duplicates = CustomCategorySubmission::where('type', $submission->type)
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($submission->name))])
                ->where('status', 'pending')
                ->where('id', '!=', $submission->id)
                ->get();

            foreach ($duplicates as $duplicate) {
                $duplicate->product->categories()->attach($newCategory->id);
                $duplicate->update(['status' => 'approved']);
            }

        } elseif ($submission->type === 'tech_stack') {
            // Create the actual tech stack in the database
            $newTechStack = TechStack::create([
                'name' => $submission->name,
                'slug' => $request->slug,
            ]);

            // Associate the new tech stack with the product
            $submission->product->techStacks()->attach($newTechStack->id);

            // Auto-approve identical pending submissions
            $duplicates = CustomCategorySubmission::where('type', 'tech_stack')
                ->whereRaw('LOWER(name) = ?', [strtolower(trim($submission->name))])
                ->where('status', 'pending')
                ->where('id', '!=', $submission->id)
                ->get();

            foreach ($duplicates as $duplicate) {
                $duplicate->product->techStacks()->attach($newTechStack->id);
                $duplicate->update(['status' => 'approved']);
            }
        }

        // Update the submission status to approved
        $submission->update(['status' => 'approved']);

        return redirect()->back()->with('success', 'Custom category approved successfully.');
    }

    public function reject(CustomCategorySubmission $submission)
    {
        $submission->update(['status' => 'rejected']);

        return redirect()->back()->with('success', 'Custom category rejected successfully.');
    }
}