<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthMagicLink;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MagicLinkController extends Controller
{
    private const EXPIRY_MINUTES = 15;

    private const EMAIL_THROTTLE_ATTEMPTS = 5;

    private const IP_THROTTLE_ATTEMPTS = 10;

    private const THROTTLE_SECONDS = 300;

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'intended' => ['nullable', 'string', 'max:2000'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = Str::lower($validated['email']);
        $emailThrottleKey = $this->sendThrottleKey($email, $request);
        $ipThrottleKey = $this->sendIpThrottleKey($request);

        $this->ensureNotRateLimited($emailThrottleKey, self::EMAIL_THROTTLE_ATTEMPTS, 'email');
        $this->ensureNotRateLimited($ipThrottleKey, self::IP_THROTTLE_ATTEMPTS, 'email');

        if (filled($validated['company_name'] ?? null)) {
            return back()
                ->with('status', 'otp-sent')
                ->with('auth_email', $email);
        }

        RateLimiter::hit($emailThrottleKey, self::THROTTLE_SECONDS);
        RateLimiter::hit($ipThrottleKey, self::THROTTLE_SECONDS);

        $user = User::where('email', $email)->first();
        $otp = $this->generateOtp();

        AuthMagicLink::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $magicLink = AuthMagicLink::create([
            'user_id' => $user?->id,
            'email' => $email,
            'token_hash' => hash('sha256', Str::uuid()->toString().Str::random(64)),
            'otp_code_hash' => hash('sha256', $otp),
            'redirect_to' => $this->normalizeRedirectTo($validated['intended'] ?? null),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'requested_ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
        ]);

        Notification::route('mail', $email)->notify(
            new EmailOtpNotification($otp, self::EXPIRY_MINUTES)
        );

        return back()
            ->with('status', 'otp-sent')
            ->with('auth_email', $email);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = Str::lower($validated['email']);
        $otp = (string) $validated['otp'];
        $throttleKey = $this->verifyThrottleKey($email, $request);

        $this->ensureNotRateLimited($throttleKey, self::EMAIL_THROTTLE_ATTEMPTS, 'otp');

        $magicLink = AuthMagicLink::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $magicLink || $magicLink->isExpired()) {
            RateLimiter::hit($throttleKey, self::THROTTLE_SECONDS);

            throw ValidationException::withMessages([
                'otp' => 'That one-time code has expired. Please request a new code.',
            ]);
        }

        if (
            blank($magicLink->otp_code_hash)
            || ! hash_equals($magicLink->otp_code_hash, hash('sha256', $otp))
        ) {
            RateLimiter::hit($throttleKey, self::THROTTLE_SECONDS);

            throw ValidationException::withMessages([
                'otp' => 'That one-time code is invalid.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $this->authenticate($request, $magicLink);
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

        return $this->authenticate($request, $magicLink);
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

    private function authenticate(Request $request, AuthMagicLink $magicLink): RedirectResponse
    {
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
            return redirect()
                ->route('auth.complete-profile.show')
                ->with('auth_sync_event', 'signed-in');
        }

        return redirect()
            ->to($magicLink->redirect_to ?: route('home'))
            ->with('auth_sync_event', 'signed-in');
    }

    private function ensureNotRateLimited(string $throttleKey, int $maxAttempts, string $field): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            $field => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    private function sendThrottleKey(string $email, Request $request): string
    {
        return 'auth-email-send:'.Str::transliterate($email.'|'.$request->ip());
    }

    private function sendIpThrottleKey(Request $request): string
    {
        return 'auth-email-send-ip:'.Str::transliterate((string) $request->ip());
    }

    private function verifyThrottleKey(string $email, Request $request): string
    {
        return 'auth-email-verify:'.Str::transliterate($email.'|'.$request->ip());
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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
