<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdZone;
use App\Models\Category;
use App\Models\Product;
use App\Support\CountryOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.advertising.index', ['tab' => 'ads']);
    }

    public function create(Request $request): View
    {
        return view('admin.ads.create', $this->formData(
            template: $request->query('template', 'custom'),
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $product = $this->resolveProduct($validated);

        $ad = Ad::create($this->buildAdData($request, $validated, $product));
        $ad->adZones()->sync($validated['ad_zones']);

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ads'])
            ->with('success', 'Ad created successfully.');
    }

    public function show(Ad $ad): RedirectResponse
    {
        return redirect()->route('admin.ads.edit', $ad);
    }

    public function edit(Ad $ad): View
    {
        return view('admin.ads.edit', $this->formData($ad));
    }

    public function update(Request $request, Ad $ad): RedirectResponse
    {
        $validated = $this->validateRequest($request, $ad);
        $product = $this->resolveProduct($validated);
        $oldManagedImage = $ad->hasManagedImage() ? Ad::normalizeStoragePath($ad->content) : null;

        $ad->update($this->buildAdData($request, $validated, $product, $ad));
        $ad->adZones()->sync($validated['ad_zones']);

        if ($oldManagedImage !== null && $oldManagedImage !== Ad::normalizeStoragePath($ad->content)) {
            Storage::disk('public')->delete($oldManagedImage);
        }

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ads'])
            ->with('success', 'Ad updated successfully.');
    }

    public function destroy(Ad $ad): RedirectResponse
    {
        if ($ad->hasManagedImage()) {
            Storage::disk('public')->delete(Ad::normalizeStoragePath($ad->content));
        }

        $ad->adZones()->detach();
        $ad->delete();

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ads'])
            ->with('success', 'Ad deleted successfully.');
    }

    public function toggleActive(Ad $ad): RedirectResponse
    {
        $ad->update([
            'is_active' => ! $ad->is_active,
        ]);

        return redirect()
            ->route('admin.advertising.index', ['tab' => 'ads'])
            ->with('success', $ad->is_active ? 'Ad activated successfully.' : 'Ad deactivated successfully.');
    }

    public function duplicate(Ad $ad): RedirectResponse
    {
        $copy = $ad->replicate([
            'impressions_count',
            'clicks_count',
        ]);
        $copy->internal_name = $ad->internal_name . ' (Copy)';
        $copy->is_active = false;
        $copy->impressions_count = 0;
        $copy->clicks_count = 0;
        $copy->save();
        $copy->adZones()->sync($ad->adZones()->pluck('ad_zones.id'));

        return redirect()
            ->route('admin.ads.edit', $copy)
            ->with('success', 'Ad duplicated. Review the copy before activating it.');
    }

    protected function formData(?Ad $ad = null, ?string $template = null): array
    {
        return [
            'ad' => $ad,
            'adZones' => AdZone::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'countries' => CountryOptions::all(),
            'products' => Product::orderBy('name')->get(),
            'selectedTemplate' => $template ?? request()->old('template', 'custom'),
        ];
    }

    protected function validateRequest(Request $request, ?Ad $ad = null): array
    {
        $validator = Validator::make($request->all(), [
            'template' => ['nullable', Rule::in(['custom', 'sponsor', 'sidebar_banner', 'inline_listing', 'product_listing_card'])],
            'product_id' => ['nullable', 'exists:products,id'],
            'internal_name' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(AdZone::SUPPORTED_AD_TYPES)],
            'content_image' => ['nullable', 'image', 'max:4096'],
            'content_text' => ['nullable', 'string', 'max:255'],
            'content_html' => ['nullable', 'string'],
            'target_url' => ['nullable', 'url', 'max:2048'],
            'open_in_new_tab' => ['nullable', 'boolean'],
            'ad_zones' => ['required', 'array', 'min:1'],
            'ad_zones.*' => ['exists:ad_zones,id'],
            'is_active' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_countries' => ['nullable', 'array'],
            'target_countries.*' => ['size:2'],
            'target_routes' => ['nullable', 'string', 'max:500'],
            'target_category_ids' => ['nullable', 'array'],
            'target_category_ids.*' => ['integer', 'exists:categories,id'],
            'audience_scope' => ['nullable', Rule::in(['all', 'guest', 'authenticated'])],
            'device_types' => ['nullable', 'array'],
            'device_types.*' => [Rule::in(['desktop', 'mobile', 'tablet'])],
            'weight' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_house_ad' => ['nullable', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $ad) {
            $validated = $validator->safe()->all();
            $type = $validated['type'] ?? null;
            $productSelected = ! empty($validated['product_id']);

            if (! $productSelected && blank($validated['internal_name'] ?? null)) {
                $validator->errors()->add('internal_name', 'The internal reference name field is required.');
            }

            $hasExistingImageContent = $ad && in_array($ad->type, ['image_banner', 'product_listing_card'], true) && filled($ad->content);

            if (in_array($type, ['image_banner', 'product_listing_card'], true) && ! $request->hasFile('content_image') && ! $productSelected && ! $hasExistingImageContent) {
                $validator->errors()->add('content_image', 'Upload an image or choose an existing product logo.');
            }

            if (in_array($type, ['image_banner', 'product_listing_card', 'text_link'], true) && blank($validated['target_url'] ?? null) && ! $productSelected) {
                $validator->errors()->add('target_url', 'The target URL field is required for clickable ads.');
            }

            if ($type === 'product_listing_card' && blank($validated['tagline'] ?? null) && ! $productSelected && blank($ad?->tagline)) {
                $validator->errors()->add('tagline', 'The tagline field is required for product listing card ads.');
            }

            if ($type === 'text_link' && blank($validated['content_text'] ?? null)) {
                $validator->errors()->add('content_text', 'The link text field is required.');
            }

            if ($type === 'html_snippet' && blank($validated['content_html'] ?? null)) {
                $validator->errors()->add('content_html', 'The HTML snippet field is required.');
            }

            if (! empty($validated['ad_zones']) && $type) {
                $zones = AdZone::query()->whereIn('id', $validated['ad_zones'])->get();

                foreach ($zones as $zone) {
                    if (! $zone->supportsAdType($type)) {
                        $validator->errors()->add('ad_zones', "{$zone->name} does not support {$type} ads.");
                    }
                }
            }
        });

        $validated = $validator->validate();
        $validated['target_routes'] = $this->csvToArray($validated['target_routes'] ?? null);

        return $validated;
    }

    protected function resolveProduct(array $validated): ?Product
    {
        if (empty($validated['product_id'])) {
            return null;
        }

        return Product::find($validated['product_id']);
    }

    protected function buildAdData(Request $request, array $validated, ?Product $product = null, ?Ad $existingAd = null): array
    {
        $type = $validated['type'];
        $adData = [
            'internal_name' => ($validated['internal_name'] ?? null) ?: $product?->name,
            'tagline' => ($validated['tagline'] ?? null) ?: $product?->tagline,
            'type' => $type,
            'target_url' => ($validated['target_url'] ?? null) ?: $product?->link,
            'open_in_new_tab' => $request->has('open_in_new_tab'),
            'is_active' => $request->boolean('is_active'),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'target_countries' => $validated['target_countries'] ?? null,
            'target_routes' => $validated['target_routes'] ?: null,
            'target_category_ids' => $validated['target_category_ids'] ?? null,
            'audience_scope' => $validated['audience_scope'] ?? 'all',
            'device_types' => $validated['device_types'] ?? null,
            'weight' => $validated['weight'] ?? 1,
            'priority' => $validated['priority'] ?? 0,
            'is_house_ad' => $request->boolean('is_house_ad'),
        ];

        if (in_array($type, ['image_banner', 'product_listing_card'], true)) {
            if ($request->hasFile('content_image')) {
                $adData['content'] = $request->file('content_image')->store('ads', 'public');
                $adData['manages_own_image'] = true;
            } elseif ($product) {
                $adData['content'] = Ad::normalizeStoragePath($product->logo)
                    ?? Ad::normalizeStoragePath($product->logo_url)
                    ?? $product->logo_url;
                $adData['manages_own_image'] = false;
            } elseif ($existingAd) {
                $adData['content'] = Ad::normalizeStoragePath($existingAd->content) ?? $existingAd->content;
                $adData['manages_own_image'] = $existingAd->manages_own_image;
            }
        }

        if ($type === 'text_link') {
            $adData['content'] = $validated['content_text'];
            $adData['manages_own_image'] = false;
        }

        if ($type === 'html_snippet') {
            $adData['content'] = $validated['content_html'];
            $adData['manages_own_image'] = false;
        }

        return $adData;
    }

    protected function csvToArray(?string $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
