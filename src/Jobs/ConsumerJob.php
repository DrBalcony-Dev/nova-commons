<?php

namespace DrBalcony\NovaCommon\Jobs;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;
use DrBalcony\NovaCommon\Enums\Priority;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * Custom RabbitMQJob that can operate without a RabbitMQQueue instance.
 * This allows us to use it with our ConsumerClient.
 * 
 * You should extend this class and implement the `consumers` method.
 */
abstract class ConsumerJob
{
    /**
     * The container instance.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * The AMQPMessage instance.
     *
     * @var AMQPMessage
     */
    protected AMQPMessage $message;

    /**
     * The connection name.
     *
     * @var string
     */
    protected string $connectionName;

    /**
     * The queue name.
     *
     * @var string
     */
    protected string $queue;

    /**
     * List of all consumers for queues and map them to their handler class
     *
     * @var array
     */
    protected array $consumers = [];

    /**
     * Create a new job instance.
     *
     * @param Container $container
     * @param AMQPMessage $message
     * @param string $connectionName
     * @param string $queue
     */
    public function __construct(
        Container $container,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ) {
        $this->container = $container;
        $this->message = $message;
        $this->connectionName = $connectionName;
        $this->queue = $queue;

        $this->consumers = $this->consumers();
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
    abstract protected function consumers(): array;

    /**
     * Get the current queue.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Get the raw body of the message.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->message->getBody();
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
        try {
            if (!isset($this->consumers[$this->getQueue()])) {
                Log::error("No consumer found for queue: {$this->getQueue()}");
                $this->delete();

                return;
            }

            $listenerClass = $this->consumers[$this->getQueue()];
            (new $listenerClass($this->payload()))->handle();

            $this->delete();
        } catch (Exception $e) {
            $this->failed($e);
            throw $e;
        }
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->message->ack();
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

    /**
     * Get a unique UUID for the job.
     *
     * @return string
     */
    public function uuid(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Get the priority from the message.
     *
     * @return int
     */
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
    public function failed(Exception $e): void
    {
        Log::error('RabbitMQJob::Exception occurred::', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
