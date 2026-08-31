<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebsiteProviderLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class WebsiteProviderController extends Controller
{
    public function __invoke(Request $request, WebsiteProviderLookup $lookup): JsonResponse
    {
        $validated = $request->validate(['url' => 'required|string|url:http,https|max:2048']);
        try {
            return response()->json($lookup->lookup($validated['url']));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['url' => $e->getMessage()]);
        }
    }
}
