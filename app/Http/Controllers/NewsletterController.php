<?php

namespace App\Http\Controllers;

use App\Jobs\SyncNewsletterSubscriberToEmailOctopus;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function index(): View
    {
        return view('newsletter.index');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            (string) config('services.emailoctopus.api_key') !== ''
                && (string) config('services.emailoctopus.list_id') !== '',
            503,
            'Newsletter subscriptions are temporarily unavailable.'
        );

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'max:0'],
            'consent' => ['accepted'],
        ]);

        $subscriber = NewsletterSubscriber::updateOrCreate(
            ['email' => mb_strtolower(trim($validated['email']))],
            [
                'first_name' => filled($validated['first_name'] ?? null) ? trim($validated['first_name']) : null,
                'status' => 'pending',
                'source' => 'newsletter_page',
                'consented_at' => now(),
                'synced_at' => null,
                'last_error' => null,
            ]
        );

        SyncNewsletterSubscriberToEmailOctopus::dispatch($subscriber->id);

        return back()->with('newsletter_success', 'You are subscribed. Check your inbox for future updates.');
    }
}
