<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProviderRoutingService;
use App\Services\DescriptionRewriterService;
use App\Services\ProductRegenerationLimiter;
use App\Support\PublicUrlGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DescriptionController extends Controller
{
    public function generate(Request $request, DescriptionRewriterService $rewriter, ProductRegenerationLimiter $limiter, AiProviderRoutingService $router)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'additional_resources' => ['nullable', 'string', 'max:10000'],
            'draft_uuid' => ['required', 'string', 'size:36'],
            'provider' => ['nullable', 'string', 'in:gemini,groq,cloudflare,openrouter,cerebras'],
        ]);
        $provider = $validated['provider'] ?? null;
        if ($provider && (! $request->user() || ! $request->user()->is_admin())) {
            abort(403, 'Only administrators may choose an AI model.');
        }

        $regeneration = $limiter->consume($request->user(), $validated['draft_uuid'], 'description');

        try {
            $url = PublicUrlGuard::sanitizePublicHttpUrl($validated['url']);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['error' => $exception->getMessage()], 422);
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; SoftwareOnTheWebBot/1.0)',
        ])->timeout(20)->get($url);

        if (! $response->successful()) {
            return response()->json(['error' => 'The website could not be read.'], 422);
        }

        $source = app(\App\Services\ProductSourceCollector::class)->collect(
            $url, $response->body(), trim((string) ($validated['additional_resources'] ?? ''))
        );
        $facts = app(\App\Services\FactExtractorService::class)->extract($source);
        $body = $facts === null ? null : $rewriter->generateFromFacts(
            trim((string) ($validated['name'] ?? '')) ?: 'This product', $facts, $source, '', $provider
        );
        if ($body === null) {
            return response()->json(['error' => 'A supported description could not be generated. Try again or provide additional source information.'], 422);
        }
        $fields = \App\Support\ListingDescription::fields($body);
        $description = $fields['description'];
        $warnings = $body['_generation_warnings'] ?? [];
        $draft = $warnings === [] ? [] : [
            'generation_status' => 'draft', 'generation_noindex' => true,
            'generation_review_required' => true, 'generation_errors' => $warnings,
            'description_notice' => 'Generated draft retained for editing. '.implode(' ', $warnings),
        ];
        $cacheKey = 'product_listing:'.hash('sha256', \App\Models\Product::normalizeLink($url));
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        \Illuminate\Support\Facades\Cache::put($cacheKey, array_merge(is_array($cached) ? $cached : [], $fields, ['facts_json' => $facts], $draft), now()->addHour());
        $usedProvider = $rewriter->listingProvider();

        return response()->json([
            'description' => $description,
            ...$draft,
            'regeneration' => $regeneration,
            'ai_provider' => $usedProvider,
            'ai_provider_label' => $usedProvider ? $router->providerLabel($usedProvider) : null,
            'ai_model' => $usedProvider ? $router->modelFor($usedProvider) : null,
        ]);
    }
}
