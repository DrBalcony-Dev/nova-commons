<?php

use DrBalcony\NovaCommon\Facades\Publisher;
use DrBalcony\NovaCommon\Services\RabbitMQ\PublisherClient;
use Illuminate\Support\Facades\App;

it('publishes a message using the facade', function () {
    // Create a mock PublisherClient
    $mockPublisherClient = Mockery::mock(PublisherClient::class);
    $mockPublisherClient->expects('publish')
        ->once()
        ->with('test_queue', ['message' => 'test'], ['priority' => 1])
        ->andReturn(true);

    // Bind the mock to the container
    App::instance('rabbitmq.publisher', $mockPublisherClient);

    // Call the facade method
    $result = Publisher::publish('test_queue', ['message' => 'test'], ['priority' => 1]);

    // Assert the facade forwarded the call to the PublisherClient
    expect($result)->toBeTrue();
}); 