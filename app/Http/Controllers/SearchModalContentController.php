<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SearchModalContentController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('partials._global-search-modal');
    }
}
