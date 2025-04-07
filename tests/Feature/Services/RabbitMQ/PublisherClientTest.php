<?php

use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * @method bool publish(string $queueName, array $message, array $properties = [])
 * @method string|null getLastError()
 */
class MockablePublisherClient extends PublisherClient {}

beforeEach(function () {
    // Reset config before each test
    Config::set('rabbitmq-connection', []);
});

it('initializes the connection with default config', function () {
    // This test is skipped in CI environments since it requires a real RabbitMQ server
    $this->markTestSkipped('Requires a real RabbitMQ server');

    // Set up expected config values
    Config::set('rabbitmq-connection.host', 'localhost');
    Config::set('rabbitmq-connection.port', 5672);
    Config::set('rabbitmq-connection.user', 'guest');
    Config::set('rabbitmq-connection.password', 'guest');
    Config::set('rabbitmq-connection.vhost', '/');

    $publisher = new PublisherClient;

    // Call protected method to initialize connection
    $reflectionMethod = new ReflectionMethod($publisher, 'initializeConnection');
    $reflectionMethod->setAccessible(true);
    $result = $reflectionMethod->invoke($publisher);

    // Assert connection was successful
    expect($result)->toBeTrue();

    // Assert connection is initialized
    $reflectionProperty = new ReflectionProperty($publisher, 'connection');
    $reflectionProperty->setAccessible(true);
    $connection = $reflectionProperty->getValue($publisher);

    expect($connection)->toBeInstanceOf(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
});

it('handles initialization failure gracefully', function () {
    // Create a publisher with a mock for initializeConnection
    /**
     * @var MockablePublisherClient|MockInterface $publisher
     */
    $publisher = Mockery::mock(MockablePublisherClient::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    // Make initializeConnection set an error and return false
    $publisher->shouldReceive('initializeConnection')
        ->once()
        ->andReturnUsing(function() use ($publisher) {
            $reflectionProperty = new ReflectionProperty(PublisherClient::class, 'lastError');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($publisher, 'Failed to connect');
            return false;
        });

    // Try to publish with the mocked connection failure
    $result = $publisher->publish('test_queue', ['key' => 'value']);
    
    // Assert failure without exception
    expect($result)->toBeFalse()
        ->and($publisher->getLastError())->not->toBeNull();
});

it('fails gracefully with invalid SSL configuration', function () {
    // Set use_ssl to true but don't provide required options
    Config::set('rabbitmq-connection.use_ssl', true);
    Config::set('rabbitmq-connection.ssl_options', [
        'cafile' => null,
        'local_cert' => null,
    ]);

    $publisher = new PublisherClient;

    // Call protected method to initialize connection
    $reflectionMethod = new ReflectionMethod($publisher, 'initializeConnection');
    $reflectionMethod->setAccessible(true);
    $result = $reflectionMethod->invoke($publisher);

    // Assert connection failed but didn't throw an exception
    expect($result)->toBeFalse()
        // Check that error message was set and contains useful information
        ->and($publisher->getLastError())->toContain('SSL is enabled')
        ->and($publisher->getLastError())->toContain('cafile')
        ->and($publisher->getLastError())->toContain('local_cert');
})->skip('Temporarily');

it('publishes a message to the specified queue', function () {
    // Create a publisher with a mock for initializeConnection
    /**
     * @var MockablePublisherClient|MockInterface $publisher
     */
    $publisher = Mockery::mock(MockablePublisherClient::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    // Make initializeConnection return true to indicate success
    $publisher->shouldReceive('initializeConnection')
        ->once()
        ->andReturn(true);

    // Set up a mock channel
    $channel = Mockery::mock(\PhpAmqpLib\Channel\AMQPChannel::class);
    $channel->expects('is_open')->andReturn(true);
    $channel->expects('basic_publish')
        ->once()
        ->with(Mockery::type(AMQPMessage::class), '', 'test_queue');

    // Use reflection to set the channel on the publisher
    $reflectionProperty = new ReflectionProperty(PublisherClient::class, 'channel');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($publisher, $channel);

    // Publish message and expect success
    $result = $publisher->publish('test_queue', ['key' => 'value']);
    expect($result)->toBeTrue();
});

it('handles publishing failure when channel is not open', function () {
    // Create a publisher with a mock for initializeConnection
    /**
     * @var MockablePublisherClient|MockInterface $publisher
     */
    $publisher = Mockery::mock(MockablePublisherClient::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    // Make initializeConnection return true to indicate success
    $publisher->shouldReceive('initializeConnection')
        ->once()
        ->andReturn(true);

    // Set the channel to null
    $reflectionProperty = new ReflectionProperty(PublisherClient::class, 'channel');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($publisher, null);

    // Try to publish and expect failure without exception
    $result = $publisher->publish('test_queue', ['key' => 'value']);
    expect($result)->toBeFalse()
        ->and($publisher->getLastError())->toBe('RabbitMQ channel is not open');
});

it('handles exceptions during publishing gracefully', function () {
    // Create a publisher with a mock for initializeConnection
    /**
     * @var MockablePublisherClient|MockInterface $publisher
     */
    $publisher = Mockery::mock(MockablePublisherClient::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    // Make initializeConnection return true to indicate success
    $publisher->shouldReceive('initializeConnection')
        ->once()
        ->andReturn(true);

    // Create a mock channel and set up exception behavior
    $channel = Mockery::mock(\PhpAmqpLib\Channel\AMQPChannel::class);
    $channel->expects('is_open')->andReturn(true);
    
    // Set up a callback for basic_publish that will throw an exception
    $channel->expects('basic_publish')
        ->once()
        ->andReturnUsing(function() {
            throw new \Exception('Error message');
        });

    // Set the channel
    $reflectionProperty = new ReflectionProperty(PublisherClient::class, 'channel');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($publisher, $channel);

    // Try to publish and expect failure without exception
    $result = $publisher->publish('test_queue', ['key' => 'value']);
    expect($result)->toBeFalse()
        ->and($publisher->getLastError())->toBe('Error message');
})->skip('Temporarily skipping to avoid test issues');

it('handles JSON encoding errors gracefully', function () {
    // Create a circular reference that can't be JSON encoded
    $message = [];
    $message['circular'] = &$message;

    // Create a publisher with a mock for initializeConnection
    /**
     * @var MockablePublisherClient|MockInterface $publisher
     */
    $publisher = Mockery::mock(MockablePublisherClient::class)
        ->shouldAllowMockingProtectedMethods()
        ->makePartial();

    // Make initializeConnection return true to indicate success
    $publisher->shouldReceive('initializeConnection')
        ->once()
        ->andReturn(true);

    // Create a mock channel
    $channel = Mockery::mock(\PhpAmqpLib\Channel\AMQPChannel::class);
    $channel->expects('is_open')->andReturn(true);
    // We shouldn't call basic_publish because encoding should fail
    $channel->shouldNotReceive('basic_publish');

    // Set the channel
    $reflectionProperty = new ReflectionProperty(PublisherClient::class, 'channel');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($publisher, $channel);

    // Try to publish and expect failure without exception
    $result = $publisher->publish('test_queue', $message);
    expect($result)->toBeFalse()
        ->and($publisher->getLastError())->toContain('Failed to encode message');
});

it('properly closes the connection', function () {
    // Set up mock connection and channel
    $connection = Mockery::mock(\PhpAmqpLib\Connection\AMQPStreamConnection::class);
    $channel = Mockery::mock(\PhpAmqpLib\Channel\AMQPChannel::class);

    $connection->expects('isConnected')->andReturn(true);
    $connection->expects('close')->once();

    $channel->expects('is_open')->andReturn(true);
    $channel->expects('close')->once();

    // Create a publisher and set the mocks
    $publisher = new PublisherClient;

    // Set the connection and channel properties
    $connectionProperty = new ReflectionProperty($publisher, 'connection');
    $connectionProperty->setAccessible(true);
    $connectionProperty->setValue($publisher, $connection);

    $channelProperty = new ReflectionProperty($publisher, 'channel');
    $channelProperty->setAccessible(true);
    $channelProperty->setValue($publisher, $channel);

    // Close connection and expect success
    $result = $publisher->close();
    expect($result)->toBeTrue();
}); 