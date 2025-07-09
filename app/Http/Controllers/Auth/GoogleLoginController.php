<?php

    namespace App\Http\Controllers\Auth;

    use App\Http\Controllers\Controller;
    use App\Models\User;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;
    use Laravel\Socialite\Facades\Socialite;
    use Exception;

    class GoogleLoginController extends Controller
    {
        /**
         * Redirect the user to the Google authentication page.
         */
        public function redirectToGoogle()
        {
            return Socialite::driver('google')->redirect();
        }

        /**
         * Obtain the user information from Google.
         */
        public function handleGoogleCallback()
        {
            try {
                $googleUser = Socialite::driver('google')->user();

                // Find user by google_id
                $user = User::where('google_id', $googleUser->getId())->first();

                if ($user) {
                    // User found by google_id, log them in
                    Auth::login($user, true); // true for "remember me"
                    return redirect()->intended('/'); // Redirect to home page
                }

                // User not found by google_id, try to find by email
                $user = User::where('email', $googleUser->getEmail())->first();

                if ($user) {
                    // User found by email, update their google_id and avatar
                    $user->google_id = $googleUser->getId();
                    $user->google_avatar = $googleUser->getAvatar();
                    $user->save();
                } else {
                    // No user found by email, create a new user
                    $user = User::create([
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'google_avatar' => $googleUser->getAvatar(),
                        'password' => Hash::make(Str::random(24)), // Generate a random password
                        'email_verified_at' => now(), // Consider email verified via Google
                    ]);
                }

                Auth::login($user, true);
                return redirect()->intended('/'); // Redirect to home page

            } catch (Exception $e) {
                // Log the error or show a generic error message
                // \Log::error('Google authentication error: ' . $e->getMessage());
                return redirect()->route('login')->withErrors(['email' => 'Unable to login using Google. Please try again.']);
            }
        }
    }
