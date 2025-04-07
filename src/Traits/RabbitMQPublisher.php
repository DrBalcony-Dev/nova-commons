<?php

namespace DrBalcony\NovaCommon\Traits;

use DrBalcony\NovaCommon\Enums\Priority;
use DrBalcony\NovaCommon\Facades\Publisher;
use Exception;
use Illuminate\Support\Facades\Log;

trait RabbitMQPublisher
{
    /**
     * Push a message to RabbitMQ.
     *
     * @param array $data The message payload.
     * @param string $queueName The RabbitMQ queue name.
     * @param array $properties The message properties.
     * @param string $exchangeName The RabbitMQ exchange name.
     * @return void
     *
     * @throws Exception
     */
    public function pushRawToRabbitMQ(array $data, string $queueName, array $properties = [], string $exchangeName = ''): void
    {
        try {
            $properties = array_merge([
                'content_encoding' => 'utf-8',
                'content_type' => 'application/json',
                'priority' => Priority::Low->value,
                'delivery_mode' => 2,
            ], $properties);

            // Use the Publisher facade which uses the PublisherClient
            $success = Publisher::publish($queueName, $data, $properties);
            
            if (!$success) {
                throw new Exception('Failed to publish message to RabbitMQ');
            }
        } catch (\Exception $e) {
            Log::error('RabbitMQ publishing error: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }
}
