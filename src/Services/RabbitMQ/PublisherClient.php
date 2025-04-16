<?php

namespace DrBalcony\NovaCommon\Services\RabbitMQ;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPConnectionConfig;
use PhpAmqpLib\Connection\AMQPConnectionFactory;
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
        $config->setKeepalive((bool) config('rabbitmq-connection.keepalive', false));
        $config->setHeartbeat((int) config('rabbitmq-connection.heartbeat', 0));
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
            if (! config('rabbitmq-connection.ssl_options.verify_peer', false)) {
                $config->setSslVerify(false);
                $config->setSslVerifyName(false);
            } else {
                $config->setSslVerify(config('rabbitmq-connection.ssl_options.verify_peer', false));
                $config->setSslVerifyName(config('rabbitmq-connection.ssl_options.verify_peer_name', false));
            }

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
}
