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
];
