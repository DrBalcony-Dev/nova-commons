<?php

namespace DrBalcony\NovaCommon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel Facade for RabbitMQ message publishing.
 *
 * This facade provides a convenient static interface to the RabbitMQ publisher client,
 * allowing for easy message publishing to RabbitMQ queues throughout the application.
 *
 * @method static bool publish(string $queueName, array $message, array $properties = [])
 *                                                                                        Publishes a message to a RabbitMQ queue.
 *                                                                                        - $queueName: The name of the queue to publish to
 *                                                                                        - $message: The message payload as an array (will be JSON encoded)
 *                                                                                        - $properties: Optional message properties (delivery mode, content type, etc.)
 *                                                                                        Returns true if the message was published successfully, false otherwise.
 *
 * @see \DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient
 */
class Publisher extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * This binds the facade to the 'rabbitmq.publisher' service
     * which is registered in the NovaCommonServiceProvider.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'rabbitmq.publisher';
    }
} 