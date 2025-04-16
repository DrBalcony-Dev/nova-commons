<?php

namespace DrBalcony\NovaCommon\Services\RabbitMQ;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Consumes messages from a RabbitMQ queue.
 *
 * This class provides a simple interface for consuming messages from a RabbitMQ queue.
 * It handles creating and managing the connection to the RabbitMQ server.
 *
 * @deprecated if you want to consume anything you should use queue
 */
class ConsumerClient
{
    /**
     * The RabbitMQ connection instance.
     *
     * @var AbstractConnection|null
     */
    private $connection = null;

    /**
     * The RabbitMQ channel instance.
     *
     * @var \PhpAmqpLib\Channel\AMQPChannel|null
     */
    private $channel = null;

    /**
     * Last error message encountered.
     *
     * @var string|null
     */
    private $lastError = null;

    /**
     * The consumer tag.
     *
     * @var string|null
     */
    private $consumerTag = null;

    /**
     * Flag to indicate if consuming should continue.
     *
     * @var bool
     */
    private bool $shouldContinueConsuming = true;

    /**
     * Consumes messages from a RabbitMQ queue.
     *
     * @param string $queueName The name of the queue to consume from
     * @param callable $callback The callback to execute when a message is received
     * @param array $options Optional consume options
     * @return bool True if consumption started successfully, false otherwise
     */
    public function consume(string $queueName, callable $callback, array $options = []): bool
    {
        try {
            // Reset last error
            $this->lastError = null;

            // Ensure we have an active connection
            if (!$this->initializeConnection()) {
                return false;
            }

            // Check if channel is open
            if ($this->channel === null || !$this->channel->is_open()) {
                $this->lastError = 'RabbitMQ channel is not open';
                Log::warning('RabbitMQ Consuming Error: '.$this->lastError);

                return false;
            }

            // Set default options
            $options = array_merge([
                'consumer_tag' => '',
                'no_local' => false,
                'no_ack' => false,
                'exclusive' => false,
                'nowait' => false,
                'prefetch_count' => 1,
                'prefetch_size' => 0,
                'global' => false,
            ], $options);

            // Set quality of service (prefetch)
            $this->channel->basic_qos(
                $options['prefetch_size'],
                $options['prefetch_count'],
                $options['global']
            );

            // Declare the queue if not already declared
            $this->channel->queue_declare(
                $queueName,
                true,  // passive - only check if queue exists, don't try to create
                true,  // durable
                false, // exclusive
                false, // auto delete
                false // nowait
            );

            // Wrap the callback to include logging
            $wrappedCallback = function (AMQPMessage $message) use ($callback, $queueName, $options) {
                Log::debug('RabbitMQ message received from queue: '.$queueName, [
                    'queue' => $queueName,
                    'message_size' => strlen($message->getBody()),
                ]);

                try {
                    // Execute the user callback
                    call_user_func($callback, $message, $this);
                } catch (Exception $e) {
                    Log::error('RabbitMQ Consumer Callback Error', [
                        'queue' => $queueName,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Don't re-throw, just handle the exception and proceed
                    if (!$options['no_ack']) {
                        // Negative acknowledgment - reject the message and requeue it
                        $message->nack(false, true);
                    }
                }
            };

            // Start consuming
            $this->consumerTag = $this->channel->basic_consume(
                $queueName,
                $options['consumer_tag'],
                $options['no_local'],
                $options['no_ack'],
                $options['exclusive'],
                $options['nowait'],
                $wrappedCallback
            );

            Log::info('Started consuming from RabbitMQ queue', [
                'queue' => $queueName,
                'consumer_tag' => $this->consumerTag,
                'prefetch_count' => $options['prefetch_count'],
            ]);

            return true;
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            $this->lastError = $e->getMessage();

            Log::error('RabbitMQ Consuming Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Process messages until the consumer is stopped or an error occurs.
     *
     * @param int $timeout Timeout in seconds, 0 for no timeout
     * @return bool True if processing completed without errors, false otherwise
     */
    public function processMessages(int $timeout = 0): bool
    {
        if ($this->channel === null || !$this->channel->is_open()) {
            $this->lastError = 'RabbitMQ channel is not open';
            Log::warning('RabbitMQ Processing Error: '.$this->lastError);
            return false;
        }

        try {
            Log::debug('Started processing RabbitMQ messages');

            // Reset the flag to continue consuming
            $this->shouldContinueConsuming = true;

            // Calculate end time if timeout is provided
            $endTime = $timeout > 0 ? time() + $timeout : 0;

            // Get heartbeat value - use a shorter wait interval based on heartbeat
            $heartbeat = (int) config('rabbitmq-connection.heartbeat', 60);
            // Wait for less time than the heartbeat interval requires
            $waitInterval = $heartbeat > 0 ? min(1, $heartbeat / 4) : 1;
            $lastHeartbeatTime = time();

            // Process messages until we're told to stop or timeout occurs
            while ($this->channel->is_consuming()) {
                // Check if timeout has occurred
                if ($timeout > 0 && time() >= $endTime) {
                    break;
                }

                // Check if we should continue consuming
                if (!$this->shouldContinue()) {
                    break;
                }

                // Always check and send heartbeat before processing messages
                $currentTime = time();

                // Check connection state periodically (e.g., every 15 seconds)
                if ($currentTime % 15 == 0) {
                    if (!$this->attemptRecovery()) {
                        break; // Exit the loop if recovery failed
                    }
                }

                if (
                    $heartbeat > 0 &&
                    ($currentTime - $lastHeartbeatTime) >= ($heartbeat / 4)
                ) {
                    if ($this->isHealthy()) {
                        try {
                            // Send heartbeat
                            if ($this->sendHeartbeat()) {
                                $lastHeartbeatTime = $currentTime;
                            }
                        } catch (Exception $hbException) {
                            Log::warning('RabbitMQ heartbeat error: '.$hbException->getMessage());
                        }
                    }
                }

                // Process messages in short bursts
                try {
                    $this->channel->wait(null, true, $waitInterval);
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    // This is normal - just means no messages arrived during wait interval
                    // We don't need to do anything special here - heartbeat is already checked above
                }
            }

            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            Log::error('RabbitMQ Processing Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Stops the consumer.
     *
     * @return bool True if stopped successfully, false otherwise
     */
    public function stopConsuming(): bool
    {
        $this->shouldContinueConsuming = false;

        if ($this->channel === null || !$this->channel->is_open() || empty($this->consumerTag)) {
            // Already stopped or never started
            return true;
        }

        try {
            $this->channel->basic_cancel($this->consumerTag);
            Log::info('Stopped consuming from RabbitMQ queue', [
                'consumer_tag' => $this->consumerTag,
            ]);

            $this->consumerTag = null;

            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            Log::warning('Error stopping RabbitMQ consumer', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Closes the RabbitMQ connection if it's open.
     *
     * @return bool True if connection was closed successfully or was already closed, false otherwise
     */
    public function close(): bool
    {
        try {
            // Stop consuming if needed
            if ($this->consumerTag !== null) {
                $this->stopConsuming();
            }

            if ($this->channel !== null && $this->channel->is_open()) {
                $this->channel->close();
            }

            if ($this->connection !== null && $this->connection->isConnected()) {
                $this->connection->close();
                Log::info('RabbitMQ connection closed');
            }

            $this->channel = null;
            $this->connection = null;

            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            Log::warning('Error closing RabbitMQ connection', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Gets the last error message that occurred.
     *
     * @return string|null The last error message or null if no error occurred
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Creates and returns a configured AMQPConnectionConfig object based on application config.
     *
     * @return AMQPConnectionConfig
     */
    protected function createConnectionConfig(): AMQPConnectionConfig
    {
        $config = new AMQPConnectionConfig;

        // Set connection details
        $config->setHost(config('rabbitmq-connection.host', '127.0.0.1'));
        $config->setPort((int) config('rabbitmq-connection.port', 5672));
        $config->setUser(config('rabbitmq-connection.user', 'guest'));
        $config->setPassword(config('rabbitmq-connection.password', 'guest'));
        $config->setVhost(config('rabbitmq-connection.vhost', '/'));

        // Authentication settings
        $config->setLoginMethod(config('rabbitmq-connection.login_method', 'AMQPLAIN'));
        $config->setLocale(config('rabbitmq-connection.locale', 'en_US'));

        // Connection timeouts and keep-alive
        $config->setConnectionTimeout((float) config('rabbitmq-connection.connection_timeout', 3.0));
        $config->setReadTimeout((float) config('rabbitmq-connection.read_timeout', 3.0));
        $config->setWriteTimeout((float) config('rabbitmq-connection.write_timeout', 3.0));

        // Explicitly set to keep alive (because using heartbeats)
        $config->setKeepalive(true);

        // Set heartbeat
        $heartbeat = (int) config('rabbitmq-connection.heartbeat');
        $config->setHeartbeat($heartbeat);
        $config->setChannelRPCTimeout((float) config('rabbitmq-connection.channel_rpc_timeout', 0.0));

        // SSL configuration
        if ((bool) config('rabbitmq-connection.use_ssl', false)) {
            $config->setIsSecure(true);

            // SSL CA Certificate
            if ($caCert = config('rabbitmq-connection.ssl_options.cafile')) {
                $config->setSslCaCert($caCert);
            }

            // SSL CA Path
            if ($caPath = config('rabbitmq-connection.ssl_options.capath')) {
                $config->setSslCaPath($caPath);
            }

            // SSL Client Certificate
            if ($cert = config('rabbitmq-connection.ssl_options.local_cert')) {
                $config->setSslCert($cert);
            }

            // SSL Client Key
            if ($key = config('rabbitmq-connection.ssl_options.local_key')) {
                $config->setSslKey($key);
            }

            // SSL Verify Options
            $config->setSslVerify(config('rabbitmq-connection.ssl_options.verify_peer', false));
            $config->setSslVerifyName(config('rabbitmq-connection.ssl_options.verify_peer_name', false));

            // SSL Passphrase
            if ($passphrase = config('rabbitmq-connection.ssl_options.passphrase')) {
                $config->setSslPassPhrase($passphrase);
            }
        }

        // Connection Name
        if ($connectionName = config('rabbitmq-connection.connection_name')) {
            $config->setConnectionName($connectionName);
        }

        // IO Type - default to stream
        $ioType = config('rabbitmq-connection.io_type', AMQPConnectionConfig::IO_TYPE_STREAM);
        $config->setIoType($ioType);

        // Other settings
        $config->setIsLazy(config('rabbitmq-connection.lazy', false));

        return $config;
    }

    /**
     * Initializes a new RabbitMQ connection using AMQPConnectionFactory and AMQPConnectionConfig.
     *
     * @return bool True if connection was successful, false otherwise
     */
    protected function initializeConnection(): bool
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return true;
        }

        try {
            // Create connection config
            $config = $this->createConnectionConfig();

            // Log connection attempt
            Log::debug('Connecting to RabbitMQ '.($config->isSecure() ? 'with SSL' : 'without SSL'), [
                'host' => $config->getHost(),
                'port' => $config->getPort(),
                'user' => $config->getUser(),
                'vhost' => $config->getVhost(),
                'use_ssl' => $config->isSecure(),
                'io_type' => $config->getIoType(),
                'heartbeat' => $config->getHeartbeat(),
            ]);

            // Create connection using factory
            $this->connection = AMQPConnectionFactory::create($config);

            // Log success
            Log::info('Successfully connected to RabbitMQ', [
                'secure' => $config->isSecure() ? 'Yes' : 'No',
                'io_type' => $config->getIoType(),
            ]);

            // Create a new channel on the connection
            $this->channel = $this->connection->channel();

            return true;
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            $this->lastError = $e->getMessage();

            Log::error('RabbitMQ Connection Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Reset connection and channel objects
            $this->connection = null;
            $this->channel = null;

            return false;
        }
    }

    /**
     * Check if we should continue consuming.
     *
     * @return bool True if we should continue, false otherwise
     */
    private function shouldContinue(): bool
    {
        return $this->shouldContinueConsuming;
    }

    /**
     * Attempt to recover the connection if it's dropped.
     *
     * @return bool True if recovery was successful or not needed, false otherwise
     */
    protected function attemptRecovery(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            Log::warning('RabbitMQ connection lost, attempting recovery...');

            // Close any existing connections to clean up resources
            $this->close();

            // Try to reconnect
            if (!$this->initializeConnection()) {
                Log::error('Failed to recover RabbitMQ connection');
                return false;
            }

            Log::info('RabbitMQ connection recovered successfully');
            return true;
        }

        return true; // Connection is already active
    }

    /**
     * Sends a heartbeat to the RabbitMQ server.
     *
     * @return bool True if heartbeat was sent successfully, false otherwise
     */
    public function sendHeartbeat(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        try {
            $this->connection->checkHeartBeat();

            return true;
        } catch (Exception $e) {
            Log::warning('Failed to send heartbeat: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Checks if the connection is healthy.
     *
     * @return bool True if the connection is healthy, false otherwise
     */
    public function isHealthy(): bool
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            return false;
        }

        if ($this->channel === null || !$this->channel->is_open()) {
            return false;
        }

        return true;
    }
}
