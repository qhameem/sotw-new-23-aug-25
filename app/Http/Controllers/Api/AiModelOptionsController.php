<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiProviderRoutingService;

class AiModelOptionsController extends Controller
{
    public function __invoke(AiProviderRoutingService $router)
    {
        return response()->json(['models' => $router->selectableModels()]);
    }
}
