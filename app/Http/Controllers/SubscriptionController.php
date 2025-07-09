<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Create a new Lemon Squeezy checkout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        // It's a good practice to validate that the user is authenticated.
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // You must replace 'your-variant-id' with an actual variant ID from your Lemon Squeezy store.
        // You can find this in your Lemon Squeezy dashboard under Products.
        $variantId = 876575;

        try {
            $checkout = $request->user()->checkout($variantId)
                ->withName('Pro Subscription') // Optional: Prefill the product name
                ->withEmail($request->user()->email) // Optional: Prefill the user's email
                ->withBillingAddress('US', '10001') // Optional: Prefill billing address 
                ->redirectTo(route('subscription.thankyou'));

            return redirect($checkout->url());
        } catch (\Exception $e) {
            // Log the error for debugging purposes.
            Log::error('Lemon Squeezy Checkout Error: ' . $e->getMessage());
            Log::debug('Lemon Squeezy Store: ' . config('lemon-squeezy.store'));

            // Redirect back with an error message.
            return back()->with('error', 'Sorry, we could not process your request. Please try again later.');
        }
    }
}