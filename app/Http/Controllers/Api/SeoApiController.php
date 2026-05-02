<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Validator;

class SeoApiController extends Controller
{
    private const GLOBAL_DEFAULTS_PAGE_ID = 'global_defaults';

    public function getPages()
    {
        try {
            \Illuminate\Support\Facades\Log::info('Attempting to fetch pages for SEO manager.');

            $friendlyNames = [
                'home' => 'Home Page',
                'products.index' => 'Browse Software (All Products)',
                'products.create' => 'Submit Product Form',
                'categories.index' => 'Browse Categories List',
                'categories.show' => 'Individual Category Page',
                'blog.index' => 'Blog Home',
                'blog.show' => 'Individual Blog Post',
                'deals.index' => 'Software Deals',
                'pages.about' => 'About Us Page',
                'pages.contact' => 'Contact Us Page',
                'pages.terms' => 'Terms of Service',
                'pages.privacy' => 'Privacy Policy',
                'pricing' => 'Pricing Page',
                'search' => 'Search Results',
            ];

            $routes = collect(Route::getRoutes()->getRoutes())->map(function ($route) use ($friendlyNames) {
                $routeName = $route->getName();
                if (!$routeName || !in_array('GET', $route->methods())) {
                    return null;
                }

                // Only include predefined friendly names or simple pages, skip complex admin/auth routes
                if (isset($friendlyNames[$routeName])) {
                    return [
                        'name' => $routeName,
                        'friendly_name' => $friendlyNames[$routeName],
                        'uri' => $route->uri(),
                    ];
                }

                return null;
            })->filter()->values()->toArray();

            // Format to match the JS frontend expectations (using 'name' for the primary label)
            $formattedRoutes = array_map(function ($route) {
                return [
                    'name' => $route['friendly_name'] . ' (' . $route['uri'] . ')',
                    'id' => $route['name'], // Store actual route name for backend indexing
                    'uri' => $route['uri'],
                ];
            }, $routes);

            // Add the Global Fallback Defaults option at the very top
            array_unshift($formattedRoutes, [
                'name' => '⭐ Global Fallback Defaults (Applies to all unconfigured pages)',
                'id' => self::GLOBAL_DEFAULTS_PAGE_ID,
                'uri' => '*',
            ]);

            \Illuminate\Support\Facades\Log::info('Successfully fetched pages for SEO manager.', ['count' => count($formattedRoutes)]);
            return response()->json($formattedRoutes);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error fetching pages for SEO manager: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Error loading pages.', 'error' => $e->getMessage()], 500);
        }
    }

    public function getMeta($page_id)
    {
        $meta = PageMetaTag::where('page_id', $page_id)->first();
        $globalMeta = PageMetaTag::where('page_id', self::GLOBAL_DEFAULTS_PAGE_ID)->first();

        if (!$meta) {
            return response()->json([
                'meta_title' => '',
                'meta_description' => '',
                'og_image_path' => $globalMeta?->og_image_path ? \Illuminate\Support\Facades\Storage::url($globalMeta->og_image_path) : null,
            ]);
        }

        return response()->json($this->metaPayload($meta, $page_id === self::GLOBAL_DEFAULTS_PAGE_ID ? $meta : $globalMeta));
    }

    public function saveMeta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page_id' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('og_image') && $request->input('page_id') !== self::GLOBAL_DEFAULTS_PAGE_ID) {
            return response()->json([
                'og_image' => ['The OG image can only be changed from Global Fallback Defaults.'],
            ], 422);
        }

        $pageMetaTag = PageMetaTag::updateOrCreate(
            ['page_id' => $request->page_id],
            [
                'path' => $request->path,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
            ]
        );

        if ($request->hasFile('og_image')) {
            // Delete the old image if it exists
            if ($pageMetaTag->og_image_path) {
                \Illuminate\Support\Facades\Storage::delete($pageMetaTag->og_image_path);
            }
            $image = $request->file('og_image');
            $filename = uniqid() . '.webp';
            $imagePath = storage_path('app/public/og_images/' . $filename);

            // Ensure the directory exists
            $directory = dirname($imagePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Create an image resource from the uploaded file
            $sourceImage = imagecreatefromstring(file_get_contents($image->getRealPath()));

            if (!$sourceImage) {
                return response()->json([
                    'message' => 'The uploaded image could not be processed. Please upload a JPG, PNG, or WEBP image.',
                ], 422);
            }

            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            // Set the target dimensions
            $targetWidth = 1200;
            $targetHeight = 630;

            // Create a new true color image
            $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

            imagealphablending($targetImage, true);
            imagesavealpha($targetImage, true);

            $sourceAspectRatio = $sourceWidth / max($sourceHeight, 1);
            $targetAspectRatio = $targetWidth / $targetHeight;

            if ($sourceAspectRatio > $targetAspectRatio) {
                $resizeHeight = $targetHeight;
                $resizeWidth = (int) round($targetHeight * $sourceAspectRatio);
            } else {
                $resizeWidth = $targetWidth;
                $resizeHeight = (int) round($targetWidth / max($sourceAspectRatio, 0.0001));
            }

            $destinationX = (int) floor(($targetWidth - $resizeWidth) / 2);
            $destinationY = (int) floor(($targetHeight - $resizeHeight) / 2);

            // Resize and crop the image to the target dimensions without distortion
            imagecopyresampled(
                $targetImage,
                $sourceImage,
                $destinationX,
                $destinationY,
                0,
                0,
                $resizeWidth,
                $resizeHeight,
                $sourceWidth,
                $sourceHeight
            );

            // Save the image as a WEBP file
            imagewebp($targetImage, $imagePath, 80);

            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($targetImage);

            $path = 'public/og_images/' . $filename;
            $pageMetaTag->og_image_path = $path;
            $pageMetaTag->save();
        }

        return response()->json([
            'message' => 'Meta tags saved successfully.',
            'data' => $this->metaPayload($pageMetaTag, $request->input('page_id') === self::GLOBAL_DEFAULTS_PAGE_ID ? $pageMetaTag : PageMetaTag::where('page_id', self::GLOBAL_DEFAULTS_PAGE_ID)->first()),
        ]);
    }

    private function metaPayload(PageMetaTag $meta, ?PageMetaTag $imageSource = null): array
    {
        return [
            'page_id' => $meta->page_id,
            'path' => $meta->path,
            'meta_title' => $meta->meta_title,
            'meta_description' => $meta->meta_description,
            'og_image_path' => $imageSource?->og_image_path ? \Illuminate\Support\Facades\Storage::url($imageSource->og_image_path) : null,
        ];
    }
}
