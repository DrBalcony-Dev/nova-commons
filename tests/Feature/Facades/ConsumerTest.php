<?php

use Illuminate\Support\Facades\App;
use DrBalcony\NovaCommon\Facades\Consumer;
use DrBalcony\NovaCommon\Jobs\SampleConsumerJob;
use DrBalcony\NovaCommon\Services\RabbitMQ\ConsumerClient;
use PhpAmqpLib\Message\AMQPMessage;

it('points to the correct consumer client', function () {
    // Create a mock ConsumerClient
    $mockConsumerClient = Mockery::mock(ConsumerClient::class);
    $mockConsumerClient->expects('consume')
        ->once()
        ->with('test_queue', Mockery::type('callable'), ['priority' => 1])
        ->andReturn(true);

    // Bind the mock to the container
    App::instance('app.rabbitmq.consumer', $mockConsumerClient);

    // Create a test callback function
    $callback = function (AMQPMessage $message) {
        // Process message
    };

    // Call the facade method with a proper callback
    $result = Consumer::consume('test_queue', $callback, ['priority' => 1]);

    // Assert the facade forwarded the call to the ConsumerClient
    expect($result)->toBeTrue();
});