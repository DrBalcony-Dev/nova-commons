<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings used to connect to your RabbitMQ server.
    |
    | The host, port, username, and password options are required.
    | All other options are optional and can be left at their default values
    | unless you need to customize them for your environment.
    |
    */

    /*
     * The hostname or IP address of the RabbitMQ server to connect to.
     * Default: '127.0.0.1'
     */
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),

    /*
     * The port number to connect to on the RabbitMQ server.
     * Default: 5672
     */
    'port' => env('RABBITMQ_PORT', 5672),

    /*
     * The username to authenticate with.
     * Default: 'guest'
     */
    'user' => env('RABBITMQ_USER', 'guest'),

    /*
     * The password to authenticate with.
     * Default: 'guest'
     */
    'password' => env('RABBITMQ_PASSWORD', 'guest'),

    /*
     * The virtual host to use on the RabbitMQ server.
     * Default: '/'
     */
    'vhost' => env('RABBITMQ_VHOST', '/'),

    /*
     * The IO type to use for the connection.
     * Options: 'stream', 'socket'
     * Default: 'stream'
     */
    'io_type' => env('RABBITMQ_IO_TYPE', 'stream'),

    /*
     * Whether to use a lazy connection that only connects when needed.
     * Default: false
     */
    'lazy' => (bool) env('RABBITMQ_LAZY', false),

    /*
     * Whether to use SSL/TLS for the connection.
     * If set to true, the ssl_options configuration section must be properly configured.
     * Default: false
     */
    'use_ssl' => (bool) env('RABBITMQ_USE_SSL', false),

    /*
     * Whether to insist on connecting to a server.
     * When set to true, the connection will be allowed to connect to a different
     * server if the original server is down.
     * Default: false
     */
    'insist' => (bool) env('RABBITMQ_INSIST', false),

    /*
     * The login method to use.
     * Valid options are 'AMQPLAIN', 'PLAIN', 'EXTERNAL'.
     * Default: 'AMQPLAIN'
     */
    'login_method' => env('RABBITMQ_LOGIN_METHOD', 'AMQPLAIN'),

    /*
     * The locale to use for the connection.
     * Default: 'en_US'
     */
    'locale' => env('RABBITMQ_LOCALE', 'en_US'),

    /*
     * The number of seconds to wait while trying to connect to the server.
     * Default: 3.0
     */
    'connection_timeout' => (float) env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),

    /*
     * The number of seconds to wait for a response from the server when reading.
     * Default: 3.0
     */
    'read_timeout' => (float) env('RABBITMQ_READ_TIMEOUT', 3.0),

    /*
     * The number of seconds to wait for a response from the server when writing.
     * Default: 3.0
     */
    'write_timeout' => (float) env('RABBITMQ_WRITE_TIMEOUT', 3.0),

    /*
     * The number of seconds between heartbeats.
     * If set to 0, heartbeats will be disabled.
     * Default: 0
     */
    'heartbeat' => (int) env('RABBITMQ_HEARTBEAT', 60),

    /*
     * The maximum time to wait for an RPC response from the server.
     * Default: 0.0 (no timeout)
     */
    'channel_rpc_timeout' => (float) env('RABBITMQ_CHANNEL_RPC_TIMEOUT', 0.0),

    /*
     * An optional name to give the connection for identification purposes.
     * Default: null
     */
    'connection_name' => env('RABBITMQ_CONNECTION_NAME', null),

    /*
     * Whether to use AMQP protocol strict field types.
     * Note: RabbitMQ does not support strict field types.
     * Default: false
     */
    'protocol_strict_fields' => (bool) env('RABBITMQ_PROTOCOL_STRICT_FIELDS', false),

    /*
     * SSL configuration options.
     * These options are used when use_ssl is set to true.
     *
     * Required SSL Parameters when use_ssl is true:
     * - Either cafile or local_cert should be set to a valid file path
     */
    'ssl_options' => [
        /*
         * The path to the CA certificate file.
         * Default: null
         */
        'cafile' => env('RABBITMQ_SSL_CAFILE'),

        /*
         * The path to the CA certificates directory.
         * Default: null
         */
        'capath' => env('RABBITMQ_SSL_CAPATH'),

        /*
         * The path to the client certificate file.
         * Default: null
         */
        'local_cert' => env('RABBITMQ_SSL_LOCALCERT'),

        /*
         * The path to the client key file.
         * Default: null
         */
        'local_key' => env('RABBITMQ_SSL_LOCALKEY'),

        /*
         * Whether to verify the server certificate.
         * Default: false
         */
        'verify_peer' => (bool) env('RABBITMQ_SSL_VERIFY_PEER', false),

        /*
         * Whether to verify the server certificate name.
         * Default: false
         */
        'verify_peer_name' => (bool) env('RABBITMQ_SSL_VERIFY_PEER_NAME', false),

        /*
         * The passphrase used to encrypt the client key.
         * Default: null
         */
        'passphrase' => env('RABBITMQ_SSL_PASSPHRASE'),

        /*
         * The list of ciphers to allow for SSL connections.
         * Default: null
         */
        'ciphers' => env('RABBITMQ_SSL_CIPHERS'),

        /*
         * The security level to use for SSL connections.
         * Default: null
         */
        'security_level' => env('RABBITMQ_SSL_SECURITY_LEVEL') ? (int) env('RABBITMQ_SSL_SECURITY_LEVEL') : null,
    ],

    /*
     * The maximum size of the send buffer in bytes.
     * Default: 0 (system default)
     */
    'send_buffer_size' => (int) env('RABBITMQ_SEND_BUFFER_SIZE', 0),

    /*
     * Whether to dispatch signals for handling.
     * Default: true
     */
    'dispatch_signals' => (bool) env('RABBITMQ_DISPATCH_SIGNALS', true),

    /*
     * Whether to enable debugging of network packets.
     * Default: false
     */
    'debug_packets' => (bool) env('RABBITMQ_DEBUG_PACKETS', false),
];
