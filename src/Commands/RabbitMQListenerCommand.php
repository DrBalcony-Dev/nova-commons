<?php

namespace DrBalcony\NovaCommon\Commands;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQListenerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nova-common:listen-rabbitmq {queue?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to RabbitMQ messages directly and process them.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $queueName = $this->argument('queue') ?? config('nova-common.rabbitmq.queues.default');

        $this->info("Listening to RabbitMQ queue: {$queueName}");


        $connection = new AMQPStreamConnection(
            config('nova-common.rabbitmq.host'),   // Fetch from config
            config('nova-common.rabbitmq.port'),   // Fetch from config
            config('nova-common.rabbitmq.user'),   // Fetch from config
            config('nova-common.rabbitmq.password'), // Fetch from config
        );

        $channel = $connection->channel();

        // Declare the queue (this ensures the queue exists)
        $channel->queue_declare($queueName, false, true, false, false);

        // Callback to process the messages
        $callback = function (AMQPMessage $msg) {
            $this->info("Received message: " . $msg->getBody());

            // Process the message (add your own logic here)
            $this->processMessage($msg->getBody());
        };

        // Start consuming messages from the queue
        $this->info("Waiting for messages. To exit press CTRL+C.");
        $channel->basic_consume($queueName, '', false, true, false, false, $callback);

        // Wait for messages
        while($channel->is_consuming()) {
            $channel->wait();
        }

        // Close the channel and connection when done
        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }

    /**
     * Process the message from RabbitMQ.
     *
     * @param string $message
     * @return void
     */
    private function processMessage(string $message): void
    {
        // Example: Log the message
        \Log::info('RabbitMQ Message:', ['message' => $message]);

        // Or dispatch an event, send it to a database, etc.
    }
}