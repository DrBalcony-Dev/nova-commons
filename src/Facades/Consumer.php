<?php

namespace DrBalcony\NovaCommon\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the RabbitMQ Consumer client.
 *
 * @method static bool consume(string $queueName, callable $callback, array $options = [])
 * @method static bool processMessages(int $timeout = 0)
 * @method static bool stopConsuming()
 * @method static bool close()
 * @method static string|null getLastError()
 *
 * @see \DrBalcony\NovaCommon\Services\RabbitMQ\ConsumerClient
 */
class Consumer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'app.rabbitmq.consumer';
    }
}
