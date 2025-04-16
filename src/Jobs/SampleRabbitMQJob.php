<?php

namespace DrBalcony\NovaCommon\Jobs;

use DrBalcony\NovaCommon\Jobs\RabbitMQConsumerJob as BaseJob;

class SampleRabbitMQJob extends BaseJob
{
    /**
     * Get the consumers mapped from their queue names to their listener classes.
     *
     * <code>
     * // Implement sth like this:
     * protected function consumers(): array
     * {
     *     return [
     *         // 'the queue name' => 'the listener class',
     *         config('rabbitmq.queues.sms_events') => SendSmsListener::class,
     *         // ...
     *     ];
     * }
     * </code>
     *
     * @return array<string, string<class-string>>
     */
    protected function mappedConsumers(): array
    {
        return []; // TODO implement
    }
}