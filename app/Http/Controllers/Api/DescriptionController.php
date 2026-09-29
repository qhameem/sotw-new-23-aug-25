<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DescriptionRewriterService;
use App\Services\ProductRegenerationLimiter;
use App\Support\PublicUrlGuard;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DescriptionController extends Controller
{
    public function generate(Request $request, DescriptionRewriterService $rewriter, ProductRegenerationLimiter $limiter)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url'],
            'additional_resources' => ['nullable', 'string', 'max:10000'],
            'draft_uuid' => ['required', 'string', 'size:36'],
        ]);

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

        $document = new DOMDocument;
        @$document->loadHTML($response->body());

        $metaDescription = '';
        foreach ($document->getElementsByTagName('meta') as $meta) {
            if (strtolower($meta->getAttribute('name')) === 'description') {
                $metaDescription = trim($meta->getAttribute('content'));
                break;
            }
        }

        $pageText = trim($document->getElementsByTagName('body')->item(0)?->textContent ?? '');
        $additionalContext = trim((string) ($validated['additional_resources'] ?? ''));
        $context = trim(mb_substr($pageText, 0, 8000)."\n".$additionalContext);
        $rawDescription = $metaDescription !== '' ? $metaDescription : mb_substr($pageText, 0, 1500);
        $description = $rewriter->rewrite(
            trim((string) ($validated['name'] ?? '')) ?: 'This product',
            $rawDescription,
            $context,
        );

        if (! is_string($description) || trim($description) === '') {
            return response()->json(['error' => 'A description could not be generated.'], 422);
        }

        return response()->json(['description' => $description, 'regeneration' => $regeneration]);
    }
}
