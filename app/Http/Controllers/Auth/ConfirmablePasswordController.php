<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('profile.edit')->with('status', 'Password confirmation is not required for email-link accounts.');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('profile.edit'));
    }
}
