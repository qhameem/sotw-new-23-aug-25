<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthMagicLink;
use App\Models\User;
use App\Notifications\MagicLoginLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    private const EXPIRY_MINUTES = 15;

    private const THROTTLE_ATTEMPTS = 5;

    private const THROTTLE_SECONDS = 300;

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'intended' => ['nullable', 'string', 'max:2000'],
        ]);

        $email = Str::lower($validated['email']);
        $throttleKey = Str::transliterate($email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, self::THROTTLE_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]),
            ]);
        }

        RateLimiter::hit($throttleKey, self::THROTTLE_SECONDS);

        $user = User::where('email', $email)->first();
        $plainToken = Str::random(64);

        $magicLink = AuthMagicLink::create([
            'user_id' => $user?->id,
            'email' => $email,
            'token_hash' => hash('sha256', $plainToken),
            'redirect_to' => $this->normalizeRedirectTo($validated['intended'] ?? null),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'requested_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        $url = URL::temporarySignedRoute(
            'auth.magic-link.consume',
            $magicLink->expires_at,
            [
                'magic_link' => $magicLink->id,
                'token' => $plainToken,
            ]
        );

        Notification::route('mail', $email)->notify(
            new MagicLoginLinkNotification($url, self::EXPIRY_MINUTES)
        );

        return back()->with('status', 'magic-link-sent');
    }

    public function consume(Request $request, AuthMagicLink $magicLink): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('login')->withErrors([
                'email' => 'That sign-in link is invalid or has been modified.',
            ]);
        }

        $token = (string) $request->query('token');

        if ($token === '' || ! hash_equals($magicLink->token_hash, hash('sha256', $token))) {
            return redirect()->route('login')->withErrors([
                'email' => 'That sign-in link is invalid.',
            ]);
        }

        if ($magicLink->hasBeenConsumed() || $magicLink->isExpired()) {
            return redirect()->route('login')->withErrors([
                'email' => 'That sign-in link has expired. Please request a new one.',
            ]);
        }

        $user = User::firstOrCreate(
            ['email' => $magicLink->email],
            [
                'name' => null,
                'password' => null,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $magicLink->forceFill([
            'user_id' => $user->id,
            'consumed_at' => now(),
        ])->save();

        AuthMagicLink::query()
            ->where('email', $magicLink->email)
            ->whereNull('consumed_at')
            ->whereKeyNot($magicLink->id)
            ->update(['consumed_at' => now()]);

        Auth::login($user, true);
        $request->session()->regenerate();

        if (blank($user->name)) {
            return redirect()->route('auth.complete-profile.show');
        }

        return redirect()->to($magicLink->redirect_to ?: route('home'));
    }

    public function showCompleteProfile(Request $request): View|RedirectResponse
    {
        if (filled($request->user()->name)) {
            return redirect()->route('home');
        }

        return view('auth.complete-profile');
    }

    public function completeProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->forceFill([
            'name' => $validated['name'],
        ])->save();

        return redirect()->intended(route('home'))->with('status', 'profile-completed');
    }

    private function normalizeRedirectTo(?string $intended): ?string
    {
        if (blank($intended)) {
            return null;
        }

        $appUrl = parse_url(config('app.url'));
        $candidate = parse_url($intended);

        if ($candidate === false) {
            return null;
        }

        if (! isset($candidate['scheme'], $candidate['host'])) {
            return str_starts_with($intended, '/') ? $intended : null;
        }

        if (
            ($candidate['host'] ?? null) !== ($appUrl['host'] ?? null)
            || ($candidate['scheme'] ?? null) !== ($appUrl['scheme'] ?? null)
        ) {
            return null;
        }

        $path = $candidate['path'] ?? '/';
        $query = isset($candidate['query']) ? '?'.$candidate['query'] : '';

        return $path.$query;
    }
}
