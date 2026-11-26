<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Models\StartupDirectory;
use App\Services\StartupDirectoryArchive;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

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
        $directory = DB::transaction(function () use ($directory, $field, $data): StartupDirectory {
            $current = StartupDirectory::query()->lockForUpdate()->findOrFail($directory->getKey());
            $current->fill([$field => $data['value'] ?? null]);
            if ($current->isDirty('website_url')) {
                $current->original_website_url ??= $current->getOriginal('website_url');
            }
            if ($current->isDirty(['dr', 'website_url'])) {
                $current->dr_updated_at = null;
            }
            $current->save();

            return $current;
        });

        return response()->json([
            'row' => view('site.directories.row', ['directory' => $directory, 'editable' => true])->render(),
        ]);
    }

    public function destroy(StartupDirectory $directory, StartupDirectoryArchive $archive)
    {
        $redirect = redirect()->route('custom-pages.show', ['product_name' => 'directories']);

        try {
            $archive->delete($directory);
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            report($exception);

            return $redirect->with('directory_error', 'Could not delete the directory and update the CSV archive.');
        }

        return $redirect->with('directory_status', 'Directory deleted.');
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
