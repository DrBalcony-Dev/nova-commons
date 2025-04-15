<?php

namespace DrBalcony\NovaCommon\Commands\RabbitMQ;

use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestRabbitMQConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:test-connection
                            {--queue=test_queue : The queue to publish test messages to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the RabbitMQ connection and publish a test message';

    /**
     * Execute the console command.
     *
     * @param PublisherClient $publisher
     * @return int
     */
    public function handle(PublisherClient $publisher)
    {
        $queue = $this->option('queue');
        $this->info("Testing RabbitMQ connection and publishing to queue: {$queue}");

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

        // Step 2: Test PublisherClient
        $this->info('Testing PublisherClient:');
        $testMessage = ['message' => 'Test message from client', 'timestamp' => now()->toIso8601String()];

        $success = $publisher->publish($queue, $testMessage);

        if ($success) {
            $this->info('✓ Successfully published test message to RabbitMQ.');

            // Close the connection
            $closeResult = $publisher->close();
            if (!$closeResult) {
                $this->warn('Warning: Could not close connection cleanly: '.$publisher->getLastError());
            } else {
                $this->info('✓ Successfully closed RabbitMQ connection.');
            }

            $this->newLine();
            $this->info('All tests completed successfully! RabbitMQ connection is working.');

            return 0;
        } else {
            $this->error('❌ Failed to publish message to RabbitMQ.');
            $this->error('Error: '.$publisher->getLastError());

            $this->newLine();
            $this->info('Debug information:');
            $this->info('Host: '.$host);
            $this->info('Port: '.$port);
            $this->info('User: '.$user);
            $this->info('VHost: '.$vhost);
            $this->info('Use SSL: '.($useSSL ? 'Yes' : 'No'));

            if ($useSSL) {
                $sslOptions = config('rabbitmq-connection.ssl_options', []);
                $this->info('SSL Options:');
                $this->info('- cafile: '.($sslOptions['cafile'] ?? 'not set'));
                $this->info('- local_cert: '.($sslOptions['local_cert'] ?? 'not set'));
                $this->info('- local_key: '.($sslOptions['local_key'] ?? 'not set'));
                $this->info('- verify_peer: '.($sslOptions['verify_peer'] ? 'true' : 'false'));
                $this->info('- verify_peer_name: '.($sslOptions['verify_peer_name'] ? 'true' : 'false'));
            }

            Log::error('RabbitMQ test connection failed', [
                'error' => $publisher->getLastError(),
                'host' => $host,
                'port' => $port,
                'use_ssl' => $useSSL,
            ]);

            return 1;
        }
    }
} 