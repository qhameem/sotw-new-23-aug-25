<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\TechStack;
use Illuminate\Support\Str;

class PseoController extends Controller
{
    /**
     * /best/{category:slug}
     * "Best {Category} Software in {year}"
     */
    public function bestOf(Category $category)
    {
        $category->loadMissing('types');
        abort_unless($category->types->contains('name', 'Software'), 404);

        $products = $category->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user', 'techStacks'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $year = now()->year;
        $title = "Best {$category->name} Software in {$year}";
        $metaDescription = "Discover the top {$category->name} tools ranked by the community in {$year}. "
            . ($category->meta_description ?: "Compare features, pricing, and more.");

        return view('pseo.best-of', compact('category', 'products', 'title', 'metaDescription', 'year'));
    }

    /**
     * /alternatives/{product:slug}
     * "Best {Product} Alternatives in {year}"
     */
    public function alternatives(Product $product)
    {
        abort_unless($product->approved && $product->is_published, 404);
        $product->loadMissing('categories.types', 'user');

        $categoryIds = $product->categories->pluck('id');

        $alternatives = Product::where('approved', true)
            ->where('is_published', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn($q) => $q->whereIn('categories.id', $categoryIds))
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(15)
            ->get();

        $year = now()->year;
        $title = "Best {$product->name} Alternatives in {$year}";
        $metaDescription = "Looking for {$product->name} alternatives? "
            . "Here are the top " . $alternatives->count() . " tools similar to {$product->name} in {$year}.";

        return view('pseo.alternatives', compact('product', 'alternatives', 'title', 'metaDescription', 'year'));
    }

    /**
     * /built-with/{techstack:slug}
     * "Software Built with {TechStack}"
     */
    public function builtWith(TechStack $techstack)
    {
        $products = $techstack->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $title = "Software Built with {$techstack->name}";
        $metaDescription = "Browse {$products->count()} tools and SaaS products built with {$techstack->name}, ranked by the community.";

        return view('pseo.built-with', compact('techstack', 'products', 'title', 'metaDescription'));
    }

    /**
     * /best/{category:slug}/for/{bestfor:slug}
     * "Best {Category} Software for {BestFor}"
     */
    public function bestFor(Category $category, Category $bestfor)
    {
        $category->loadMissing('types');
        $bestfor->loadMissing('types');

        abort_unless($category->types->contains('name', 'Software'), 404);
        abort_unless($bestfor->types->contains('name', 'Best for'), 404);

        $products = Product::where('approved', true)
            ->where('is_published', true)
            ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
            ->whereHas('categories', fn($q) => $q->where('categories.id', $bestfor->id))
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        abort_if($products->isEmpty(), 404);

        $year = now()->year;
        $title = "Best {$category->name} Software for {$bestfor->name} in {$year}";
        $metaDescription = "Top {$category->name} tools for {$bestfor->name}, ranked by community votes in {$year}.";

        return view('pseo.best-for', compact('category', 'bestfor', 'products', 'title', 'metaDescription', 'year'));
    }

    /**
     * /compare/{slugA}-vs-{slugB}
     * "{ProductA} vs {ProductB}: Which is Better?"
     */
    public function compare($params)
    {
        // Split on the FIRST occurrence of '-vs-' so slugs with hyphens work
        $pos = strpos($params, '-vs-');
        if ($pos === false) {
            abort(404);
        }
        $slugA = substr($params, 0, $pos);
        $slugB = substr($params, $pos + 4); // 4 = strlen('-vs-')


        $productA = Product::where('slug', $slugA)
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'techStacks', 'user'])
            ->firstOrFail();

        $productB = Product::where('slug', $slugB)
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'techStacks', 'user'])
            ->firstOrFail();

        $title = "{$productA->name} vs {$productB->name}: Which is Better?";
        $metaDescription = "Compare {$productA->name} and {$productB->name} side-by-side — features, pricing, tech stack, and community votes.";

        return view('pseo.compare', compact('productA', 'productB', 'title', 'metaDescription'));
    }

    /**
     * /software/{pricing:slug}
     * "{Pricing} Software Tools"
     */
    public function pricingModel(Category $pricing)
    {
        $pricing->loadMissing('types');
        abort_unless($pricing->types->contains('name', 'Pricing'), 404);

        $products = $pricing->products()
            ->where('approved', true)
            ->where('is_published', true)
            ->with(['categories.types', 'user'])
            ->orderByRaw('(votes_count + impressions) DESC')
            ->take(20)
            ->get();

        $title = "{$pricing->name} Software Tools";
        $metaDescription = "Browse {$products->count()} {$pricing->name} software tools, ranked by the community.";

        return view('pseo.pricing-model', compact('pricing', 'products', 'title', 'metaDescription'));
    }
}
