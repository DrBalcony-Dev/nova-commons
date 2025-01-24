<?php

namespace DrBalcony\NovaCommon\Traits;

use DrBalcony\NovaCommon\Enums\Priority;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
            $connection = AMQPStreamConnection::create_connection(Config::get('queue.connections.rabbitmq.hosts'));
            $channel = $connection->channel();

            $properties = array_merge([
                'content_encoding' => 'utf-8',
                'content_type' => 'application/json',
                'priority' => Priority::Low->value,
                'delivery_mode' => 2,
            ], $properties);

            $msg = new AMQPMessage(json_encode($data), $properties);
            $channel->basic_publish($msg, $exchangeName, $queueName);

            $channel->close();
            $connection->close();
        } catch (\Exception $e) {
            Log::error('RabbitMQ publishing error: '.$e->getMessage(), ['exception' => $e]);

            throw $e;
        }
    }
}
