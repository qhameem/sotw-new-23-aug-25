<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TechStackDetectorService;
use App\Models\TechStack;

class TechStackController extends Controller
{
    protected $detector;

    public function __construct(TechStackDetectorService $detector)
    {
        $this->detector = $detector;
    }

    public function detect(Request $request)
    {
        $request->validate(['url' => 'required|url']);
        $url = $request->input('url');

        $detectedNames = $this->detector->detect($url);

        if (empty($detectedNames)) {
            return response()->json([]);
        }

        $techStacks = TechStack::whereIn('name', $detectedNames)->get();

        return response()->json($techStacks);
    }
}
