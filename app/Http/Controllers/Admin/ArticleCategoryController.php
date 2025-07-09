<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ArticleCategory::query();

        // Search
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('slug', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at'); // Default sort column
        $sortDirection = $request->input('sort_direction', 'desc'); // Default sort direction

        $validSortColumns = ['name', 'slug', 'created_at', 'articles_count'];
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->withCount('articles'); // Ensure count is loaded before ordering

        if ($sortBy === 'articles_count') {
            $query->orderBy('articles_count', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $categories = $query->paginate(15)->appends($request->except('page'));

        return view('admin.articles.categories.index', compact('categories', 'sortBy', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ArticleCategory::orderBy('name')->get();
        return view('admin.articles.categories.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:article_categories,name',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_categories', 'slug'),
            ],
            'description' => 'nullable|string|max:65535',
            'parent_id' => 'nullable|exists:article_categories,id',
        ]);

        $category = new ArticleCategory($validatedData);

        if (empty($validatedData['slug'])) {
            $category->slug = Str::slug($validatedData['name']);
        } else {
            $category->slug = Str::slug($validatedData['slug']); // Ensure slug is sanitized
        }
        
        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        $originalSlug = $category->slug;
        $counter = 1;
        while (ArticleCategory::where('slug', $category->slug)->exists()) {
            $category->slug = $originalSlug . '-' . $counter++;
        }

        $category->save();

        return redirect()->route('admin.articles.categories.index')->with('success', 'Article category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ArticleCategory $articleCategory) // Route model binding
    {
        $categories = ArticleCategory::where('id', '!=', $articleCategory->id) // Exclude self
                                    ->orderBy('name')
                                    ->get();
        return view('admin.articles.categories.edit', compact('articleCategory', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArticleCategory $articleCategory) // Route model binding
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('article_categories', 'name')->ignore($articleCategory->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_categories', 'slug')->ignore($articleCategory->id),
            ],
            'description' => 'nullable|string|max:65535',
            'parent_id' => [
                'nullable',
                'exists:article_categories,id',
                Rule::notIn([$articleCategory->id]), // Prevent setting itself as parent
            ],
        ]);

        // Handle case where parent_id is empty string, should be null
        if (isset($validatedData['parent_id']) && $validatedData['parent_id'] === '') {
            $validatedData['parent_id'] = null;
        }
        
        $articleCategory->fill($validatedData);

        if (empty($validatedData['slug'])) {
            $articleCategory->slug = Str::slug($validatedData['name']);
        } else {
            $articleCategory->slug = Str::slug($validatedData['slug']);
        }

        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        if($articleCategory->isDirty('slug')) {
            $originalSlug = $articleCategory->slug;
            $counter = 1;
            while (ArticleCategory::where('slug', $articleCategory->slug)->where('id', '!=', $articleCategory->id)->exists()) {
                $articleCategory->slug = $originalSlug . '-' . $counter++;
            }
        }
        
        $articleCategory->save();

        return redirect()->route('admin.articles.categories.index')->with('success', 'Article category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArticleCategory $articleCategory) // Route model binding
    {
        // Consider what happens to posts associated with this category.
        // By default, they won't be deleted due to foreign key constraints (unless cascade is set, which is not typical for category-post).
        // You might want to disallow deletion if posts are associated, or reassign them.
        // For now, simple deletion.
        if ($articleCategory->articles()->count() > 0) {
            return redirect()->route('admin.articles.categories.index')->with('error', 'Cannot delete category with associated posts. Please reassign or delete posts first.');
        }
        $articleCategory->delete();
        return redirect()->route('admin.articles.categories.index')->with('success', 'Article category deleted successfully.');
    }
}