<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$profileStartup = ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET'
    && in_array($_SERVER['REQUEST_URI'] ?? '', ['/', '/up'], true)
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    && ($_SERVER['HTTP_X_STARTUP_PROFILE'] ?? '') === '1';
$entryTime = $profileStartup ? hrtime(true) : 0;

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

$autoloadedAt = $profileStartup ? hrtime(true) : 0;

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

if (! $profileStartup) {
    $app->handleRequest(Request::capture());

    return;
}

$createdAt = hrtime(true);
$bootedAt = null;
$stageStartedAt = [];
$stageDurations = [];
$app['events']->listen('bootstrapping: *', static function (string $event) use (&$stageStartedAt): void {
    $stageStartedAt[substr($event, strlen('bootstrapping: '))] = hrtime(true);
});
$app['events']->listen('bootstrapped: *', static function (string $event) use (&$stageStartedAt, &$stageDurations): void {
    $class = substr($event, strlen('bootstrapped: '));
    if (isset($stageStartedAt[$class])) {
        $stageDurations[class_basename($class)] = sprintf('%.1fms', (hrtime(true) - $stageStartedAt[$class]) / 1_000_000);
    }
});
$app->afterBootstrapping(BootProviders::class, static function () use (&$bootedAt): void {
    $bootedAt = hrtime(true);
});

$request = Request::capture();
$kernel = $app->make(Kernel::class);
$kernelReadyAt = hrtime(true);
$response = $kernel->handle($request);
$handledAt = hrtime(true);

if ($bootedAt !== null && $response->isSuccessful()) {
    $response->headers->set('X-Startup-Profile', sprintf(
        'autoload=%.1fms; app_create=%.1fms; kernel_setup=%.1fms; bootstrap=%.1fms; pipeline=%.1fms; php_total=%.1fms',
        ($autoloadedAt - $entryTime) / 1_000_000,
        ($createdAt - $autoloadedAt) / 1_000_000,
        ($kernelReadyAt - $createdAt) / 1_000_000,
        ($bootedAt - $kernelReadyAt) / 1_000_000,
        ($handledAt - $bootedAt) / 1_000_000,
        ($handledAt - $entryTime) / 1_000_000,
    ));
    $stages = [];
    foreach ($stageDurations as $stage => $duration) {
        $stages[] = $stage.'='.$duration;
    }
    $response->headers->set('X-Bootstrap-Profile', implode('; ', $stages));
}

$response->send();
$kernel->terminate($request, $response);
