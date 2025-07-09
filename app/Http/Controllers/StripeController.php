<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Mail\PaymentConfirmation;
use App\Mail\ProductScheduled;
use App\Models\PremiumProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeController extends Controller
{
    public function checkout(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|string',
        ]);

        $productIds = explode(',', $request->product_ids);
        $publishDates = $request->publish_dates ?? [];
        $products = Product::whereIn('id', $productIds)->get();

        Stripe::setApiKey(config('stripe.sk'));

        $lineItems = [];
        foreach ($products as $product) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $product->name,
                        'description' => 'Fast-track your submission',
                    ],
                    'unit_amount' => 3000,
                ],
                'quantity' => 1,
            ];
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('promote.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.cancel'),
                'metadata' => [
                    'product_ids' => implode(',', $productIds),
                    'publish_dates' => json_encode($publishDates),
                    'user_id' => Auth::id(),
                    'type' => 'fast-track',
                ]
            ]);

            return redirect($session->url);
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Error creating checkout session: ' . $e->getMessage());
        }
    }

    public function promoteSuccess(Request $request)
    {
        Stripe::setApiKey(config('stripe.sk'));

        try {
            $session = Session::retrieve($request->get('session_id'));
            if ($session->metadata->type === 'premium') {
                return $this->premiumSuccess($request, $session);
            }

            $productIds = explode(',', $session->metadata->product_ids);

            // Get the products from the database.
            $products = Product::whereIn('id', $productIds)->get();

            // Check if the first product has already been processed to prevent reprocessing.
            $firstProduct = $products->first();
            if ($firstProduct && !$firstProduct->approved) {
                // If not approved, this is the first time. Process the payment.
                $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);

                if ($paymentIntent->status == 'succeeded') {
                    $userId = $session->metadata->user_id;
                    $publishDates = json_decode($session->metadata->publish_dates, true) ?? [];

                    foreach ($products as $product) {
                        $product->approved = true;
                        $publishDate = $publishDates[$product->id] ?? null;
                        $publishDateTime = $publishDate ? \Carbon\Carbon::parse($publishDate, 'UTC')->startOfDay()->addHours(7) : now()->utc()->setTime(7, 0, 0);
                        $product->published_at = $publishDateTime;
                        if ($publishDateTime->isPast()) {
                            $product->is_published = true;
                        }
                        $product->save();
                    }
 
                    Artisan::call('products:publish-scheduled');
 
                    $user = User::find($userId);
 
                    if ($user) {
                        foreach ($products as $product) {
                            Mail::to($user->email)->send(new ProductScheduled($product, $user));
                        }
                    }
                } else {
                    return redirect()->route('promote')->with('error', 'Payment was not successful.');
                }
            }

            // Now, regardless of whether we just processed it or are just viewing,
            // fetch the FRESH data from the database to pass to the view.
            $freshProducts = Product::whereIn('id', $productIds)->get();

            return view('site.promote-success', [
                'products' => $freshProducts, // Pass the fresh data
                'session_id' => $request->get('session_id'),
            ]);

        } catch (ApiErrorException $e) {
            return redirect()->route('promote')->with('error', 'Error retrieving payment information: ' . $e->getMessage());
        }
    }

    public function premiumSuccess(Request $request, Session $session)
    {
        $productIds = explode(',', $session->metadata->product_ids);
        $products = Product::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $product->approved = true;
            $product->published_at = now();
            $product->save();

            PremiumProduct::updateOrCreate(
                ['product_id' => $product->id],
                ['expires_at' => now()->addMonth()]
            );
        }

        return view('site.premium-success', compact('products'));
    }

    public function updateDate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'publish_date' => 'required|date',
            'session_id' => 'required|string',
        ]);

        $product = Product::find($request->product_id);

        // Ensure the user owns the product
        if ($product->user_id !== Auth::id()) {
            return back()->with('error', 'You are not authorized to update this product.');
        }

        $product->published_at = \Carbon\Carbon::parse($request->publish_date)->startOfDay();
        $product->save();

        return redirect()->route('promote.success', ['session_id' => $request->session_id])->with('success', 'Publish date updated successfully.');
    }

    public function cancel()
    {
        return redirect()->route('promote')->with('error', 'Payment was canceled.');
    }

    public function webhook()
    {
        // This is where you would handle webhooks from Stripe
    }
    public function productReviewCheckout(Request $request)
    {
        Stripe::setApiKey(config('stripe.sk'));

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Product Review',
                            'description' => 'In-depth review of your product',
                        ],
                        'unit_amount' => 24900,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.product-review.success'),
                'cancel_url' => route('stripe.cancel'),
                'metadata' => [
                    'user_id' => Auth::id(),
                    'type' => 'product-review',
                ]
            ]);

            return redirect($session->url);
        } catch (ApiErrorException $e) {
            return back()->with('error', 'Error creating checkout session: ' . $e->getMessage());
        }
    }

    public function productReviewSuccess(Request $request)
    {
        return redirect()->route('product-reviews.create')->with('success', 'Payment successful! Please fill out the form below.');
    }
}
