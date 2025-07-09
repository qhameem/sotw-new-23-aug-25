<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleTagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ArticleTag::query();

        // Search
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('slug', 'like', "%{$searchTerm}%");
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

        $tags = $query->paginate(15)->appends($request->except('page'));

        return view('admin.articles.tags.index', compact('tags', 'sortBy', 'sortDirection'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.articles.tags.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:article_tags,name',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_tags', 'slug'),
            ],
        ]);

        $tag = new ArticleTag($validatedData);

        if (empty($validatedData['slug'])) {
            $tag->slug = Str::slug($validatedData['name']);
        } else {
            $tag->slug = Str::slug($validatedData['slug']); // Ensure slug is sanitized
        }
        
        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        $originalSlug = $tag->slug;
        $counter = 1;
        while (ArticleTag::where('slug', $tag->slug)->exists()) {
            $tag->slug = $originalSlug . '-' . $counter++;
        }

        $tag->save();

        return redirect()->route('admin.articles.tags.index')->with('success', 'Article tag created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ArticleTag $articleTag) // Route model binding
    {
        return view('admin.articles.tags.edit', compact('articleTag'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ArticleTag $articleTag) // Route model binding
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('article_tags', 'name')->ignore($articleTag->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('article_tags', 'slug')->ignore($articleTag->id),
            ],
        ]);

        $articleTag->fill($validatedData);

        if (empty($validatedData['slug'])) {
            $articleTag->slug = Str::slug($validatedData['name']);
        } else {
            $articleTag->slug = Str::slug($validatedData['slug']);
        }
        
        // Ensure slug uniqueness again after potential auto-generation or manual input sanitization
        if($articleTag->isDirty('slug')) {
            $originalSlug = $articleTag->slug;
            $counter = 1;
            while (ArticleTag::where('slug', $articleTag->slug)->where('id', '!=', $articleTag->id)->exists()) {
                $articleTag->slug = $originalSlug . '-' . $counter++;
            }
        }

        $articleTag->save();

        return redirect()->route('admin.articles.tags.index')->with('success', 'Article tag updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArticleTag $articleTag) // Route model binding
    {
        // Similar to categories, consider implications of deleting a tag.
        if ($articleTag->articles()->count() > 0) {
            return redirect()->route('admin.articles.tags.index')->with('error', 'Cannot delete tag with associated posts. Please remove tag from posts first.');
        }
        $articleTag->delete();
        return redirect()->route('admin.articles.tags.index')->with('success', 'Article tag deleted successfully.');
    }
}