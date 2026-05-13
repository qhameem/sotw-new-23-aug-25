<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelogs = Changelog::latest('released_at')->get()->groupBy(function ($log) {
            return $log->released_at->format('F j, Y');
        });

        return view('changelogs.index', compact('changelogs'));
    }

    public function store(Request $request)
    {
        $validator = $this->entryValidator($request->all());

        if ($validator->fails()) {
            return redirect()
                ->route('changelog.index')
                ->withErrors($validator, 'changelogEntry')
                ->withInput()
                ->with('changelog_modal', 'create');
        }

        $validated = $this->prepareEntryPayload($validator->validated());

        Changelog::create($validated);

        return redirect()
            ->route('changelog.index')
            ->with('success', 'Changelog entry created successfully.');
    }

    public function update(Request $request, Changelog $changelog)
    {
        $validator = $this->entryValidator($request->all());

        if ($validator->fails()) {
            return redirect()
                ->route('changelog.index')
                ->withErrors($validator, 'changelogEntry')
                ->withInput()
                ->with('changelog_modal', 'edit-' . $changelog->id);
        }

        $changelog->update($this->prepareEntryPayload($validator->validated()));

        return redirect()
            ->route('changelog.index')
            ->with('success', 'Changelog entry updated successfully.');
    }

    public function destroy(Changelog $changelog)
    {
        $changelog->delete();

        return redirect()
            ->route('changelog.index')
            ->with('success', 'Changelog entry deleted successfully.');
    }

    private function entryValidator(array $data)
    {
        return Validator::make($data, [
            'version' => ['nullable', 'string', 'max:255'],
            'released_at' => ['required', 'date'],
            'type' => ['required', 'in:added,changed,fixed,removed'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function prepareEntryPayload(array $validated): array
    {
        $description = $validated['description'] ?? null;

        if ($description !== null) {
            $description = trim($description);
            $validated['description'] = $description === '' ? null : nl2br(e($description));
        }

        $validated['version'] = filled($validated['version'] ?? null) ? trim($validated['version']) : null;
        $validated['title'] = trim($validated['title']);
        $validated['released_at'] = Carbon::parse($validated['released_at'])->toDateString();

        return $validated;
    }
}
