<?php

namespace DrBalcony\NovaCommon\Commands;

use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
use Illuminate\Console\Command;

class PublishRabbitMQMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:publish
                            {queue : The name of the queue to publish to}
                            {message : The JSON-encoded message payload}
                            {--P|properties= : Optional JSON-encoded message properties}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a message to a RabbitMQ queue';

    /**
     * Execute the console command.
     *
     * @param \DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient $publisher
     * @return int
     */
    public function handle(PublisherClient $publisher)
    {
        $queueName = $this->argument('queue');
        $message = json_decode($this->argument('message'), true);
        $properties = $this->option('properties') ? json_decode($this->option('properties'), true) : [];

        $published = $publisher->publish($queueName, $message, $properties);

        if ($published) {
            $this->info('Message published to queue: '.$queueName);
            return 0;
        } else {
            $this->error('Failed to publish message: ' . $publisher->getLastError());
            return 1;
        }
    }
} 