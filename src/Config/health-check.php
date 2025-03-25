<?php
return [
    /*
        * A result store is responsible for saving the results of the checks.
        */
    'result_stores' => [
        Spatie\Health\ResultStores\EloquentHealthResultStore::class => [
            'connection' => env('HEALTH_DB_CONNECTION', env('DB_CONNECTION')),
            'model' => Spatie\Health\Models\HealthCheckResultHistoryItem::class,
            'keep_history_for_days' => 5,
        ],
    ],

    /*
     * You can get notified when specific events occur. Out of the box you can use 'mail' and 'slack'.
     */
    'notifications' => [
        /*
         * Notifications will only get sent if this option is set to `true`.
         */
        'enabled' => env('HEALTH_NOTIFICATIONS_ENABLED', false),

        /*
         * When checks start failing, you could potentially end up getting
         * a notification every minute.
         *
         * With this setting, notifications are throttled. By default, you'll
         * only get one notification per hour.
         */
        'throttle_notifications_for_minutes' => env('HEALTH_NOTIFICATIONS_THROTTLE_MINUTES', 60),
        'throttle_notifications_key' => 'health:latestNotificationSentAt:',

        'notifications' => [
            Spatie\Health\Notifications\CheckFailedNotification::class => [
                'mail', 'slack'
            ],
        ],

        /*
         * Here you can specify the notifiable to which the notifications should be sent.
         * The default notifiable will use the variables specified in this config file.
         */
        'notifiable' => Spatie\Health\Notifications\Notifiable::class,

        'mail' => [
            'to' => env('HEALTH_MAIL_TO', 'your@example.com'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => env('HEALTH_SLACK_WEBHOOK_URL', ''),
            'channel' => env('HEALTH_SLACK_CHANNEL', '#health-notifications'),
            'username' => env('HEALTH_SLACK_USERNAME', 'Health Status'),
            'icon' => env('HEALTH_SLACK_ICON', ':medical_symbol:'),
        ],
    ],

    /*
     * You can customize which checks to run
     */
    'checks' => [
        'enabled' => [
            'used_disk_space' => true,
            'database' => true,
            'database_connection_count' => true,
            'cache' => true,
            'environment' => true,
            'debug_mode' => true,
            'schedule' => true,
            'security_advisories' => true,
            'rabbitmq' => true,
        ],
        'used_disk_space' => [
            'warning_threshold_percentage' => 70,
            'error_threshold_percentage' => 90,
        ],
        'database_connection_count' => [
            'warning_threshold' => 50,
            'error_threshold' => 100,
        ],
        'environment' => [
            'expected' => env('APP_ENV', 'production'),
        ],
    ],

    /*
     * Theme configuration for the health check page
     */
    'theme' => [
        'views' => [
            'mail' => 'health::mail',
            'slack' => 'health::slack',
        ],
    ],

    /*
     * Route configuration
     */
    'route' => [
        'enabled' => true,
        'path' => 'health',
        'middleware' => ['web'],
    ],
];