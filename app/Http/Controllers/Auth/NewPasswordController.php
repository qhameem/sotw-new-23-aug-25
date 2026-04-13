<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Password reset has been replaced by email sign-in links.');
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('login')->with('status', 'Password reset has been replaced by email sign-in links.');
    }
}
