<?php

namespace DrBalcony\NovaCommon\Commands\RabbitMQ;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use DrBalcony\NovaCommon\Jobs\ConsumerJob;
use DrBalcony\NovaCommon\Services\RabbitMQ\ConsumerClient;

class ConsumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consume
                            {--queue= : The queue to consume messages from}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--tries=3 : Number of times to attempt a job before logging it failed}
                            {--backoff=0 : Number of seconds to wait before retrying a job that has failed}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--prefetch-count=1 : The prefetch count for the consumer}
                            {--max-jobs=0 : The number of jobs to process before stopping (0 for unlimited)}
                            {--max-time=0 : The maximum number of seconds the worker should run (0 for unlimited)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consume messages from a RabbitMQ queue';

    /**
     * The ConsumerClient instance.
     *
     * @var ConsumerClient
     */
    protected ConsumerClient $consumer;

    /**
     * The Container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Count of jobs that have been processed.
     *
     * @var int
     */
    protected int $jobCount = 0;

    /**
     * Start time of the worker.
     *
     * @var int
     */
    protected int $startTime;

    /**
     * Whether the worker should continue processing.
     *
     * @var bool
     */
    protected bool $shouldContinue = true;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->container = Container::getInstance();
    }

    /**
     * Execute the console command.
     *
     * @param ConsumerClient $consumer
     * @return int
     */
    public function handle(ConsumerClient $consumer): int
    {
        $this->consumer = $consumer;
        $this->startTime = time();

        // Setup signal handlers for graceful shutdown
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
            pcntl_signal(SIGINT, [$this, 'shutdown']);
        }

        // Get options
        $queue = $this->option('queue');
        $sleep = (int) $this->option('sleep');
        $tries = (int) $this->option('tries');
        $backoff = (int) $this->option('backoff');
        $timeout = (int) $this->option('timeout');
        $prefetchCount = (int) $this->option('prefetch-count');
        $maxJobs = (int) $this->option('max-jobs');
        $maxTime = (int) $this->option('max-time');
        $verbose = (bool) $this->option('verbose');

        // Validate queue name
        if (empty($queue)) {
            $this->error('The queue name must be provided.');

            return 1;
        }

        // Log startup information
        if ($verbose) {
            $this->info("Starting RabbitMQ consumer for queue: {$queue}");
            $this->info("Configuration: tries={$tries}, backoff={$backoff}, timeout={$timeout}, prefetch-count={$prefetchCount}");
        }

        // Set up the message callback
        $messageCallback = function (AMQPMessage $message) use ($queue, $tries, $backoff, $verbose, $maxJobs, $maxTime) {
            if ($verbose) {
                $messageSize = strlen($message->getBody());
                $this->line("Processing message from queue: {$queue} [{$messageSize} bytes]");
            }

            // Process the message using the inherited consumer job class. (see config/nova-common.php)
            try {
                $concreteJobClass = $this->getConcreteConsumerJobClass();

                // Create a concrete consumer job instance
                $job = new $concreteJobClass(
                    $this->container,
                    $message,
                    'direct', // Connection name
                    $queue
                );

                // Fire the job
                $job->fire();

                // Count the processed job
                $this->jobCount++;

                if ($verbose) {
                    $this->info("Job processed successfully. Total jobs processed: {$this->jobCount}");
                }

                // Check if we should stop processing
                $this->checkShouldStop($maxJobs, $maxTime);

                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }
            } catch (Exception $e) {
                $this->handleJobException($e, $message, $tries, $backoff);
            }
        };

        // Configure consumer options
        $consumeOptions = [
            'consumer_tag' => 'consumer_'.Str::random(10),
            'no_local' => false,
            'no_ack' => false,
            'exclusive' => false,
            'nowait' => false,
            'prefetch_count' => $prefetchCount,
        ];

        // Start consuming
        try {
            $success = $this->consumer->consume($queue, $messageCallback, $consumeOptions);

            if (!$success) {
                $this->error('Failed to start consuming from RabbitMQ queue.');
                $this->error('Error: '.$this->consumer->getLastError());

                return 1;
            }

            if ($verbose) {
                $this->info("Successfully connected to RabbitMQ queue '{$queue}'.");
                $this->info('Waiting for messages...');
            }

            // Process messages until shouldContinue is false
            while ($this->shouldContinue) {
                $this->consumer->processMessages($sleep);

                // Check if we should stop
                $this->checkShouldStop($maxJobs, $maxTime);

                if (extension_loaded('pcntl')) {
                    pcntl_signal_dispatch();
                }
            }

            // Close the connection
            $this->consumer->close();

            if ($verbose) {
                $this->info('Consumer stopped gracefully.');
            }

            return 0;
        } catch (Exception $e) {
            $this->error('An exception occurred during consumption:');
            $this->error($e->getMessage());

            Log::error('RabbitMQ consumption error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to close the connection
            $this->consumer->close();

            return 1;
        }
    }

    /**
     * Handle shutdown signals.
     *
     * @param int $signal
     * @return void
     */
    public function shutdown(int $signal): void
    {
        $this->info("Received signal {$signal}. Shutting down gracefully...");
        $this->shouldContinue = false;
        $this->consumer->stopConsuming();
    }

    /**
     * Handle job exception.
     *
     * @param Exception $e
     * @param AMQPMessage $message
     * @param int $tries
     * @param int $backoff
     * @return void
     */
    protected function handleJobException(Exception $e, AMQPMessage $message, int $tries, int $backoff): void
    {
        $this->error('Error processing job: '.$e->getMessage());

        // Get message properties to check retry count
        $properties = $message->get_properties();
        $headers = $properties['application_headers'] ?? [];
        $retryCount = $headers['x-retry-count'] ?? 0;

        if ($retryCount < $tries) {
            // Increment retry count
            $retryCount++;

            $this->warn("Retrying job (attempt {$retryCount} of {$tries})...");

            // Add retry count to headers
            $headers['x-retry-count'] = $retryCount;
            $properties['application_headers'] = $headers;

            // Reject the message and requeue it
            $message->nack(false, true);

            // Sleep before next attempt if backoff is specified
            if ($backoff > 0) {
                $this->info("Backing off for {$backoff} seconds before retry...");
                sleep($backoff);
            }
        } else {
            $this->error("Job failed after {$tries} attempts. Marking as failed.");

            // Log the failed job
            Log::error('RabbitMQ job failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'queue' => $message->getRoutingKey(),
                'body' => $message->getBody(),
            ]);

            // Reject the message without requeuing
            $message->nack(false, false);
        }
    }

    /**
     * Check if the worker should stop processing.
     *
     * @param int $maxJobs
     * @param int $maxTime
     * @return void
     */
    protected function checkShouldStop(int $maxJobs, int $maxTime): void
    {
        // Check if max jobs limit is reached
        if ($maxJobs > 0 && $this->jobCount >= $maxJobs) {
            $this->shouldContinue = false;
            $this->info("Maximum of {$maxJobs} jobs processed. Stopping worker.");

            return;
        }

        // Check if max time limit is reached
        if ($maxTime > 0 && (time() - $this->startTime) >= $maxTime) {
            $this->shouldContinue = false;
            $this->info("Maximum runtime of {$maxTime} seconds reached. Stopping worker.");

            return;
        }
    }

    protected function getConcreteConsumerJobClass(): string
    {
        $jobClass = config('rabbitmq.consume.job');

        if (empty($jobClass) || !is_subclass_of($jobClass, ConsumerJob::class)) {
            throw new Exception('The cosumer job class is not set or does not extend the base ConsumerJob class.');
        }

        return $jobClass;
    }
}
