<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SeoComposer
{
    private static array $routeMetaCache = [];
    private static bool $globalMetaLoaded = false;
    private static ?PageMetaTag $globalMeta = null;

    public function compose(View $view)
    {
        $routeName = Route::currentRouteName();
        $routeMeta = $routeName
            ? (self::$routeMetaCache[$routeName] ??= PageMetaTag::query()
                ->select(['page_id', 'meta_title', 'meta_description', 'og_image_path'])
                ->where('page_id', $routeName)
                ->first())
            : null;
        $globalMeta = $this->globalMeta();
        $meta = $routeMeta ?: $globalMeta;

        $viewData = $view->getData();

        if ($meta) {
            $category = $viewData['category'] ?? null;
            if ($category && $routeName === 'categories.show') {
                if (!isset($viewData['meta_title'])) {
                    $view->with('meta_title', $category->name . ' - ' . config('app.name'));
                }
            } else {
                if (!isset($viewData['meta_title'])) {
                    $view->with('meta_title', $meta->meta_title ?? config('app.name'));
                }
            }
            
            if (!isset($viewData['meta_description'])) {
                $view->with('meta_description', $meta->meta_description ?? '');
            }
            
            if (!isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', $globalMeta?->og_image_path ? $this->absoluteUrl(\Illuminate\Support\Facades\Storage::url($globalMeta->og_image_path)) : null);
            }
        } else {
            // Absolute default if nothing is configured
            if (!isset($viewData['meta_title'])) {
                $view->with('meta_title', config('app.name'));
            }
            if (!isset($viewData['meta_description'])) {
                $view->with('meta_description', '');
            }
            if (!isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', null);
            }
        }
    }

    private function absoluteUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url($value);
    }

    private function globalMeta(): ?PageMetaTag
    {
        if (!self::$globalMetaLoaded) {
            self::$globalMeta = PageMetaTag::query()
                ->select(['page_id', 'meta_title', 'meta_description', 'og_image_path'])
                ->where('page_id', 'global_defaults')
                ->first();
            self::$globalMetaLoaded = true;
        }

        return self::$globalMeta;
    }
}
