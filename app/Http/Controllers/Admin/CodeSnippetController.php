<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodeSnippet;
use Illuminate\Http\Request;

class CodeSnippetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('admin.advertising.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'page' => 'required|string',
            'location' => 'required|string|in:head,body',
            'code' => 'required|string',
        ]);

        CodeSnippet::create($request->all());

        return redirect()->route('admin.advertising.index')
            ->with('success', 'Code snippet created successfully.');
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CodeSnippet $codeSnippet)
    {
        $codeSnippet->delete();

        return redirect()->route('admin.advertising.index')
            ->with('success', 'Code snippet deleted successfully.');
    }
}
