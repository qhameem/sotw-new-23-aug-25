<?php

namespace App\Livewire;

use Livewire\Component;

class ProductSearch extends Component
{
    public $query = '';

    public function updatedQuery()
    {
        $products = \App\Models\Product::where('name', 'like', '%' . $this->query . '%')
            ->orWhere('tagline', 'like', '%' . $this->query . '%')
            ->take(5)
            ->get();

        $categories = \App\Models\Category::where('name', 'like', '%' . $this->query . '%')
            ->take(3)
            ->get();

        $this->dispatch('search-results', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function render()
    {
        return view('livewire.product-search');
    }
}
