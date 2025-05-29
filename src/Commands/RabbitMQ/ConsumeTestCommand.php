<?php

namespace DrBalcony\NovaCommon\Commands\RabbitMQ;

use Exception;
use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use DrBalcony\NovaCommon\Facades\Consumer;

class ConsumeTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume-test
                           {--queue=test_queue : The queue to consume test messages from}
                           {--timeout=0 : Time in seconds to consume messages (0 = unlimited)}
                           {--count=0 : Maximum number of messages to consume (0 = unlimited)}
                           {--no-ack : Don\'t acknowledge messages (for testing redelivery)}
                           {--prefetch=5 : Prefetch count for the consumer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume and display test messages from a RabbitMQ queue';

    /**
     * Counter for received messages
     *
     * @var int
     */
    protected int $messageCount = 0;

    /**
     * Whether to continue consuming messages
     *
     * @var bool
     */
    protected bool $shouldContinue = true;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $queue = $this->option('queue');
        $timeout = (int) $this->option('timeout');
        $maxCount = (int) $this->option('count');
        $noAck = (bool) $this->option('no-ack');
        $prefetch = (int) $this->option('prefetch');

        // Display the test options
        $this->info('🔌 RabbitMQ Consumer Test');
        $this->info("Queue: {$queue}");
        $this->info('Timeout: '.($timeout > 0 ? "{$timeout} seconds" : 'Unlimited'));
        $this->info('Max Messages: '.($maxCount > 0 ? $maxCount : 'Unlimited'));
        $this->info('Acknowledgement: '.($noAck ? 'Disabled' : 'Enabled'));
        $this->info("Prefetch Count: {$prefetch}");

        $this->newLine();
        $this->comment('RabbitMQ Connection Parameters:');
        $this->table(
            ['Parameter', 'Value'],
            [
                ['Host', config('rabbitmq-connection.host')],
                ['Port', config('rabbitmq-connection.port')],
                ['VHost', config('rabbitmq-connection.vhost')],
                ['SSL Enabled', config('rabbitmq-connection.use_ssl') ? 'Yes' : 'No'],
            ]
        );

        $this->newLine();
        $this->info('⏳ Waiting for messages... Press Ctrl+C to exit.');
        $this->newLine();

        // Setup signal handlers for graceful shutdown if pcntl is available
        if (extension_loaded('pcntl')) {
            $this->line('Signal handling enabled (SIGINT, SIGTERM)');

            // Register signal handlers
            pcntl_signal(SIGINT, [$this, 'novaCustomHandleSignal']);
            pcntl_signal(SIGTERM, [$this, 'novaCustomHandleSignal']);
        } else {
            $this->warn('Signal handling not available (pcntl extension not loaded)');
        }

        try {
            // Define the message callback
            $messageCallback = function (AMQPMessage $message) use ($noAck, $maxCount) {
                $this->messageCount++;

                // Parse the message body
                $body = $message->getBody();
                $data = json_decode($body, true);

                // Get message properties
                $properties = $message->get_properties();

                // Format the output
                $this->info("📩 Received Message #{$this->messageCount}");
                $this->line('Timestamp: '.now()->toDateTimeString());

                if ($data) {
                    $this->table(
                        ['Field', 'Value'],
                        array_map(function ($key, $value) {
                            return [$key, is_array($value) ? json_encode($value) : $value];
                        }, array_keys($data), array_values($data))
                    );
                } else {
                    $this->line("Raw Body: {$body}");
                }

                // Display properties
                $this->table(
                    ['Property', 'Value'],
                    array_filter(array_map(function ($key, $value) {
                        if (is_scalar($value)) {
                            return [$key, $value];
                        } elseif (is_null($value)) {
                            return [$key, 'null'];
                        } elseif (is_array($value) && !empty($value)) {
                            return [$key, json_encode($value)];
                        }

                        return null;
                    }, array_keys($properties), array_values($properties)))
                );

                $this->newLine();

                // Acknowledge the message if not in no-ack mode
                if (!$noAck) {
                    $message->ack();
                    $this->line('✅ Message acknowledged');
                } else {
                    $this->line('⚠️ Message NOT acknowledged (no-ack mode)');
                }

                $this->newLine();

                // Check if we should stop consuming based on message count
                if ($maxCount > 0 && $this->messageCount >= $maxCount) {
                    $this->shouldContinue = false;
                    Consumer::stopConsuming();
                    $this->info("Reached maximum message count ({$maxCount}), stopping consumer...");
                }

                // Process PCNTL signals between messages
                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }
            };

            // Configure consumer options
            $options = [
                'consumer_tag' => 'test_consumer_'.time(),
                'no_local' => false,
                'no_ack' => $noAck,
                'exclusive' => false,
                'nowait' => false,
                'prefetch_count' => $prefetch,
            ];

            // Start consuming
            $consumeResult = Consumer::consume($queue, $messageCallback, $options);

            if (!$consumeResult) {
                $this->error('Failed to start consuming: '.Consumer::getLastError());

                return 1;
            }

            // Process messages (this will block until timeout or interrupted)
            if ($timeout > 0) {
                Consumer::processMessages($timeout);
                $this->info("Timeout of {$timeout} seconds reached, stopping consumer...");
            } else {
                // Process indefinitely (or until max count is reached)
                while ($this->shouldContinue) {
                    Consumer::processMessages(1); // Process with a 1-second timeout to allow for interruptions

                    // Process PCNTL signals
                    if (extension_loaded('pcntl')) {
                        pcntl_signal_dispatch();
                    }
                }
            }

            // Close the connection
            Consumer::close();

            $this->newLine();
            $this->info("📊 Summary: Consumed {$this->messageCount} message(s) from queue '{$queue}'");

            return 0;
        } catch (Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            // Attempt to close the connection
            try {
                Consumer::close();
            } catch (Exception $closeException) {
                $this->error('Failed to close consumer connection: '.$closeException->getMessage());
            }

            return 1;
        }
    }

    /**
     * Handle signals for graceful shutdown.
     *
     * @param int $signo The signal number
     * @return void
     */
    public function novaCustomHandleSignal(int $signo): void
    {
        $this->newLine();
        $this->info("Received signal {$signo}, shutting down gracefully...");

        $this->shouldContinue = false;

        // Attempt to stop consuming
        try {
            Consumer::stopConsuming();
        } catch (Exception $e) {
            $this->warn('Error during shutdown: '.$e->getMessage());
        }
    }
}
