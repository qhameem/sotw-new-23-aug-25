<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomPageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => CustomPage::latest('id')->paginate(25)]);
    }

    public function create()
    {
        return view('admin.pages.form', ['page' => new CustomPage]);
    }

    public function store(Request $request)
    {
        CustomPage::create($this->validatePage($request));

        return redirect()->route('admin.pages.index')->with('success', 'Page created.');
    }

    public function edit(CustomPage $page)
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(Request $request, CustomPage $page)
    {
        $page->update($this->validatePage($request, $page));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page saved.');
    }

    public function updateSidebarSection(Request $request, CustomPage $page)
    {
        $data = $request->validate([
            'section' => ['required', Rule::in(array_keys(CustomPage::SIDEBAR_SECTIONS))],
            'enabled' => ['required', 'boolean'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($page, $data) {
            $lockedPage = CustomPage::whereKey($page->id)->lockForUpdate()->firstOrFail();
            $sections = $lockedPage->sidebarSections();
            $sections[$data['section']] = (bool) $data['enabled'];
            $lockedPage->update(['sidebar_sections' => $sections]);
        });

        return redirect()->route('custom-pages.show', ['product_name' => $page->slug])
            ->with('success', 'Sidebar section updated.');
    }

    private function validatePage(Request $request, ?CustomPage $page = null): array
    {
        $uniqueSlug = Rule::unique('custom_pages', 'slug');

        if ($page) {
            $uniqueSlug->ignore($page);
        }

        $data = $request->validate([
            'slug' => ['bail', 'required', 'string', 'max:120', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $uniqueSlug],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:100000'],
            'is_published' => ['required', 'boolean'],
            'sidebar_sections' => ['required', 'array:'.implode(',', array_keys(CustomPage::SIDEBAR_SECTIONS))],
            'sidebar_sections.*' => ['required', 'boolean'],
        ]);

        foreach (CustomPage::SIDEBAR_SECTIONS as $key => $label) {
            $data['sidebar_sections'][$key] = (bool) ($data['sidebar_sections'][$key] ?? false);
        }

        $slug = $data['slug'];
        $routes = Route::getRoutes();
        $url = Request::create($request->getSchemeAndHttpHost().'/'.$slug);

        try {
            $matchesPageRoute = $routes->match($url)->named('custom-pages.show');
        } catch (NotFoundHttpException) {
            $matchesPageRoute = false;
        }

        $usesReservedNamespace = collect($routes->getRoutes())->contains(
            fn ($route) => str_starts_with($route->uri(), $slug.'/')
        );

        if (! $matchesPageRoute || $usesReservedNamespace || Product::where('slug', $slug)->exists() || file_exists(public_path($slug))) {
            throw ValidationException::withMessages(['slug' => 'This URL is reserved or already in use.']);
        }

        $data['body'] = $data['body'] ?? '';

        return $data;
    }
}
