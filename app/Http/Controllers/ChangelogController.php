<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelogs = Changelog::latest('released_at')->get()->groupBy(function ($log) {
            return $log->released_at->format('F j, Y');
        });
        return view('changelogs.index', compact('changelogs'));
    }
}