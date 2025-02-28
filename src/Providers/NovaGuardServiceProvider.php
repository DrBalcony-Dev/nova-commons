<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Utils\Auth\NovaGuard;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class NovaGuardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Auth::extend(NovaGuard::GUARD_NAME, function (Container $app) {
            return new NovaGuard($app['request']);
        });
    }
}