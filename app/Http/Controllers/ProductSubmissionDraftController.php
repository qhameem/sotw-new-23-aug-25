<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSubmissionDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ProductSubmissionDraftController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'draft_uuid' => 'nullable|string|size:36',
            'link' => 'nullable|string|max:2048',
            'additional_resources' => 'nullable|string',
            'name' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'tagline_detailed' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*' => 'nullable',
            'categories_custom' => 'nullable|array',
            'useCases' => 'nullable|array',
            'useCases.*' => 'nullable',
            'useCases_custom' => 'nullable|array',
            'platforms' => 'nullable|array',
            'platforms.*' => 'nullable',
            'platforms_custom' => 'nullable|array',
            'bestFor' => 'nullable|array',
            'bestFor.*' => 'nullable',
            'bestFor_custom' => 'nullable|array',
            'pricing' => 'nullable|array',
            'pricing.*' => 'nullable',
            'pricing_page_url' => 'nullable|string|max:2048',
            'tech_stack' => 'nullable|array',
            'tech_stack.*' => 'nullable',
            'tech_stack_custom' => 'nullable|array',
            'favicon' => 'nullable|string|max:2048',
            'logo' => 'nullable|string',
            'gallery' => 'nullable|array|max:1',
            'gallery.*' => 'nullable|string',
            'video_url' => 'nullable|string|max:2048',
            'logos' => 'nullable|array|max:12',
            'logos.*' => 'nullable|string',
            'maker_links' => 'nullable|array',
            'maker_links.*' => 'nullable|string|max:2048',
            'sell_product' => 'nullable|boolean',
            'asking_price' => 'nullable|numeric|min:0|max:99999.99',
            'x_account' => 'nullable|string|max:255',
            'submissionOption' => 'nullable|string|max:32',
            'submission_type' => 'nullable|string|max:32',
            'badge_opt_in' => 'nullable|boolean',
            'badge_placement_url' => 'nullable|string|max:2048',
            'badge_week_start' => 'nullable|string|max:32',
            'badge_verified' => 'nullable|boolean',
            'free_schedule_date' => 'nullable|string|max:32',
            'paid_schedule_date' => 'nullable|string|max:32',
            'comparison_overrides_input' => 'nullable|string',
            'alternative_overrides_input' => 'nullable|string',
            'logoPreview' => 'nullable|string',
            'galleryPreviews' => 'nullable|array|max:1',
            'galleryPreviews.*' => 'nullable|string',
        ]);

        $payload = Arr::except($validated, ['draft_uuid']);

        if (! $this->hasMeaningfulContent($payload)) {
            return response()->json([
                'message' => 'Add a URL or some product details before autosave can create an unfinished submission.',
            ], 422);
        }

        if (filled($payload['link'] ?? null)) {
            $payload['link'] = Product::normalizeLink($payload['link']);
        }

        if (filled($payload['pricing_page_url'] ?? null)) {
            $payload['pricing_page_url'] = Product::normalizeLink($payload['pricing_page_url']);
        }

        if (filled($payload['badge_placement_url'] ?? null)) {
            $payload['badge_placement_url'] = Product::normalizeLink($payload['badge_placement_url']);
        }

        if (array_key_exists('x_account', $payload)) {
            $payload['x_account'] = Product::normalizeXAccount($payload['x_account']);
        }

        $draft = null;

        if (filled($validated['draft_uuid'] ?? null)) {
            $draft = ProductSubmissionDraft::query()
                ->forUser($request->user())
                ->where('uuid', $validated['draft_uuid'])
                ->first();
        }

        if (! $draft && filled($payload['link'] ?? null)) {
            $draft = ProductSubmissionDraft::query()
                ->forUser($request->user())
                ->where('link', $payload['link'])
                ->latest('updated_at')
                ->first();
        }

        $draft ??= new ProductSubmissionDraft([
            'user_id' => $request->user()->id,
        ]);

        $draft->fill([
            'name' => filled(trim((string) ($payload['name'] ?? ''))) ? trim((string) $payload['name']) : null,
            'link' => $payload['link'] ?? null,
            'payload' => $payload,
            'last_autosaved_at' => now(),
        ]);
        $draft->user()->associate($request->user());
        $draft->save();

        return response()->json([
            'draft_uuid' => $draft->uuid,
            'draft' => $draft->toSummaryArray(),
            'autosaved_at' => $draft->last_autosaved_at?->toIso8601String(),
            'autosaved_at_label' => $draft->last_autosaved_at?->format('M j, Y g:i A'),
        ]);
    }

    private function hasMeaningfulContent(array $payload): bool
    {
        $textFields = [
            'link',
            'additional_resources',
            'name',
            'tagline',
            'tagline_detailed',
            'description',
            'pricing_page_url',
            'video_url',
            'x_account',
            'badge_placement_url',
            'logo',
            'logoPreview',
        ];

        foreach ($textFields as $field) {
            if (filled(trim((string) ($payload[$field] ?? '')))) {
                return true;
            }
        }

        $arrayFields = [
            'categories',
            'categories_custom',
            'useCases',
            'useCases_custom',
            'platforms',
            'platforms_custom',
            'bestFor',
            'bestFor_custom',
            'pricing',
            'tech_stack',
            'tech_stack_custom',
            'logos',
            'maker_links',
            'gallery',
            'galleryPreviews',
        ];

        foreach ($arrayFields as $field) {
            if (! empty(array_filter((array) ($payload[$field] ?? []), fn ($value) => filled($value)))) {
                return true;
            }
        }

        return false;
    }
}
