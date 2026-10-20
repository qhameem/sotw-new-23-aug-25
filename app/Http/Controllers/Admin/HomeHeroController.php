<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HomeHero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\FeatureIconNormalizer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeHeroController extends Controller
{
    public function updateFeatureIcon(Request $request, int $feature, FeatureIconNormalizer $normalizer): RedirectResponse
    {
        abort_unless(array_key_exists($feature, HomeHero::DEFAULT_FEATURES), 404);
        $request->validateWithBag('homeHeroIcon'.$feature, [
            'icon' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        try {
            $png = $normalizer->normalize($request->file('icon'));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $exception->errorBag = 'homeHeroIcon'.$feature;
            throw $exception;
        }
        $path = 'hero-icons/'.Str::uuid().'.png';
        if (! Storage::disk('public')->put($path, $png)) {
            throw new \RuntimeException('Unable to save feature icon.');
        }
        $previous = HomeHero::featureIcons()[$feature] ?? null;
        try {
            HomeHero::saveFeatureIcon($feature, $path);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
        if (is_string($previous) && str_starts_with($previous, 'hero-icons/')) {
            Storage::disk('public')->delete($previous);
        }

        return redirect()->route('home')->with('home_hero_icon_saved', $feature);
    }

    public function updateFeature(Request $request, int $feature): RedirectResponse
    {
        abort_unless(array_key_exists($feature, HomeHero::DEFAULT_FEATURES), 404);

        $validated = $request->validateWithBag('homeHeroFeature'.$feature, [
            'label' => ['required', 'string', 'max:150'],
        ]);

        HomeHero::saveFeature($feature, $validated['label']);

        return redirect()->route('home')->with('home_hero_feature_saved', $feature);
    }

    public function updateDescription(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('homeHeroDescription', [
            'description' => ['required', 'string', 'max:2000'],
        ]);

        HomeHero::saveDescription($validated['description']);

        return redirect()->route('home')->with('home_hero_description_saved', true);
    }

    public function update(Request $request): RedirectResponse
    {
        $headline = $request->validateWithBag('homeHero', [
            'headline' => ['required', 'string', 'max:150'],
            'highlights' => ['required', 'array', 'min:1', 'max:20'],
            'highlights.*' => ['required', 'string', 'max:150', 'distinct'],
        ]);

        $headline['highlights'] = array_values($headline['highlights']);
        $headline['highlight'] = $headline['highlights'][0];
        HomeHero::saveHeadline($headline);

        return redirect()->route('home')->with('home_hero_saved', true);
    }
}
