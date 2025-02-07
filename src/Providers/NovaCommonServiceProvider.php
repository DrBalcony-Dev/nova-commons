<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Commands\RabbitMQListenerCommand;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;
use Illuminate\Support\ServiceProvider;


class NovaCommonServiceProvider extends ServiceProvider
{
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

        $this->mergeConfigFrom(__DIR__ . '/../Config/nova-common.php', 'nova-common');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../Config/nova-common.php' => config_path('nova-common.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQListenerCommand::class,
            ]);
        }
    }
}