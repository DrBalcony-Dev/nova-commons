<?php


use DrBalcony\NovaCommon\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| Health check routes for monitoring application status.
|
*/

if (config('nova-common.health.route.enabled', true)) {
    Route::middleware(config('nova-common.health.route.middleware', ['web']))
        ->get(config('nova-common.health.route.path', 'health'), HealthCheckController::class)
        ->name('health.check');
}