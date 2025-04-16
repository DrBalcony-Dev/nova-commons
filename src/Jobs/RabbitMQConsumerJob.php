<?php

namespace DrBalcony\NovaCommon\Jobs;

use DrBalcony\NovaCommon\Enums\Priority;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob as baseJob;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

abstract class RabbitMQConsumerJob extends baseJob
{
    /**
     * List of all consumers for queues and map theme to their handler class
     *
     * @var array
     */
    protected array $consumers = [];

    public function __construct(
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ) {
        $this->consumers = $this->mappedConsumers();

        parent::__construct($container, $rabbitmq, $message, $connectionName, $queue);
    }

    /**
     * Fire the job.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function fire(): void
    {
        (new ($this->consumers[$this->getQueue()])($this->payload()))->handle();

        $this->delete();
    }

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array
    {
        return [
            'job' => $this->getQueue(),
            'data' => json_decode($this->getRawBody(), true),
            'uuid' => $this->uuid(),
            'priority' => $this->getPriority(),
        ];
    }

    public function uuid(): ?string
    {
        return Str::uuid()->toString();
    }

    public function getPriority(): int
    {
        $properties = $this->message->get_properties();

        return $properties['priority'] ?? Priority::Low->value;
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param Exception $e
     * @return void
     */
    public function failed($e): void
    {
        Log::error('RabbitMQJob::Exception occurred::', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

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
    abstract protected function mappedConsumers(): array;
}
