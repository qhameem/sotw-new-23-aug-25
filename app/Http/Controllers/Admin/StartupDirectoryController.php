<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Models\StartupDirectory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StartupDirectoryController extends Controller
{
    private function directoryRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'website_url' => ['required', 'url:http,https', 'max:2048'],
            'submission_url' => ['nullable', 'url:http,https', 'max:2048'],
            'category' => ['nullable', Rule::in(array_keys(StartupDirectory::CATEGORIES))],
            'best_for' => ['nullable', 'string', 'max:255'],
            'pricing' => ['nullable', Rule::in(array_keys(StartupDirectory::PRICING))],
            'price_details' => ['nullable', 'string', 'max:255'],
            'link_type' => ['nullable', Rule::in(array_keys(StartupDirectory::LINK_TYPES))],
            'dr' => ['nullable', 'integer', 'between:0,100'],
            'priority' => ['required', 'integer', 'between:0,9999'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'verified_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate(collect($this->directoryRules())->only(['name', 'website_url'])->all());
        $directory = StartupDirectory::create($data);

        return redirect()->route('custom-pages.show', ['product_name' => 'directories', 'q' => $directory->name])
            ->with('directory_status', 'Directory added.');
    }

    public function update(Request $request, StartupDirectory $directory)
    {
        $rules = $this->directoryRules();
        $field = $request->validate(['field' => ['required', 'string', Rule::in(array_keys($rules))]])['field'];
        $data = $request->validate(['value' => $rules[$field]]);
        $directory->update([$field => $data['value'] ?? null]);

        return response()->json([
            'row' => view('site.directories.row', ['directory' => $directory, 'editable' => true])->render(),
        ]);
    }

    public function updateContent(Request $request, CustomPage $page)
    {
        abort_unless($page->slug === 'directories' && $page->is_published, 404);

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:100000'],
            'directory_heading' => ['required', 'string', 'max:255'],
            'directory_intro' => ['nullable', 'string', 'max:2000'],
        ];
        $field = $request->validate(['field' => ['required', 'string', Rule::in(array_keys($rules))]])['field'];
        $data = $request->validate(['value' => $rules[$field]]);
        $page->update([$field => $data['value'] ?? '']);

        return back()->with('directory_status', 'Page content saved.');
    }
}
