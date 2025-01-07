<?php

namespace DrBalcony\NovaCommon\Services;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    protected  ?AMQPStreamConnection $connection= null;
    protected  ?AMQPChannel $channel = null;

    public function __construct()
    {
        // Initialize the connection and channel once, and reuse them
        $this->initializeConnection();
    }

    /**
     * Initialize the RabbitMQ connection and channel.
     *
     * @return void
     */
    private function initializeConnection(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('nova-common.rabbitmq.host'),   // Fetch from config
                config('nova-common.rabbitmq.port'),   // Fetch from config
                config('nova-common.rabbitmq.user'),   // Fetch from config
                config('nova-common.rabbitmq.password'), // Fetch from config
            );

            $this->channel = $this->connection->channel();
        }
    }

    /**
     * Publish a message to the specified RabbitMQ queue.
     *
     * @param string $message
     * @param string $queue
     * @return void
     */
    public function publish(string $message, string $queue = ''): void
    {
        try {
            // Ensure the connection and channel are initialized
            $this->initializeConnection();

            // Declare the queue (ensure it exists)
            $this->channel->queue_declare($queue ?? config('nova-common.rabbitmq.queues.default'), false, true, false, false);

            // Create the message
            $msg = new AMQPMessage($message);

            // Publish the message to the specified queue
            $this->channel->basic_publish($msg, '', $queue);


        } catch (\Exception $e) {
            // Log the error
            Log::error('RabbitMQ Publish Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Close the connection and channel (optional, but can be used during shutdown).
     *
     * @return void
     */
    public function closeConnection(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }

        if ($this->connection) {
            $this->connection->close();
        }
    }
}
