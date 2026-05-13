<?php

namespace App\Http\Controllers;

use App\Services\IndexNowService;
use Illuminate\Http\Response;

class IndexNowKeyController extends Controller
{
    public function __invoke(string $key, IndexNowService $indexNow): Response
    {
        abort_unless($indexNow->key() && hash_equals($indexNow->key(), $key), 404);

        return response($key . PHP_EOL, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
