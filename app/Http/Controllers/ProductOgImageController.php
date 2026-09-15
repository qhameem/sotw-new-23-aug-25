<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductOgImageService;
use Illuminate\Http\Response;

class ProductOgImageController extends Controller
{
    public function __invoke(Product $product, ProductOgImageService $images): Response
    {
        abort_unless($product->approved && $product->is_published, 404);

        $image = $images->generate($product);

        return response($image, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => (string) strlen($image),
            'Cache-Control' => 'public, max-age=604800, immutable',
            'ETag' => '"'.$images->version($product).'"',
        ]);
    }
}
