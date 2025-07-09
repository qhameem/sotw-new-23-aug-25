<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's notification preferences.
     */
    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        // Define all known preference keys that can be submitted from the form
        $knownPreferenceKeys = ['product_approval_notifications', 'newsletter_notifications']; // Add more as needed

        $processedPreferences = [];
        $submittedPreferences = $request->input('notification_preferences', []);

        foreach ($knownPreferenceKeys as $key) {
            // If a checkbox is checked, its key will be present in the submitted data (value is usually 'on' or its defined value).
            // If a checkbox is unchecked, its key will NOT be present in the submitted data.
            $processedPreferences[$key] = isset($submittedPreferences[$key]);
        }

        // Merge the processed boolean values back into the request for validation
        $request->merge(['notification_preferences' => $processedPreferences]);

        $validatedData = $request->validate([
            'notification_preferences' => ['required', 'array'],
            'notification_preferences.*' => ['boolean'], // Now this will correctly validate true/false
        ]);

        $user = $request->user();
        // Ensure the profile exists, or create it if it doesn't
        $profile = $user->profile()->firstOrCreate(
            ['user_id' => $user->id],
            ['notification_preferences' => []] // Default empty preferences if creating
        );
        
        // Update existing preferences with the new validated ones
        // This preserves any preferences not managed by this form
        $currentPreferences = $profile->notification_preferences ?? [];
        $newPreferences = array_merge($currentPreferences, $validatedData['notification_preferences']);
        
        $profile->notification_preferences = $newPreferences;
        $profile->save();

        return Redirect::route('profile.edit')->with('status', 'notification-preferences-updated');
    }
}
