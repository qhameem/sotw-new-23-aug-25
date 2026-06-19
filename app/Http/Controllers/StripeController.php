<?php

namespace App\Http\Controllers;

use App\Models\PaidSubmissionCheckout;
use App\Models\PaymentEventLog;
use App\Models\Product;
use App\Mail\ProductScheduled;
use App\Models\PremiumProduct;
use App\Services\PaidSubmissionService;
use App\Models\User;
use App\Support\ProductPublishSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function __construct(
        protected PaidSubmissionService $paidSubmissionService
    ) {
    }

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
                        $publishDateTime = $publishDate
                            ? ProductPublishSchedule::forDate($publishDate)
                            : ProductPublishSchedule::forDate(now()->utc());
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
                    return redirect()->route('fast-track.index')->with('error', 'Payment was not successful.');
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
            return redirect()->route('fast-track.index')->with('error', 'Error retrieving payment information: ' . $e->getMessage());
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
        return redirect()->route('home')->with('error', 'Payment was canceled.');
    }

    public function paidSubmissionCheckout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        Stripe::setApiKey(config('stripe.sk'));

        $checkout = $this->paidSubmissionService->stageCheckoutFromRequest($request, $user);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => PaidSubmissionService::CURRENCY,
                        'product_data' => [
                            'name' => 'Paid product submission',
                            'description' => 'Permanent do-follow backlink and custom schedule date',
                        ],
                        'unit_amount' => $checkout->amount_cents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('stripe.paid-submission.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('stripe.paid-submission.cancel', $checkout),
                'metadata' => [
                    'checkout_id' => (string) $checkout->id,
                    'checkout_uuid' => $checkout->uuid,
                    'user_id' => (string) $user->id,
                    'type' => 'paid-submission',
                ],
            ], [
                'idempotency_key' => $checkout->idempotency_key,
            ]);

            $checkout->forceFill([
                'status' => 'checkout_created',
                'stripe_checkout_session_id' => $session->id,
            ])->save();

            Log::info('Stripe Checkout session created for paid submission.', [
                'checkout_id' => $checkout->id,
                'session_id' => $session->id,
            ]);

            return response()->json([
                'checkout_url' => $session->url,
            ]);
        } catch (ApiErrorException $e) {
            $checkout->forceFill([
                'status' => 'failed',
                'failure_message' => 'Checkout session creation failed.',
            ])->save();

            Log::error('Failed to create Stripe Checkout session for paid submission.', [
                'checkout_id' => $checkout->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to start payment. Please try again.',
            ], 422);
        }
    }

    public function paidSubmissionSuccess(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $checkout = PaidSubmissionCheckout::where('stripe_checkout_session_id', $request->string('session_id'))->firstOrFail();

        $this->authorizeCheckoutAccess($checkout);

        if (!in_array($checkout->status, ['paid', 'completed'], true) || !$checkout->paid_at) {
            Stripe::setApiKey(config('stripe.sk'));

            try {
                $session = Session::retrieve($request->string('session_id'));

                if (($session->metadata->type ?? null) === 'paid-submission' && ($session->payment_status ?? null) === 'paid') {
                    $checkout->forceFill([
                        'status' => $checkout->product_id ? 'completed' : 'paid',
                        'stripe_checkout_session_id' => $session->id,
                        'stripe_payment_intent_id' => $session->payment_intent ?? $checkout->stripe_payment_intent_id,
                        'paid_at' => $checkout->paid_at ?: now(),
                    ])->save();

                    $this->paidSubmissionService->fulfillCheckout(
                        $checkout,
                        $session->payment_intent ?? null,
                        $checkout->stripe_event_id
                    );
                }
            } catch (ApiErrorException $e) {
                Log::warning('Unable to sync paid submission checkout from Stripe success return.', [
                    'checkout_id' => $checkout->id,
                    'session_id' => $request->string('session_id'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('stripe.paid-submission.confirmation', $checkout);
    }

    public function paidSubmissionConfirmation(PaidSubmissionCheckout $checkout)
    {
        $this->authorizeCheckoutAccess($checkout);

        $freshCheckout = $checkout->fresh(['product', 'user']);

        return view('stripe.paid-submission-confirmation', [
            'checkout' => $freshCheckout,
            'product' => $freshCheckout?->product,
        ]);
    }

    public function updatePaidSubmissionScheduleDate(Request $request, PaidSubmissionCheckout $checkout)
    {
        $this->authorizeCheckoutAccess($checkout);

        try {
            $validated = $request->validateWithBag('changePaidSubmissionSchedule', [
                'schedule_date' => ['required', 'date_format:Y-m-d'],
            ]);

            $selectedDate = $this->paidSubmissionService->validatePaidScheduleDate(
                $validated['schedule_date'],
                'schedule_date'
            );

            DB::transaction(function () use ($checkout, $selectedDate) {
                $lockedCheckout = PaidSubmissionCheckout::query()
                    ->with('product')
                    ->lockForUpdate()
                    ->findOrFail($checkout->id);

                if (!$lockedCheckout->canChangeScheduleDateOnce()) {
                    throw ValidationException::withMessages([
                        'schedule_date' => 'This publish date can no longer be changed.',
                    ]);
                }

                if ($lockedCheckout->schedule_date && $lockedCheckout->schedule_date->isSameDay($selectedDate)) {
                    throw ValidationException::withMessages([
                        'schedule_date' => 'Please choose a different publish date.',
                    ]);
                }

                $publishAt = ProductPublishSchedule::forDate($selectedDate);
                $payload = $lockedCheckout->submission_payload ?? [];
                $payload['paid_schedule_date'] = $selectedDate->toDateString();

                $lockedCheckout->forceFill([
                    'schedule_date' => $selectedDate->toDateString(),
                    'schedule_date_changed_at' => now(),
                    'submission_payload' => $payload,
                ])->save();

                if ($lockedCheckout->product) {
                    $lockedCheckout->product->forceFill([
                        'published_at' => $publishAt,
                        'is_published' => $publishAt->isPast(),
                    ])->save();
                }
            });
        } catch (ValidationException $exception) {
            $exception->errorBag = 'changePaidSubmissionSchedule';
            throw $exception;
        }

        return redirect()
            ->route('stripe.paid-submission.confirmation', $checkout)
            ->with('status', 'Publish date updated successfully.');
    }

    public function paidSubmissionCancel(PaidSubmissionCheckout $checkout)
    {
        $this->authorizeCheckoutAccess($checkout);

        if (in_array($checkout->status, ['pending', 'checkout_created'], true)) {
            $checkout->forceFill([
                'status' => 'canceled',
                'failure_message' => 'Checkout was canceled by the user.',
            ])->save();
        }

        return redirect()->route('products.create')->with('error', 'Payment declined. Please try again.');
    }

    public function webhook()
    {
        $payload = request()->getContent();
        $signature = request()->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        if (!$secret) {
            Log::error('Stripe webhook secret is not configured.');
            return response()->json(['message' => 'Webhook not configured.'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Throwable $throwable) {
            Log::warning('Invalid Stripe webhook received.', [
                'error' => $throwable->getMessage(),
            ]);

            return response()->json(['message' => 'Invalid webhook.'], 400);
        }

        $eventPayload = json_decode($payload, true) ?? [];
        $eventLog = PaymentEventLog::firstOrCreate([
            'provider' => 'stripe',
            'provider_event_id' => $event->id,
        ], [
            'event_type' => $event->type,
            'payload' => $eventPayload,
        ]);

        if ($eventLog->processed_at) {
            return response()->json(['received' => true]);
        }

        $sessionObject = $event->data->object;
        $checkoutId = $sessionObject->metadata->checkout_id ?? null;
        $checkout = $checkoutId ? PaidSubmissionCheckout::find($checkoutId) : null;

        if ($checkout) {
            $eventLog->paid_submission_checkout_id = $checkout->id;
            $eventLog->save();
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    if (($sessionObject->metadata->type ?? null) === 'paid-submission' && $checkout) {
                        $checkout->forceFill([
                            'status' => 'paid',
                            'stripe_checkout_session_id' => $sessionObject->id,
                            'stripe_payment_intent_id' => $sessionObject->payment_intent ?? null,
                            'stripe_event_id' => $event->id,
                            'paid_at' => $checkout->paid_at ?: now(),
                        ])->save();

                        $this->paidSubmissionService->fulfillCheckout(
                            $checkout,
                            $sessionObject->payment_intent ?? null,
                            $event->id
                        );
                    }
                    break;

                case 'checkout.session.async_payment_failed':
                case 'checkout.session.expired':
                    if ($checkout) {
                        $checkout->forceFill([
                            'status' => 'failed',
                            'failure_message' => 'Stripe reported that the checkout did not complete successfully.',
                            'stripe_event_id' => $event->id,
                        ])->save();
                    }
                    break;
            }
        } catch (\Throwable $throwable) {
            if ($checkout) {
                $this->paidSubmissionService->markCheckoutFailed($checkout, $throwable->getMessage());
            }

            return response()->json(['message' => 'Webhook handling failed.'], 500);
        }

        $eventLog->processed_at = now();
        $eventLog->save();

        return response()->json(['received' => true]);
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

    protected function authorizeCheckoutAccess(PaidSubmissionCheckout $checkout): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        if ((int) $checkout->user_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403);
        }
    }
}
