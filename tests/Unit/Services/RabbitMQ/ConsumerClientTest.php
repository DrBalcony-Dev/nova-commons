<?php

namespace DrBalcony\NovaCommon\Tests\Unit\Services\RabbitMQ;

use DrBalcony\NovaCommon\Services\RabbitMQ\ConsumerClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use Mockery;

beforeEach(function () {
    Mockery::close();
});

test('getLastError returns null', function () {
    $consumer = new ConsumerClient;
    expect($consumer->getLastError())->toBeNull();
});

test('consume fails with invalid connection settings', function () {
    // Configure the RabbitMQ connection to use invalid settings
    Config::shouldReceive('get')
        ->with('rabbitmq-connection.host', Mockery::any())
        ->andReturn('invalid.host.that.does.not.exist');

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.port', Mockery::any())
        ->andReturn(12345); // Invalid port

    // Allow other calls to use defaults
    Config::shouldReceive('get')->andReturn('default');

    // Suppress warnings
    Log::shouldReceive('warning')->andReturn(null);
    Log::shouldReceive('error')->andReturn(null);
    Log::shouldReceive('debug')->andReturn(null);

    $consumer = new ConsumerClient;
    $callback = function (AMQPMessage $message) {};

    // Consume should fail due to invalid connection settings
    $result = $consumer->consume('test_queue', $callback);

    expect($result)->toBeFalse();
});

test('close returns true', function () {
    $consumer = new ConsumerClient;
    // Since we have no active connection, close() should return true
    expect($consumer->close())->toBeTrue();
});

test('stopConsuming returns true when no active consumer', function () {
    $consumer = new ConsumerClient;
    // Since we have no active consumer, stopConsuming() should return true
    expect($consumer->stopConsuming())->toBeTrue();
});

test('processMessages returns false without active channel', function () {
    // Mock the Log facade to avoid warning output
    Log::shouldReceive('warning')->andReturn(null);

    $consumer = new ConsumerClient;
    // Without an active channel, processMessages() should return false
    expect($consumer->processMessages())->toBeFalse();
});

test('initializeConnection reads configuration', function () {
    // Create mocks for the Config facade
    Config::shouldReceive('get')
        ->with('rabbitmq-connection.host', Mockery::any())
        ->andReturn('test.host');

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.port', Mockery::any())
        ->andReturn(5672);

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.user', Mockery::any())
        ->andReturn('guest');

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.password', Mockery::any())
        ->andReturn('guest');

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.vhost', Mockery::any())
        ->andReturn('/');

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.use_ssl', Mockery::any())
        ->andReturn(false);

    Config::shouldReceive('get')
        ->with('rabbitmq-connection.ssl_options', Mockery::any())
        ->andReturn([]);

    // Allow other configuration calls to use defaults
    Config::shouldReceive('get')->andReturn('default');

    // Suppress log warnings/errors
    Log::shouldReceive('debug')->andReturn(null);
    Log::shouldReceive('error')->andReturn(null);
    Log::shouldReceive('info')->andReturn(null);

    $consumer = new ConsumerClient;

    // Use reflection to access the protected method
    $reflection = new \ReflectionClass(ConsumerClient::class);
    $method = $reflection->getMethod('initializeConnection');
    $method->setAccessible(true);

    // Call the method - this should fail since we're not connected to a real server
    $result = $method->invoke($consumer);

    // We expect this to return false since connection to test.host will fail
    expect($result)->toBeFalse();
}); 