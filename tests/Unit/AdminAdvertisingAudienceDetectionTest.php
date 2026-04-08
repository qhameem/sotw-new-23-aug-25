<?php

use App\Http\Controllers\Admin\AdvertisingController;
use App\Services\CodeSnippetVisibilityService;
use Illuminate\Http\Request;

test('detect audience returns current request ip and country details', function () {
    $controller = new AdvertisingController();
    $service = new CodeSnippetVisibilityService();

    $request = Request::create('/admin/advertising/detect-audience', 'GET', server: [
        'REMOTE_ADDR' => '198.51.100.24',
    ]);
    $request->headers->set('CF-Connecting-IP', '203.0.113.44');
    $request->headers->set('CF-IPCountry', 'BD');

    $response = $controller->detectAudience($request, $service);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true))->toBe([
        'ip' => '203.0.113.44',
        'country_code' => 'BD',
        'country_name' => 'Bangladesh',
    ]);
});
