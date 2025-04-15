<?php

namespace DrBalcony\NovaCommon\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use DrBalcony\NovaCommon\Commands\ConsumeCommand;
use DrBalcony\NovaCommon\Services\RabbitMQLogger;
use DrBalcony\NovaCommon\Commands\RedisCacheCommand;
use DrBalcony\NovaCommon\Services\PermissionService;
use DrBalcony\NovaCommon\Services\RabbitMQPublisher;
use DrBalcony\NovaCommon\Services\PhoneNumberService;
use DrBalcony\NovaCommon\Middleware\UserAuthMiddleware;
use DrBalcony\NovaCommon\Services\AuthenticationService;
use DrBalcony\NovaCommon\Middleware\ClientAuthMiddleware;
use DrBalcony\NovaCommon\Services\RabbitMQ\ConsumerClient;
use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
use DrBalcony\NovaCommon\Commands\RabbitMQ\ConsumeTestCommand;
use DrBalcony\NovaCommon\Middleware\CheckPermissionMiddleware;
use App\Console\Commands\RabbitMQ\TestConsumerConnectionCommand;
use DrBalcony\NovaCommon\Commands\RabbitMQ\PublishRabbitMQMessage;
use DrBalcony\NovaCommon\Commands\RabbitMQ\TestRabbitMQConnection;
use DrBalcony\NovaCommon\Commands\RabbitMQ\RabbitMQListenerCommand;
use DrBalcony\NovaCommon\Commands\RabbitMQ\PublishTestMessageCommand;

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

        $this->registerRabbitClients();

        $this->registerFacades();

        // TODO check if this is needed and remove it if not
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
        ], 'rabbitmq');

        if ($this->app->runningInConsole()) {
            $this->commands([
                // RabbitMQ commands
                ConsumeCommand::class,
                ConsumeTestCommand::class,
                PublishRabbitMQMessage::class,
                TestRabbitMQConnection::class,
                PublishTestMessageCommand::class,
                TestConsumerConnectionCommand::class,
                RabbitMQListenerCommand::class, // TODO remove this command after checking if it's not used anywhere.

                // Redis commands
                RedisCacheCommand::class,
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

    private function registerFacades(): void
    {
        $this->app->singleton('app.rabbitmq.consumer', function ($app) {
            return new ConsumerClient();
        });

        $this->app->singleton('app.rabbitmq.publisher', function ($app) {
            return new PublisherClient();
        });
    }

    private function registerRabbitClients(): void
    {
        // Register both ConsumerClient and PublisherClient as singletons.
        $this->app->singleton(ConsumerClient::class, function ($app) {
            return new ConsumerClient();
        });

        $this->app->singleton(PublisherClient::class, function ($app) {
            return new PublisherClient();
        });
    }
}