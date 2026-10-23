<?php

namespace App\View\Components;

use App\Models\Article;
use App\Services\ProductResearchService;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ResearchLinks extends Component
{
    public Collection $links;

    public function __construct(
        ProductResearchService $research,
        iterable $products = [],
        ?Article $article = null,
        public string $heading = 'Alternatives and comparisons',
        bool $includeAlternatives = true,
    ) {
        $this->links = $research->forProducts(
            $article ? $research->articleProducts($article) : $products,
            6,
            $includeAlternatives
        );
    }

    public function render()
    {
        return view('components.research-links');
    }
}
