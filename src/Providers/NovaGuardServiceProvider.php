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
        $useNovaGuard = config('nova-common.use_nova_guard');

        if ($useNovaGuard) {
            Auth::extend('nova', function (Container $app) {
                return new NovaGuard($app['request']);
            });
        }
    }
}