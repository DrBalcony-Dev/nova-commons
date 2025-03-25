<?php

namespace DrBalcony\NovaCommon\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Debug\ExceptionHandler;
use DrBalcony\NovaCommon\Handlers\ExceptionHandler as NovaExceptionHandler;
use function Sentry\init;

class SentryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Only configure Sentry if it's enabled in config
        if (config('nova-common.reporting.sentry.enabled', false)) {
            $this->configureSentry();
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Configure Sentry integration
     *
     * @return void
     */
    protected function configureSentry(): void
    {
        // Skip if Sentry SDK isn't installed
        if (!class_exists('\Sentry\SentrySdk')) {
            return;
        }

        // Configure Sentry from Laravel config
        $dsn = config('sentry.dsn') ?? env('SENTRY_LARAVEL_DSN');

        if (!$dsn) {
            return;
        }

        init([
            'dsn' => $dsn,
            'environment' => app()->environment(),
            'release' => config('app.version', '1.0.0'),
//            'traces_sample_rate' => config('nova-common.reporting.sentry.traces_sample_rate', 0.1),
        ]);
    }
}