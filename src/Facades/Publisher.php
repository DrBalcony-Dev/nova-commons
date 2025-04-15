<?php

namespace DrBalcony\NovaCommon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the RabbitMQ Publisher client.
 *
 * @method static bool publish(string $queueName, array $message, array $properties = [])
 * @method static bool close()
 * @method static string|null getLastError()
 *
 * @see \App\Services\RabbitMQ\PublisherClient
 */
class Publisher extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app.rabbitmq.publisher';
    }
}