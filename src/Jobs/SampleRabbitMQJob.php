<?php

namespace DrBalcony\NovaCommon\Jobs;

use DrBalcony\NovaCommon\Jobs\RabbitMQConsumerJob as BaseJob;

class SampleRabbitMQJob extends BaseJob
{
    /**
     * @inheritDoc
     *
     * @return array<string, string>
     */
    protected function mappedConsumers(): array
    {
        return []; // TODO implement
    }
}