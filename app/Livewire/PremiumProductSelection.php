<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\PremiumProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Stripe\Checkout\Session;
use Stripe\Stripe;

use Livewire\Attributes\Computed;

class PremiumProductSelection extends Component
{
    public $products;
    public $selectedProducts = [];
    public $totalPrice = 0;
    public $spotsAvailable;
    public $searchTerm = '';

    public function mount($products, $spotsAvailable)
    {
        $this->products = $products;
        $this->spotsAvailable = $spotsAvailable;
    }

    public function updatedSelectedProducts()
    {
        if (count($this->selectedProducts) > $this->spotsAvailable) {
            $this->selectedProducts = array_slice($this->selectedProducts, 0, $this->spotsAvailable);
        }
        $this->totalPrice = count($this->selectedProducts) * 149;
    }

    #[Computed]
    public function filteredProducts()
    {
        return $this->products->filter(function ($product) {
            return str_contains(strtolower($product->name), strtolower($this->searchTerm)) ||
                   str_contains(strtolower($product->tagline), strtolower($this->searchTerm));
        });
    }

    public function render()
    {
        return view('livewire.premium-product-selection');
    }

    public function checkout()
    {
        $products = Product::whereIn('id', $this->selectedProducts)->get();

        Stripe::setApiKey(config('stripe.sk'));

        $lineItems = [];
        foreach ($products as $product) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                        'description' => 'Premium Spot',
                    ],
                    'unit_amount' => 14900,
                ],
                'quantity' => 1,
            ];
        }

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('promote.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel'),
            'metadata' => [
                'product_ids' => implode(',', $this->selectedProducts),
                'user_id' => Auth::id(),
                'type' => 'premium',
            ]
        ]);

        return redirect($session->url);
    }
}
