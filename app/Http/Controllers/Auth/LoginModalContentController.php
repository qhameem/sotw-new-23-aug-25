<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class LoginModalContentController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('auth.partials.login-modal-content');
    }
}
