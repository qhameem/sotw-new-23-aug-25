<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleLoginController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $intended = $this->normalizeRedirectTo($request->query('intended'));

        if ($intended !== null) {
            $request->session()->put('url.intended', $intended);
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Find user by google_id
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                Auth::login($user, true);

                return redirect()->intended('/')->with('auth_sync_event', 'signed-in');
            }

            // User not found by google_id, try to find by email
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->google_id = $googleUser->getId();
                $user->google_avatar = $googleUser->getAvatar();
                $user->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'password' => null,
                    'email_verified_at' => now(),
                ]);
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Auth::login($user, true);

            return redirect()->intended('/')->with('auth_sync_event', 'signed-in');
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Unable to login using Google. Please try again.']);
        }
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
