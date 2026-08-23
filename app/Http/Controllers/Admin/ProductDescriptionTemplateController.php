<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDescriptionTemplate;
use App\Support\ProductDescriptionTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductDescriptionTemplateController extends Controller
{
    public function index(ProductDescriptionTemplates $templates): View
    {
        $templates->ensureDefaultExists();

        return view('admin.settings.product-description-templates', [
            'templates' => ProductDescriptionTemplate::query()->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['nullable', 'integer', 'exists:product_description_templates,id'],
            'name' => ['required', 'string', 'max:120'],
            'instruction' => ['required', 'string', 'max:10000'],
        ]);

        DB::transaction(function () use ($validated): void {
            ProductDescriptionTemplate::query()->update(['is_active' => false]);

            ProductDescriptionTemplate::query()->updateOrCreate(
                ['id' => $validated['template_id'] ?? null],
                [
                    'name' => trim($validated['name']),
                    'instruction' => trim($validated['instruction']),
                    'is_active' => true,
                ]
            );
        });

        return redirect()->route('admin.settings.product-description-templates.index')
            ->with('success', 'Product description instruction saved and activated.');
    }
}
