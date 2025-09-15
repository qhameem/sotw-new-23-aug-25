<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Badge;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badges = Badge::all();
        return view('site.badges.index', compact('badges'));
    }
}
