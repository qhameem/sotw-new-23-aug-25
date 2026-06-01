<?php

namespace App\Http\Controllers;

use App\Models\ToolUser;
use App\Support\ToolSettings;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ToolGoogleLoginController extends Controller
{
    public function __construct(private readonly ToolSettings $toolSettings) {}

    public function redirectToGoogle(Request $request): RedirectResponse
    {
        $intended = $this->normalizeRedirectTo($request->query('intended'));

        if ($intended !== null) {
            $request->session()->put('tool_auth.intended', $intended);
        }

        return Socialite::driver('google')
            ->redirectUrl(route('launch-readiness.auth.google.callback', [
                'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
            ]))
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('launch-readiness.auth.google.callback', [
                    'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
                ]))
                ->user();
            $user = ToolUser::query()->where('google_id', $googleUser->getId())->first();

            if (! $user) {
                $user = ToolUser::query()->where('email', $googleUser->getEmail())->first();
            }

            if ($user) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?: now(),
                ])->save();
            } else {
                $user = ToolUser::query()->create([
                    'name' => $googleUser->getName() ?: Str::headline(Str::before($googleUser->getEmail(), '@')),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                ]);
            }

            Auth::guard('tool_user')->login($user, true);

            return redirect()->to($request->session()->pull(
                'tool_auth.intended',
                $this->toolSettings->path(ToolSettings::LAUNCH_READINESS_KEY)
            ));
        } catch (Exception) {
            return redirect()->route('launch-readiness.auth.login', [
                'toolSlug' => $this->toolSettings->slug(ToolSettings::LAUNCH_READINESS_KEY),
            ])->withErrors([
                'email' => 'Unable to login using Google. Please try again.',
            ]);
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

        if (($candidate['host'] ?? null) !== ($appUrl['host'] ?? null) || ($candidate['scheme'] ?? null) !== ($appUrl['scheme'] ?? null)) {
            return null;
        }

        $path = $candidate['path'] ?? '/';
        $query = isset($candidate['query']) ? '?'.$candidate['query'] : '';

        return $path.$query;
    }
}
