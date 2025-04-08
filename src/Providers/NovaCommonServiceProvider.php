<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Commands\RabbitMQListenerCommand;
use DrBalcony\NovaCommon\Commands\RedisCacheCommand;
use DrBalcony\NovaCommon\Commands\PublishRabbitMQMessage;
use DrBalcony\NovaCommon\Commands\TestRabbitMQConnection;
use DrBalcony\NovaCommon\Middleware\CheckPermissionMiddleware;
use DrBalcony\NovaCommon\Middleware\ClientAuthMiddleware;
use DrBalcony\NovaCommon\Middleware\UserAuthMiddleware;
use DrBalcony\NovaCommon\Services\AuthenticationService;
use DrBalcony\NovaCommon\Services\PermissionService;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;
use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
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
        $this->mergeConfigFrom(__DIR__ . '/../Config/nova-common.php', 'nova-common');

        $this->app->singleton(PhoneNumberService::class, static function () {
            return new PhoneNumberService();
        });

        $this->app->register(SentryServiceProvider::class);

        $this->app->register(ResponseMacroServiceProvider::class);

        $this->app->register(CommandBlockerServiceProvider::class);

        $this->app->register(NovaGuardServiceProvider::class);

        // Register health check provider
        $this->app->register(HealthServiceProvider::class);

        $this->app->singleton('DrBalcony\\NovaCommon\\Handlers\\ExceptionHandler');

        // Register PublisherClient for RabbitMQ
        $this->app->singleton(PublisherClient::class, function ($app) {
            return new PublisherClient();
        });

        // Bind the PublisherClient to the 'rabbitmq.publisher' service for the facade
        $this->app->singleton('rabbitmq.publisher', function ($app) {
            return $app->make(PublisherClient::class);
        });

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

        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService();
        });
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
        ], 'nova-commons-config');

        $this->publishes([
            __DIR__ . '/../Config/rabbitmq-connection.php' => config_path('rabbitmq-connection.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                RabbitMQListenerCommand::class,
                RedisCacheCommand::class,
                PublishRabbitMQMessage::class,
                TestRabbitMQConnection::class,
            ]);

            // Load migrations
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Register middleware
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware('permission', CheckPermissionMiddleware::class);
        $router->aliasMiddleware('nova-user-auth', UserAuthMiddleware::class);
        $router->aliasMiddleware('nova-client-auth', ClientAuthMiddleware::class);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/health.php');
    }
}