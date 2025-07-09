<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Type;
use App\Models\Category;

class CategoryTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $types = Type::with('categories')->get();
        $categories = Category::all();
        return view('admin.category_types.index', compact('types', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.category_types.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:types,name',
            'description' => 'nullable|string',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $type = Type::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null
        ]);
        if (!empty($validated['categories'])) {
            $type->categories()->sync($validated['categories']);
        }
        return redirect()->route('admin.category-types.index')->with('success', 'Type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Type $category_type)
    {
        $categories = Category::all();
        $type = $category_type;
        $assigned = $type->categories->pluck('id')->toArray();
        return view('admin.category_types.edit', compact('type', 'categories', 'assigned'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Type $category_type)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:types,name,' . $category_type->id,
            'description' => 'nullable|string',
            'categories' => 'array',
            'categories.*' => 'exists:categories,id',
        ]);
        $category_type->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null
        ]);
        $category_type->categories()->sync($validated['categories'] ?? []);
        return redirect()->route('admin.category-types.index')->with('success', 'Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Type $category_type)
    {
        $category_type->categories()->detach();
        $category_type->delete();
        return redirect()->route('admin.category-types.index')->with('success', 'Type deleted successfully.');
    }
}
