<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TechStack;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechStackController extends Controller
{
    public function index()
    {
        $techStacks = TechStack::orderBy('name')->paginate(20);

        return view('admin.tech-stacks.index', compact('techStacks'));
    }
    
    public function create()
    {
        return view('admin.tech-stacks.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tech_stacks,name',
            'icon' => 'nullable|string|max:255',
        ]);

        $techStack = new TechStack();
        $techStack->name = $request->name;
        $techStack->slug = Str::slug($request->name);
        $techStack->icon = $request->icon;
        $techStack->save();

        return redirect()->route('admin.tech-stacks.index')->with('status', 'Tech stack created successfully!');
    }
    
    public function edit(TechStack $tech_stack)
    {
        return view('admin.tech-stacks.edit', compact('tech_stack'));
    }

    public function update(Request $request, TechStack $tech_stack)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:tech_stacks,name,' . $tech_stack->id,
            'icon' => 'nullable|string|max:255',
        ]);

        $tech_stack->name = $request->name;
        $tech_stack->slug = Str::slug($request->name);
        $tech_stack->icon = $request->icon;
        $tech_stack->save();

        return redirect()->route('admin.tech-stacks.index')->with('status', 'Tech stack updated successfully!');
    }

    public function destroy(TechStack $tech_stack)
    {
        $tech_stack->delete();

        return redirect()->route('admin.tech-stacks.index')->with('status', 'Tech stack deleted successfully!');
    }
}