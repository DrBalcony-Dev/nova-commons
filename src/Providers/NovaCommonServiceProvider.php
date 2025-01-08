<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Commands\RabbitMQListenerCommand;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;
use Illuminate\Support\ServiceProvider;


class NovaCommonServiceProvider extends ServiceProvider
{
    public function register()
    {

        // Register your service provider for macros
        $this->app->register(ResponseMacroServiceProvider::class);

        $this->app->singleton('DrBalcony\\NovaCommon\\Handlers\\ExceptionHandler');

        // Bind RabbitMQPublisher service as a singleton
        $this->app->singleton(RabbitMQPublisher::class, function ($app) {
            return new RabbitMQPublisher();
        });

        // Bind RabbitMQLogger service as a singleton
        $this->app->singleton(RabbitMQLogger::class, function ($app) {
            return new RabbitMQLogger();
        });

        $this->mergeConfigFrom(__DIR__ . '/../Config/nova-common.php', 'nova-common');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../Config/nova-common.php' => config_path('nova-common.php'),
        ]);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQListenerCommand::class,
            ]);
        }
    }
}