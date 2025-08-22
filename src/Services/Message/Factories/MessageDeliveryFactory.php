<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Factories;

use DrBalcony\NovaCommon\Enums\MessageChannelEnum;
use DrBalcony\NovaCommon\Exceptions\UnsupportedMessageMethodException;
use DrBalcony\NovaCommon\Services\Message\Contracts\MessageDeliveryStrategyInterface;
use DrBalcony\NovaCommon\Services\Message\Strategies\CallMessageStrategy;
use DrBalcony\NovaCommon\Services\Message\Strategies\EmailMessageStrategy;
use DrBalcony\NovaCommon\Services\Message\Strategies\SmsMessageStrategy;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating message delivery strategy instances
 *
 * This factory manages the registration and creation of message delivery strategies,
 * providing a centralized way to handle different communication channels.
 */
final class MessageDeliveryFactory
{
    /**
     * @var array<string, class-string<MessageDeliveryStrategyInterface>>
     */
    private array $strategies = [];

    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->registerDefaultStrategies();
    }

    /**
     * Register a new message strategy
     *
     * @param string $method The message method identifier
     * @param class-string<MessageDeliveryStrategyInterface> $strategyClass The strategy class
     * @return self For method chaining
     *
     * @throws InvalidArgumentException If the strategy class is invalid
     */
    public function registerStrategy(string $method, string $strategyClass): self
    {
        if (!class_exists($strategyClass)) {
            throw new InvalidArgumentException("Strategy class '{$strategyClass}' does not exist");
        }

        if (!is_subclass_of($strategyClass, MessageDeliveryStrategyInterface::class)) {
            throw new InvalidArgumentException(
                "Strategy class '{$strategyClass}' must implement MessageDeliveryStrategyInterface"
            );
        }

        $this->strategies[$method] = $strategyClass;

        $this->logger->debug('Message strategy registered', [
            'method' => $method,
            'strategy_class' => $strategyClass,
        ]);

        return $this;
    }

    /**
     * Create a message delivery strategy instance
     *
     * @param string $method The message method
     * @return MessageDeliveryStrategyInterface The strategy instance
     *
     * @throws UnsupportedMessageMethodException If the method is not supported
     */
    public function create(string $method): MessageDeliveryStrategyInterface
    {
        if (!isset($this->strategies[$method])) {
            $this->logger->error('Unsupported notification method requested', [
                'method' => $method,
                'available_methods' => $this->getAvailableMethods(),
            ]);

            throw new UnsupportedMessageMethodException($method, $this->getAvailableMethods());
        }

        $strategyClass = $this->strategies[$method];

        $this->logger->debug('Creating notification strategy', [
            'method' => $method,
            'strategy_class' => $strategyClass,
        ]);

        // Create instance with logger if the constructor accepts it
        $reflection = new \ReflectionClass($strategyClass);
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            return new $strategyClass($this->logger);
        }

        return new $strategyClass();
    }

    /**
     * Get all registered notification methods
     *
     * @return array<string> List of available methods
     */
    public function getAvailableMethods(): array
    {
        return array_keys($this->strategies);
    }

    /**
     * Check if a notification method is supported
     *
     * @param string $method The method to check
     * @return bool True if the method is supported
     */
    public function isMethodSupported(string $method): bool
    {
        return isset($this->strategies[$method]);
    }

    /**
     * Get the strategy class for a given method
     *
     * @param string $method The notification method
     * @return class-string<MessageDeliveryStrategyInterface>|null The strategy class or null if not found
     */
    public function getStrategyClass(string $method): ?string
    {
        return $this->strategies[$method] ?? null;
    }

    /**
     * Unregister a notification strategy
     *
     * @param string $method The method to unregister
     * @return self For method chaining
     */
    public function unregisterStrategy(string $method): self
    {
        if (isset($this->strategies[$method])) {
            unset($this->strategies[$method]);

            $this->logger->debug('Notification strategy unregistered', [
                'method' => $method,
            ]);
        }

        return $this;
    }

    /**
     * Register the default notification strategies
     *
     * @return void
     */
    private function registerDefaultStrategies(): void
    {
        $defaultStrategies = [
            MessageChannelEnum::EMAIL->value => EmailMessageStrategy::class,
            MessageChannelEnum::SMS->value => SmsMessageStrategy::class,
            MessageChannelEnum::CALL->value => CallMessageStrategy::class,
        ];

        foreach ($defaultStrategies as $method => $strategyClass) {
            $this->registerStrategy($method, $strategyClass);
        }

        $this->logger->info('Default notification strategies registered', [
            'strategies' => array_keys($defaultStrategies),
        ]);
    }
}