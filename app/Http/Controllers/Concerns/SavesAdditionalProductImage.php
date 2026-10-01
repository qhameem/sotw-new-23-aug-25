<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Product;
use App\Support\ProductMediaSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

trait SavesAdditionalProductImage
{
    protected function saveAdditionalProductImage(Product $product, Request $request, bool $proposed): void
    {
        $file = $request->file('media.1');
        $temporaryPath = null;
        if (! $file && $request->filled('media_urls.1')) {
            $temporaryPath = $this->downloadMediaUrlToTemporaryPublicPath($request->input('media_urls.1'));
            if ($temporaryPath) {
                $file = Storage::disk('public')->path($temporaryPath);
            }
        }
        if (! $file) {
            return;
        }
        try {
            $asset = $this->storeScreenshotAsset($product, $file, new ImageManager(new Driver), (bool) $temporaryPath, $proposed ? 'proposed-extra-' : 'extra-');
            if (! $asset) {
                return;
            }
            if ($proposed) {
                $old = $product->proposed_additional_image;
                if ($old) {
                    $this->deleteMediaFiles($old['path'], $old['path_thumb'] ?? null, $old['path_medium'] ?? null);
                }
                $product->proposed_additional_image = $asset;
                return;
            }
            $media = $product->media()->whereIn('type', ['image', 'screenshot'])->orderBy('id')->skip(1)->first();
            if ($media) {
                $this->deleteMediaFiles($media->path, $media->path_thumb, $media->path_medium);
                $media->update(array_merge($asset, ['type' => 'image', 'alt_text' => ProductMediaSeo::productMediaAltText($product, 'image', 2)]));
            } else {
                $product->media()->create(array_merge($asset, ['type' => 'image', 'alt_text' => ProductMediaSeo::productMediaAltText($product, 'image', 2)]));
            }
        } finally {
            if ($temporaryPath) {
                Storage::disk('public')->delete($temporaryPath);
            }
        }
    }

}
