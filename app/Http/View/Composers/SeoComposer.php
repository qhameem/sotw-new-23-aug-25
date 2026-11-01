<?php

namespace App\Http\View\Composers;

use App\Models\PageMetaTag;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoComposer
{
    public function compose(View $view)
    {
        $routeName = Route::currentRouteName();
        $routeMeta = $routeName ? $this->metadata($routeName) : null;
        $globalMeta = $this->metadata('global_defaults');
        $meta = $routeMeta ?: $globalMeta;

        $viewData = $view->getData();

        if ($meta) {
            $category = $viewData['category'] ?? null;
            if ($category && $routeName === 'categories.show') {
                if (! isset($viewData['meta_title'])) {
                    $view->with('meta_title', $category->name.' - '.config('app.name'));
                }
            } else {
                if (! isset($viewData['meta_title']) || ($routeName === 'home' && filled($routeMeta?->meta_title))) {
                    $view->with('meta_title', $meta->meta_title ?? config('app.name'));
                }
            }

            if (! isset($viewData['meta_description'])) {
                $view->with('meta_description', $meta->meta_description ?? '');
            }

            if (! isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', $globalMeta?->og_image_path ? $this->absoluteUrl(\Illuminate\Support\Facades\Storage::url($globalMeta->og_image_path)) : null);
            }
        } else {
            // Absolute default if nothing is configured
            if (! isset($viewData['meta_title'])) {
                $view->with('meta_title', config('app.name'));
            }
            if (! isset($viewData['meta_description'])) {
                $view->with('meta_description', '');
            }
            if (! isset($viewData['meta_og_image'])) {
                $view->with('meta_og_image', null);
            }
        }
    }

    private function absoluteUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url($value);
    }

    private function metadata(string $pageId): ?PageMetaTag
    {
        $cache = request()->attributes->get('_seo_page_metadata', []);
        if (! array_key_exists($pageId, $cache)) {
            $cache[$pageId] = PageMetaTag::query()
                ->select(['page_id', 'meta_title', 'meta_description', 'og_image_path'])
                ->where('page_id', $pageId)->first();
            request()->attributes->set('_seo_page_metadata', $cache);
        }

        return $cache[$pageId];
    }
}
