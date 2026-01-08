<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Type; // Added for Category Types
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware(['auth', 'role:admin']);
    // }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Category::with('types');

        // Handle filtering by type
        if ($request->has('type') && !empty($request->type)) {
            $query->whereHas('types', function ($q) use ($request) {
                $q->where('types.id', $request->type);
            });
        }

        // Handle sorting by type
        if ($request->has('sort_by_type') && $request->sort_by_type === 'asc') {
            $query->whereHas('types')
                  ->withAggregate('types', 'name')
                  ->orderBy('types_name');
        } elseif ($request->has('sort_by_type') && $request->sort_by_type === 'desc') {
            $query->whereHas('types')
                  ->withAggregate('types', 'name')
                  ->orderBy('types_name', 'desc');
        } else {
            // Default sorting by category name
            $query->orderBy('name');
        }

        $categories = $query->get();
        $categoryTypes = Type::orderBy('name')->get(); // Fetch all category types
        $selectedType = $request->type ?? '';
        $sortByType = $request->sort_by_type ?? '';

        return view('admin.categories.index', compact('categories', 'categoryTypes', 'selectedType', 'sortByType'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categoryTypes = Type::orderBy('name')->get();
        return view('admin.categories.create', compact('categoryTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'category_types' => 'nullable|array',
            'category_types.*' => 'exists:types,id', // Ensure selected types exist
        ]);
        $category = Category::create($validated);
        $category->types()->sync($validated['category_types'] ?? []);

        // Eager load the types to get their names
        $category->load('types');
        $typeNames = $category->types->pluck('name')->join(', ');

        $message = "Category '{$category->name}'";
        if (!empty($typeNames)) {
            $message .= " in \"{$typeNames}\"";
        }
        $message .= " has been created successfully.";

        return redirect()->route('admin.categories.index')->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return view('admin.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $categoryTypes = Type::orderBy('name')->get();
        $assignedTypeIds = $category->types->pluck('id')->toArray();
        return view('admin.categories.edit', compact('category', 'categoryTypes', 'assignedTypeIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'meta_description' => 'nullable|string|max:255',
            'category_types' => 'nullable|array',
            'category_types.*' => 'exists:types,id', // Ensure selected types exist
        ]);
        $category->update($validated);
        $category->types()->sync($validated['category_types'] ?? []); // Sync types, pass empty array if none selected
        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return redirect()->route('admin.categories.index')->with('error', 'Cannot delete category: it is assigned to one or more products.');
        }
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}
