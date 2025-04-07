<?php

namespace DrBalcony\NovaCommon\Services\RabbitMQ;

use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Publishes messages to a RabbitMQ queue.
 *
 * This class provides a simple interface for publishing messages to a RabbitMQ queue.
 * It handles creating and managing the connection to the RabbitMQ server.
 */
class PublisherClient
{
    /**
     * The RabbitMQ connection instance.
     *
     * @var AMQPStreamConnection|AMQPSSLConnection|null
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
     * Publishes a message to a RabbitMQ queue.
     *
     * @param string $queueName The name of the queue to publish to
     * @param array $message The message to publish
     * @param array $properties Optional message properties
     * @return bool True if the message was published successfully, false otherwise
     */
    public function publish(string $queueName, array $message, array $properties = []): bool
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
                Log::warning('RabbitMQ Publishing Error: '.$this->lastError);

                return false;
            }

            // Encode the message as JSON
            $encodedMessage = json_encode($message);
            if ($encodedMessage === false) {
                $this->lastError = 'Failed to encode message as JSON: '.json_last_error_msg();
                Log::error('RabbitMQ Publishing Error: '.$this->lastError);

                return false;
            }

            // Create a new AMQPMessage instance with the encoded message and properties
            $amqpMessage = new AMQPMessage($encodedMessage, $properties);

            // Publish the message to the specified queue
            $this->channel->basic_publish($amqpMessage, '', $queueName);

            return true;
        } catch (Exception $e) {
            // Log the exception for debugging purposes
            $this->lastError = $e->getMessage();

            Log::error('RabbitMQ Publishing Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
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
     * Initializes a new RabbitMQ connection.
     *
     * This method creates a new connection to the RabbitMQ server using the
     * configuration options specified in the `config/rabbitmq-connection.php` file.
     * It will use SSL if the use_ssl option is set to true in the configuration.
     *
     * @return bool True if connection was successful, false otherwise
     */
    protected function initializeConnection(): bool
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            return true;
        }

        try {
            $host = config('rabbitmq-connection.host');
            $port = (int) config('rabbitmq-connection.port');
            $user = config('rabbitmq-connection.user');
            $password = config('rabbitmq-connection.password');
            $vhost = config('rabbitmq-connection.vhost');

            // Determine whether to use SSL
            $useSSL = (bool) config('rabbitmq-connection.use_ssl', false);
            $sslOptions = config('rabbitmq-connection.ssl_options', []);

            // Additional optional parameters
            $options = [
                'insist' => config('rabbitmq-connection.insist', false),
                'login_method' => config('rabbitmq-connection.login_method', 'AMQPLAIN'),
                'login_response' => null, // Can be computed based on login_method
                'locale' => config('rabbitmq-connection.locale', 'en_US'),
                'connection_timeout' => config('rabbitmq-connection.connection_timeout', 3.0),
                'read_write_timeout' => config('rabbitmq-connection.read_write_timeout', 3.0),
                'context' => null, // SSL context options
                'keepalive' => config('rabbitmq-connection.keepalive', false),
                'heartbeat' => config('rabbitmq-connection.heartbeat', 0),
                'channel_rpc_timeout' => config('rabbitmq-connection.channel_rpc_timeout', 0.0),
            ];

            // Validate SSL configuration if SSL is enabled
            if ($useSSL) {
                if (empty($sslOptions['cafile']) && empty($sslOptions['local_cert'])) {
                    $this->lastError = 'SSL is enabled but neither cafile nor local_cert is set. One of these must be provided for SSL to work.';
                    Log::warning('RabbitMQ SSL Configuration Warning: '.$this->lastError);
                }

                // Ensure security options are explicitly set
                $sslOptions['verify_peer'] = $sslOptions['verify_peer'] ?? false;
                $sslOptions['verify_peer_name'] = $sslOptions['verify_peer_name'] ?? false;
            }

            // Log connection attempt
            Log::debug('Connecting to RabbitMQ '.($useSSL ? 'with SSL' : 'without SSL'), [
                'host' => $host,
                'port' => $port,
                'user' => $user,
                'vhost' => $vhost,
                'use_ssl' => $useSSL,
                'ssl_options' => $useSSL ? [
                    'verify_peer' => $sslOptions['verify_peer'],
                    'verify_peer_name' => $sslOptions['verify_peer_name'],
                ] : null,
            ]);

            // Create a new connection to RabbitMQ using key-based parameters
            if ($useSSL) {
                $this->connection = new AMQPSSLConnection(
                    $host,
                    $port,
                    $user,
                    $password,
                    $vhost,
                    $sslOptions,
                    $options
                );
                Log::info('Successfully connected to RabbitMQ with SSL');
            } else {
                $this->connection = new AMQPStreamConnection(
                    $host,
                    $port,
                    $user,
                    $password,
                    $vhost,
                    $options['insist'],
                    $options['login_method'],
                    $options['login_response'],
                    $options['locale'],
                    $options['connection_timeout'],
                    $options['read_write_timeout'],
                    null, // IO
                    $options['keepalive'],
                    $options['heartbeat'],
                    $options['channel_rpc_timeout']
                );
                Log::info('Successfully connected to RabbitMQ without SSL');
            }

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
}