<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelogs = Changelog::latest('released_at')->get()->groupBy(function ($log) {
            return $log->released_at->format('F j, Y');
        });
        return view('admin.changelogs.index', compact('changelogs'));
    }

    public function create()
    {
        return view('admin.changelogs.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'version' => 'nullable|string|max:255',
            'released_at' => 'required|date',
            'type' => 'required|in:added,changed,fixed,removed',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Changelog::create($request->all());

        return redirect()->route('admin.changelogs.index')->with('success', 'Changelog entry created successfully.');
    }

    public function edit(Changelog $changelog)
    {
        return view('admin.changelogs.edit', compact('changelog'));
    }

    public function update(Request $request, Changelog $changelog)
    {
        $request->validate([
            'version' => 'nullable|string|max:255',
            'released_at' => 'required|date',
            'type' => 'required|in:added,changed,fixed,removed',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $changelog->update($request->all());

        return redirect()->route('admin.changelogs.index')->with('success', 'Changelog entry updated successfully.');
    }

    public function destroy(Changelog $changelog)
    {
        $changelog->delete();

        return redirect()->route('admin.changelogs.index')->with('success', 'Changelog entry deleted successfully.');
    }
}