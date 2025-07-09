<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PremiumProduct;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PremiumSpotController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $products = $user->products()
            ->whereDoesntHave('premiumSpot', function ($query) {
                $query->where('expires_at', '>', now());
            })
            ->with('categories.types')
            ->get();

        $alpineProducts = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'is_upvoted_by_current_user' => false,
                'name' => $product->name,
                'slug' => $product->slug,
                'tagline' => $product->tagline,
                'description' => $product->description,
                'logo' => $product->logo
                    ? (Str::startsWith($product->logo, 'http')
                        ? $product->logo
                        : asset('storage/' . $product->logo))
                    : null,
                'favicon' => 'https://www.google.com/s2/favicons?sz=256&domain_url=' . urlencode($product->link),
                'link' => $product->link,
                'categories' => $product->categories->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                        'types' => $cat->types->map(fn($type) => ['name' => $type->name])->values()
                    ];
                })->values(),
                'category_ids' => $product->categories->pluck('id')->all(),
                'pricing_type' => $product->pricing_type ?? null,
                'price' => $product->price ?? null,
            ];
        })->values();

        $totalPremiumSpots = 6;
        $currentPremiumSpots = PremiumProduct::where('expires_at', '>', now())->count();
        $availableSpots = $totalPremiumSpots - $currentPremiumSpots;

        $title = 'Get a Premium Spot';

        return view('premium-spot.index', compact('products', 'title', 'alpineProducts', 'availableSpots'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|string',
        ]);

        $productIds = explode(',', $request->product_ids);
        $products = \App\Models\Product::whereIn('id', $productIds)->get();

        $totalPremiumSpots = 6;
        $currentPremiumSpots = PremiumProduct::where('expires_at', '>', now())->count();
        $availableSpots = $totalPremiumSpots - $currentPremiumSpots;

        if (count($products) > $availableSpots) {
            return back()->with('error', 'You can only select up to ' . $availableSpots . ' product(s).');
        }

        \Stripe\Stripe::setApiKey(config('stripe.sk'));

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

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('premium-spot.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel'),
                'metadata' => [
                    'product_ids' => implode(',', $productIds),
                    'user_id' => Auth::id(),
                    'type' => 'premium-spot',
                ]
            ]);

            return redirect($session->url);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return back()->with('error', 'Error creating checkout session: ' . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('stripe.sk'));

        try {
            $session = \Stripe\Checkout\Session::retrieve($request->get('session_id'));
            $productIds = explode(',', $session->metadata->product_ids);
            $products = \App\Models\Product::whereIn('id', $productIds)->get();

            foreach ($products as $product) {
                PremiumProduct::updateOrCreate(
                    ['product_id' => $product->id],
                    ['expires_at' => now()->addMonth()]
                );
            }

            return view('premium-spot.success', compact('products'));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return redirect()->route('premium-spot.index')->with('error', 'Error retrieving payment information: ' . $e->getMessage());
        }
    }

    public function details()
    {
        return view('premium-spot.details');
    }
}
