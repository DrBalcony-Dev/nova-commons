<?php

namespace App\Console\Commands\RabbitMQ;

use App\Services\RabbitMQ\ConsumerClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

class TestConsumerConnectionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:test-consumer-connection
                            {--queue=test_queue : The queue to connect and consume test messages from}
                            {--timeout=10 : Timeout in seconds for consuming messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the RabbitMQ consumer connection by consuming messages from a test queue';

    /**
     * Execute the console command.
     *
     * @param ConsumerClient $consumerInstance
     * @return int
     */
    public function handle(ConsumerClient $consumerInstance): int
    {
        $queue = $this->option('queue');
        $timeout = (int) $this->option('timeout');

        $this->info("Testing RabbitMQ consumer connection on queue: {$queue} with {$timeout}s timeout");

        // Step 1: Display the connection config
        $this->info('RabbitMQ Connection Configuration:');
        $host = config('rabbitmq-connection.host', 'not set');
        $port = config('rabbitmq-connection.port', 'not set');
        $user = config('rabbitmq-connection.user', 'not set');
        $vhost = config('rabbitmq-connection.vhost', 'not set');
        $useSSL = config('rabbitmq-connection.use_ssl', false);

        $this->table(
            ['Setting', 'Value'],
            [
                ['Host', $host],
                ['Port', $port],
                ['User', $user],
                ['VHost', $vhost],
                ['Use SSL', $useSSL ? 'Yes' : 'No'],
            ]
        );

        // Step 2: Setup message callback
        $messageCallback = function (AMQPMessage $message) {
            $this->info('✓ Received test message: '.$message->getBody());

            // Acknowledge the message
            $message->ack();

            // Display message properties
            $properties = $message->get_properties();
            $this->info('Message properties:');
            foreach ($properties as $key => $value) {
                if (!is_object($value) && !is_array($value)) {
                    $this->line("- {$key}: {$value}");
                }
            }
        };

        // Step 3: Start consuming
        $this->info('Starting to consume messages from the queue...');

        try {
            // Set consumer options
            $consumeOptions = [
                'consumer_tag' => 'test_consumer_'.time(),
                'no_local' => false,
                'no_ack' => false,
                'exclusive' => false,
                'nowait' => false,
                'prefetch_count' => 1,
            ];

            // Start consuming from the queue using the injected consumer
            $success = $consumerInstance->consume($queue, $messageCallback, $consumeOptions);

            if (!$success) {
                $this->error('❌ Failed to start consuming from RabbitMQ queue.');
                $this->error('Error: '.$consumerInstance->getLastError());

                return 1;
            }

            $this->info("✓ Successfully connected to RabbitMQ queue '{$queue}'.");
            $this->info("Waiting for messages for {$timeout} seconds...");

            // Wait for messages with timeout
            $consumerInstance->processMessages($timeout);

            $this->info('Test completed. Closing connection...');

            // Close the connection
            $closeResult = $consumerInstance->close();
            if (!$closeResult) {
                $this->warn('Warning: Could not close connection cleanly: '.$consumerInstance->getLastError());
            } else {
                $this->info('✓ Successfully closed RabbitMQ connection.');
            }

            $this->newLine();
            $this->info('Test connection command completed successfully!');

            return 0;
        } catch (Exception $e) {
            $this->error('❌ An exception occurred during the test:');
            $this->error($e->getMessage());

            Log::error('RabbitMQ consumer test connection failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
