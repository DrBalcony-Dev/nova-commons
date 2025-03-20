<?php
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
];
