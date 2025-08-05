<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Validator;

class SeoApiController extends Controller
{
    public function getPages()
    {
        try {
            \Illuminate\Support\Facades\Log::info('Attempting to fetch pages for SEO manager.');

            $routes = collect(Route::getRoutes()->getRoutes())->map(function ($route) {
                $routeName = $route->getName();
                $routeUri = $route->uri();
                $routeMethods = $route->methods();

                \Illuminate\Support\Facades\Log::debug("Processing route: Name={$routeName}, URI={$routeUri}, Methods=" . implode(',', $routeMethods));

                return [
                    'name' => $routeName,
                    'uri' => $routeUri,
                    'methods' => $routeMethods,
                ];
            })->filter(function ($route) {
                return $route['name'] && in_array('GET', $route['methods']);
            })->values();

            \Illuminate\Support\Facades\Log::info('Successfully fetched pages for SEO manager.', ['count' => $routes->count()]);
            return response()->json($routes);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching pages for SEO manager: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Error loading pages.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getMeta($page_id)
    {
        $meta = PageMetaTag::where('page_id', $page_id)->first();

        if (!$meta) {
            return response()->json(['meta_title' => '', 'meta_description' => '']);
        }

        return response()->json([
            'meta_title' => $meta->meta_title,
            'meta_description' => $meta->meta_description,
        ]);
    }

    public function saveMeta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $pageMetaTag = PageMetaTag::updateOrCreate(
            ['page_id' => $request->page_id],
            [
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]
        );

        return response()->json(['message' => 'Meta tags saved successfully.', 'data' => $pageMetaTag]);
    }
}
