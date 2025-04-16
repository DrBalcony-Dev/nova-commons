<?php

namespace DrBalcony\NovaCommon\Jobs;

/**
 * Sample concrete implementation of the ConsumerJob abstract class.
 *
 * @deprecated
 */
class SampleConsumerJob extends ConsumerJob
{
    /**
     * @inheritDoc
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
     * @return array<string, string>
     */
    public function consumers(): array
    {
        return []; // TODO: Implement.
    }
}
