<?php

use DrBalcony\NovaCommon\Jobs\SampleConsumerJob;
return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'queues' => [
            'default' => env('RABBITMQ_QUEUE_DEFAULT', 'default'),
            'log' => env('RABBITMQ_QUEUE_LOG', 'log'),
            'exception' => env('RABBITMQ_QUEUE_EXCEPTION', 'exception'),
        ],

        /**
         * Consume RabbitMQ configurations
         *
         */
        'consume' => [
            // Extend the abstract ConsumerJob class and put the full class name here.
            'job' => SampleConsumerJob::class, 
        ]
    ],

    'earth'=>[
        'base-url'=> env('EARTH_BASE_URL', 'https://nova.drbalcony.dev/earth'),
        'auth-cache-driver'=> env('EARTH_CACHE_DRIVER', 'redis'), // database, file ...
        'auth-cache-connection'=> env('EARTH_CACHE_CONNECTION', 'default'),
        'client-token'=> env('EARTH_CLIENT_TOKEN','')
    ],

    'phone' => [
        'default_region' => env('PHONE_DEFAULT_REGION', 'US'),
    ],

    'permission' => [
        'verify_endpoint' => env('PERMISSION_VERIFY_ENDPOINT', 'https://nova.drbalcony.dev/earth/api/api/permissions/verify'),
        'should_log_invalid_request' => env("SHOULD_LOG_INVALID_REQUEST", true),
    ],

    'reporting' => [
        // Exceptions that shouldn't be reported
        'dont_report' => [
            \Illuminate\Validation\ValidationException::class,
        ],

        // RabbitMQ reporting configuration
        'rabbitmq' => [
            'enabled' => env('NOVA_REPORT_RABBITMQ_ENABLED', false),
        ],

        // Sentry reporting configuration
        'sentry' => [
            'enabled' => env('NOVA_REPORT_SENTRY_ENABLED', true),
            'include_context' => true,
            'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        ],
    ],


    /*
     * Health check configuration
     */
    'health' => require __DIR__ . '/health-check.php',
];
