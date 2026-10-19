<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDescriptionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductDescriptionTemplateController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('admin.settings.index').'#settings-description-instructions');
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

        return redirect()->to(route('admin.settings.index').'#settings-description-instructions')
            ->with('success', 'Product description instruction saved and activated.');
    }
}
