<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Set the intended URL in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setIntendedUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $request->session()->put('url.intended', $request->input('url'));

        return response()->json(['status' => 'success']);
    }
}