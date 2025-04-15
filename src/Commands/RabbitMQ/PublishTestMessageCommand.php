<?php

namespace DrBalcony\NovaCommon\Commands\RabbitMQ;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use DrBalcony\NovaCommon\Facades\Publisher;

class PublishTestMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:publish-test
                          {--queue=test_queue : The queue to publish the test message to}
                          {--message= : Custom message text (default: auto-generated test message)}
                          {--count=1 : Number of messages to publish}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a test message to a RabbitMQ queue';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $queue = $this->option('queue');
        $message = $this->option('message');
        $count = (int) $this->option('count');

        if (!$message) {
            $message = 'Hello from Nova Team at... '.now()->toDateTimeString();
        }

        $this->info("Publishing to queue: {$queue}");
        $this->info("Message template: {$message}");
        $this->info("Number of messages: {$count}");

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

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                $messageData = [
                    'content' => $message,
                    'sequence' => $i,
                    'timestamp' => now()->timestamp,
                    'uuid' => Str::uuid()->toString(),
                ];

                $properties = [
                    'delivery_mode' => 2, // persistent
                    'content_type' => 'application/json',
                    'priority' => 1,
                    'app_id' => 'rabbitmq-test-publisher',
                ];

                $result = Publisher::publish($queue, $messageData, $properties);

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->error("\nFailed to publish message #{$i}: ".Publisher::getLastError());
                }
            } catch (Exception $e) {
                $failCount++;
                $this->error("\nException publishing message #{$i}: ".$e->getMessage());
            }

            $bar->advance();

            // Small delay between messages to make them easier to see when consumed
            if ($i < $count) {
                usleep(200000); // 0.2 seconds
            }
        }

        $bar->finish();
        $this->newLine(2);

        if ($failCount > 0) {
            $this->warn("Summary: Published {$successCount} messages, failed to publish {$failCount} messages.");

            return 1;
        } else {
            $this->info("Success! Published {$successCount} messages to '{$queue}' queue.");
            $this->info('To consume these messages, run:');
            $this->comment("php artisan rabbitmq:consume-test --queue={$queue}");

            return 0;
        }
    }
}
