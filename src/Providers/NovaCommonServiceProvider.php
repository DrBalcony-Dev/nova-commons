<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Commands\RabbitMQListenerCommand;
use DrBalcony\NovaCommon\Commands\RedisCacheCommand;
use DrBalcony\NovaCommon\Middleware\CheckPermissionMiddleware;
use DrBalcony\NovaCommon\Services\PermissionService;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class NovaCommonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(PhoneNumberService::class, static function () {
            return new PhoneNumberService();
        });

        $this->app->register(ResponseMacroServiceProvider::class);

        $this->app->singleton('DrBalcony\\NovaCommon\\Handlers\\ExceptionHandler');

        $this->app->singleton(RabbitMQPublisher::class, function ($app) {
            return new RabbitMQPublisher();
        });

        $this->app->singleton(RabbitMQLogger::class, function ($app) {
            return new RabbitMQLogger();
        });

        // Register permission service
        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });

        $this->mergeConfigFrom(__DIR__ . '/../Config/nova-common.php', 'nova-common');
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/nova-common.php' => config_path('nova-common.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQListenerCommand::class,
                RedisCacheCommand::class,
            ]);
        }

        // Register middleware
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('permission', CheckPermissionMiddleware::class);
    }
}