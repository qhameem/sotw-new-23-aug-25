<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

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
$providerDurations = [];
$app->beforeBootstrapping(BootProviders::class, static function (Application $app) use (&$providerDurations): void {
    foreach ($app->getProviders(ServiceProvider::class) as $provider) {
        $startedAt = null;
        $provider->booting(static function () use (&$startedAt): void {
            $startedAt = hrtime(true);
        });
        $provider->booted(static function () use (&$startedAt, &$providerDurations, $provider): void {
            if ($startedAt !== null) {
                $providerDurations[$provider::class] = (hrtime(true) - $startedAt) / 1_000_000;
            }
        });
        unset($startedAt);
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

    arsort($providerDurations, SORT_NUMERIC);
    $providers = [];
    foreach (array_slice($providerDurations, 0, 8, true) as $provider => $duration) {
        $providers[] = sprintf('%s=%.1fms', class_basename($provider), $duration);
    }
    $providers[] = sprintf('measured_total=%.1fms', array_sum($providerDurations));
    $response->headers->set('X-Provider-Profile', implode('; ', $providers));
}

$response->send();
$kernel->terminate($request, $response);
