<?php

namespace DrBalcony\NovaCommon\Providers;

use DrBalcony\NovaCommon\Utils\Checks\RabbitmqCheck;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
use Spatie\Health\HealthServiceProvider as SpatieHealthServiceProvider;
use Spatie\Health\Notifications\CheckFailedNotification;
use Spatie\Health\ResultStores\ResultStore;
use Spatie\SecurityAdvisoriesHealthCheck\SecurityAdvisoriesCheck;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register Spatie Health if not already registered
        if (!$this->app->providerIsLoaded(SpatieHealthServiceProvider::class)) {
            $this->app->register(SpatieHealthServiceProvider::class);
        }

        // Set up result stores via config
        $resultStoresConfig = config('nova-common.health.result_stores', []);
        if (!empty($resultStoresConfig)) {
            config(['health.result_stores' => $resultStoresConfig]);
        }

        // Configure notifications with fail-safe defaults
        $this->configureNotifications();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Register health checks
        $checks = $this->getEnabledChecks();
        if (!empty($checks)) {
            Health::checks($checks);
        }

        // Log configuration for debugging
        if (app()->environment('local', 'development', 'testing')) {
            $this->logHealthConfiguration();
        }
    }

    /**
     * Configure health notifications ensuring Slack is properly set up
     *
     * @return void
     */
    protected function configureNotifications(): void
    {
        // Get notification config
        $notificationsEnabled = config('nova-common.health.notifications.enabled', env('HEALTH_NOTIFICATIONS_ENABLED', false));

        // Only proceed if notifications are enabled
        if (!$notificationsEnabled) {
            return;
        }

        // Get webhook URL - this is critical for Slack notifications
        $webhookUrl = env('HEALTH_SLACK_WEBHOOK_URL');
        if (empty($webhookUrl)) {
            Log::warning('Health check Slack notifications enabled but HEALTH_SLACK_WEBHOOK_URL is not set');
            return;
        }

        // Configure notification channels
        $notificationChannels = ['slack']; // Force slack as the channel
        if (config('nova-common.health.notifications.mail.enabled', false)) {
            $notificationChannels[] = 'mail';
        }

        // Set up full notification config directly
        Config::set([
            'health.notifications.enabled' => true,
            'health.notifications.notifications' => [
                CheckFailedNotification::class => $notificationChannels,
            ],
            'health.notifications.notifiable' => config('nova-common.health.notifications.notifiable', \Spatie\Health\Notifications\Notifiable::class),
            'health.notifications.throttle_notifications_for_minutes' => env('HEALTH_NOTIFICATIONS_THROTTLE_MINUTES', 60),
            'health.notifications.throttle_notifications_key' => 'health:latestNotificationSentAt:',
        ]);

        // Set up Slack config directly
        Config::set([
            'health.notifications.slack' => [
                'webhook_url' => $webhookUrl,
                'channel' => env('HEALTH_SLACK_CHANNEL', '#health-notifications'),
                'username' => env('HEALTH_SLACK_USERNAME', 'Health Monitor'),
                'icon' => env('HEALTH_SLACK_ICON', ':rotating_light:'),
            ]
        ]);

        // Mail configuration if needed
        if (in_array('mail', $notificationChannels)) {
            Config::set([
                'health.notifications.mail' => [
                    'to' => env('HEALTH_MAIL_TO', config('nova-common.health.notifications.mail.to')),
                    'from' => [
                        'address' => env('MAIL_FROM_ADDRESS', config('mail.from.address')),
                        'name' => env('MAIL_FROM_NAME', config('mail.from.name')),
                    ],
                ]
            ]);
        }
    }

    /**
     * Get the list of enabled health checks
     *
     * @return array
     */
    protected function getEnabledChecks(): array
    {
        $config = config('nova-common.health.checks');
        $checks = [];

        // Add UsedDiskSpaceCheck if enabled
        if ($config['enabled']['used_disk_space'] ?? true) {
            $checks[] = UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage($config['used_disk_space']['warning_threshold_percentage'] ?? 70)
                ->failWhenUsedSpaceIsAbovePercentage($config['used_disk_space']['error_threshold_percentage'] ?? 90);
        }

        // Add DatabaseConnectionCountCheck if enabled
        if ($config['enabled']['database_connection_count'] ?? true) {
            $checks[] = DatabaseConnectionCountCheck::new()
                ->warnWhenMoreConnectionsThan($config['database_connection_count']['warning_threshold'] ?? 50)
                ->failWhenMoreConnectionsThan($config['database_connection_count']['error_threshold'] ?? 100);
        }

        // Add DatabaseCheck if enabled
        if ($config['enabled']['database'] ?? true) {
            $checks[] = DatabaseCheck::new();
        }

        // Add CacheCheck if enabled
        if ($config['enabled']['cache'] ?? true) {
            $checks[] = CacheCheck::new();
        }

        // Add EnvironmentCheck if enabled
        if ($config['enabled']['environment'] ?? true) {
            $checks[] = EnvironmentCheck::new()
                ->expectEnvironment($config['environment']['expected'] ?? app()->environment());
        }

        // Add ScheduleCheck if enabled
        if ($config['enabled']['schedule'] ?? true) {
            $checks[] = ScheduleCheck::new();
        }

        // Add SecurityAdvisoriesCheck if enabled
        if ($config['enabled']['security_advisories'] ?? true) {
            $checks[] = SecurityAdvisoriesCheck::new();
        }

        // Add DebugModeCheck if enabled
        if ($config['enabled']['debug_mode'] ?? true) {
            $checks[] = DebugModeCheck::new();
        }

        // Add RabbitmqCheck if enabled
        if ($config['enabled']['rabbitmq'] ?? true) {
            $checks[] = RabbitmqCheck::new();
        }

        return $checks;
    }

    /**
     * Log health configuration for debugging
     *
     * @return void
     */
    protected function logHealthConfiguration(): void
    {
        Log::debug('Health check configuration', [
            'notifications_enabled' => config('health.notifications.enabled'),
            'webhook_url' => config('health.notifications.slack.webhook_url') ? 'Set' : 'Not set',
            'slack_channel' => config('health.notifications.slack.channel'),
            'channels' => config('health.notifications.notifications'),
            'throttle_minutes' => config('health.notifications.throttle_notifications_for_minutes'),
        ]);
    }
}