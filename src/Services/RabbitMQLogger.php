<?php

namespace DrBalcony\NovaCommon\Services;

use Illuminate\Support\Facades\Queue;


use Illuminate\Support\Facades\Log;

class RabbitMQLogger
{
    protected static $rabbitMQPublisher;

    /**
     * Initialize the RabbitMQPublisher statically.
     */
    protected static function initializePublisher(): void
    {
        // Lazy-load the RabbitMQPublisher once and reuse it
        if (self::$rabbitMQPublisher === null) {
            self::$rabbitMQPublisher = app(RabbitMQPublisher::class);
        }
    }

    /**
     * Log a message to RabbitMQ using the RabbitMQPublisher (statistically).
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log(string $message, array $context = [], string $logQueue=null): void
    {
        try {
            // Initialize the RabbitMQPublisher if not already done
            self::initializePublisher();

            // Format the message and context to send to RabbitMQ
            $formattedMessage = json_encode([
                'message' => $message,
                'context' => $context,
            ]);

            // Use RabbitMQPublisher to publish the log message to RabbitMQ
            self::$rabbitMQPublisher->publish($formattedMessage, $logQueue??config('nova-common.rabbitmq.queues.log')); // You can customize the queue name here


        } catch (\Exception $e) {
            // Log any errors
            Log::error('Error sending message to RabbitMQ', ['error' => $e->getMessage()]);
        }
    }
}
