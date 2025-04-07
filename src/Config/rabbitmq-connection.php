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
     * Whether to use SSL/TLS for the connection.
     * If set to true, the ssl_options configuration section must be properly configured.
     * Default: false
     */
    'use_ssl' => (bool) env('RABBITMQ_USE_SSL', false),

    /*
     * The hostname or IP address of the RabbitMQ server to connect to.
     * Default: 'localhost'
     */
    'host' => env('RABBITMQ_HOST', 'localhost'),

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
     * SSL configuration options.
     * These options are used when use_ssl is set to true.
     *
     * Required SSL Parameters when use_ssl is true:
     * - Either cafile or local_cert should be set to a valid file path
     *
     * Optional SSL Parameters:
     * - local_key: Path to private key file (if not included in local_cert)
     * - verify_peer: Whether to verify the server's certificate
     * - verify_peer_name: Whether to verify the server's hostname in the certificate
     * - passphrase: Password for the private key if it's encrypted
     */
    'ssl_options' => [
        // TODO temporarily removed them to handle
        /*
         * The path to the CA certificate file.
         * Default: null
         */
//        'cafile' => env('RABBITMQ_SSL_CAFILE', null),
        
        /*
         * The path to the client certificate file.
         * Default: null
         */
//        'local_cert' => env('RABBITMQ_SSL_CERT', null),
        
        /*
         * The path to the client key file.
         * Default: null
         */
//        'local_pk' => env('RABBITMQ_SSL_KEY', null),
        
        /*
         * Whether to verify the server certificate.
         * Default: false
         */
        'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', false),
        
        /*
         * Whether to verify the server certificate name.
         * Default: false
         */
        'verify_peer_name' => env('RABBITMQ_SSL_VERIFY_PEER_NAME', false),

        /*
         * The passphrase used to encrypt the client key.
         * Default: null
         */
        'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
    ],

    /*
     * Whether to insist on connecting to a server.
     * When set to true, the connection will be allowed to connect to a different
     * server if the original server is down.
     * Default: false
     */
    'insist' => false,

    /*
     * The login method to use.
     * Valid options are 'AMQPLAIN', 'PLAIN', 'EXTERNAL', and 'GSSAPI'.
     * Default: 'AMQPLAIN'
     */
    'login_method' => 'AMQPLAIN',

    /*
     * The locale to use for the connection.
     * Default: 'en_US'
     */
    'locale' => 'en_US',

    /*
     * The number of seconds to wait while trying to connect to the server.
     * Default: 3.0
     */
    'connection_timeout' => env('RABBITMQ_CONNECTION_TIMEOUT', 3.0),

    /*
     * The number of seconds to wait for a response from the server.
     * Default: 3.0
     */
    'read_write_timeout' => env('RABBITMQ_READ_WRITE_TIMEOUT', 3.0),

    /*
     * Whether to enable keepalive.
     * If enabled, the connection will send a heartbeat to the server periodically.
     * Default: false
     */
    'keepalive' => env('RABBITMQ_KEEPALIVE', false),

    /*
     * The number of seconds between heartbeats.
     * If set to 0, heartbeats will be disabled.
     * Default: 0
     */
    'heartbeat' => env('RABBITMQ_HEARTBEAT', 0),

    /*
     * Channel RPC timeout in seconds.
     * Default: 0.0 (no timeout)
     */
    'channel_rpc_timeout' => env('RABBITMQ_CHANNEL_RPC_TIMEOUT', 0.0),
]; 