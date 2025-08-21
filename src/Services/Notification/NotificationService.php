<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification;

use DrBalcony\NovaCommon\DTO\NotificationMetadataDTO;
use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;
use DrBalcony\NovaCommon\Enums\NotificationChannelEnum;
use DrBalcony\NovaCommon\Exceptions\InvalidNotificationIdentifierException;
use DrBalcony\NovaCommon\Exceptions\UnsupportedNotificationMethodException;
use DrBalcony\NovaCommon\Services\Notification\Factories\NotificationDeliveryFactory;
use DrBalcony\NovaCommon\Services\Notification\Generators\NotificationPayloadGenerator;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Main notification service orchestrator
 *
 * This service provides a high-level interface for sending notifications
 * across different channels (email, SMS, call) using the strategy pattern.
 */
final class NotificationService
{
    private LoggerInterface $logger;
    private NotificationDeliveryFactory $deliveryFactory;

    public function __construct(
        ?NotificationDeliveryFactory $deliveryFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->deliveryFactory = $deliveryFactory ?? new NotificationDeliveryFactory($this->logger);
    }

    /**
     * Send a notification via the specified channel
     *
     * @param NotificationChannelEnum $channel The notification channel
     * @param NotificationRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     *
     * @throws UnsupportedNotificationMethodException If the channel is not supported
     * @throws Exception If the delivery fails
     */
    public function send(NotificationChannelEnum $channel, NotificationRequestDTO $requestDTO): bool
    {
        $this->logger->info('Processing notification request', [
            'channel' => $channel->value,
            'recipient' => $requestDTO->recipient,
            'account_uuid' => $requestDTO->getAccountUuid(),
            'is_template_based' => $requestDTO->isTemplateBased(),
            'template_slug' => $requestDTO->templateSlug,
        ]);

        try {
            $strategy = $this->deliveryFactory->create($channel->value);
            $result = $strategy->send($requestDTO);

            $this->logger->info('Notification sent successfully', [
                'channel' => $channel->value,
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'success' => $result,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('Failed to send notification', [
                'channel' => $channel->value,
                'recipient' => $requestDTO->recipient,
                'account_uuid' => $requestDTO->getAccountUuid(),
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Send an email notification
     *
     * @param NotificationRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendEmail(NotificationRequestDTO $requestDTO): bool
    {
        return $this->send(NotificationChannelEnum::EMAIL, $requestDTO);
    }

    /**
     * Send an SMS notification
     *
     * @param NotificationRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendSms(NotificationRequestDTO $requestDTO): bool
    {
        return $this->send(NotificationChannelEnum::SMS, $requestDTO);
    }

    /**
     * Send a call notification
     *
     * @param NotificationRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendCall(NotificationRequestDTO $requestDTO): bool
    {
        return $this->send(NotificationChannelEnum::CALL, $requestDTO);
    }

    /**
     * Send notification using legacy method signature for backward compatibility
     *
     * @param string $channel The notification channel
     * @param string $recipient Recipient identifier (email/phone)
     * @param string|null $accountUuid Account UUID (if null, uses config default)
     * @param string $content Content for notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return bool Success status of the delivery attempt
     *
     * @throws UnsupportedNotificationMethodException If the channel is not supported
     */
    public function sendLegacy(
        string $channel,
        string $recipient,
        ?string $accountUuid = null,
        string $content = '',
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): bool {
        // Validate channel
        if (!NotificationChannelEnum::isSupported($channel)) {
            throw new UnsupportedNotificationMethodException(
                $channel,
                NotificationChannelEnum::getAvailableChannels()
            );
        }

        // Create request DTO from legacy parameters
        $requestDTO = NotificationPayloadGenerator::createRequest(
            $recipient,
            $accountUuid,
            $content,
            $templateSlug,
            $placeholders,
            $metadata
        );

        // Send using the modern method
        return $this->send(NotificationChannelEnum::from($channel), $requestDTO);
    }

    /**
     * Send notification using legacy identifier format for backward compatibility
     *
     * @param string $channel The notification channel
     * @param string $identifier Legacy identifier in format: recipient_accountUuid
     * @param string $content Content for notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return bool Success status of the delivery attempt
     *
     * @throws UnsupportedNotificationMethodException If the channel is not supported
     * @throws InvalidNotificationIdentifierException If identifier format is invalid
     */
    public function sendLegacyWithIdentifier(
        string $channel,
        string $identifier,
        string $content,
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): bool {
        // Validate channel
        if (!NotificationChannelEnum::isSupported($channel)) {
            throw new UnsupportedNotificationMethodException(
                $channel,
                NotificationChannelEnum::getAvailableChannels()
            );
        }

        // Create request DTO from legacy identifier
        $requestDTO = NotificationPayloadGenerator::createFromLegacyIdentifier(
            $identifier,
            $content,
            $templateSlug,
            $placeholders,
            $metadata
        );

        // Send using the modern method
        return $this->send(NotificationChannelEnum::from($channel), $requestDTO);
    }

    /**
     * Get all available notification channels
     *
     * @return array<string> List of available channels
     */
    public function getAvailableChannels(): array
    {
        return $this->deliveryFactory->getAvailableMethods();
    }

    /**
     * Check if a notification channel is supported
     *
     * @param string $channel The channel to check
     * @return bool True if the channel is supported
     */
    public function isChannelSupported(string $channel): bool
    {
        return $this->deliveryFactory->isMethodSupported($channel);
    }

    /**
     * Register a custom notification strategy
     *
     * @param string $method The notification method identifier
     * @param class-string $strategyClass The strategy class
     * @return self For method chaining
     */
    public function registerStrategy(string $method, string $strategyClass): self
    {
        $this->deliveryFactory->registerStrategy($method, $strategyClass);

        $this->logger->info('Custom notification strategy registered', [
            'method' => $method,
            'strategy_class' => $strategyClass,
        ]);

        return $this;
    }

    /**
     * Create a notification request DTO from array data
     *
     * This is a convenience method for creating request DTOs from array data,
     * useful for API endpoints or configuration-driven notifications.
     *
     * @param array<string, mixed> $data The request data
     * @return NotificationRequestDTO The created request DTO
     */
    public function createRequestFromArray(array $data): NotificationRequestDTO
    {
        return NotificationRequestDTO::fromArray($data);
    }

    /**
     * Create a notification request DTO with metadata
     *
     * @param string $recipient Recipient identifier (email/phone)
     * @param string|null $accountUuid Account UUID (if null, uses config default)
     * @param string $content Content for the notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param NotificationMetadataDTO|null $metadata Additional metadata
     * @return NotificationRequestDTO The created request DTO
     */
    public function createRequest(
        string $recipient,
        ?string $accountUuid = null,
        string $content = '',
        ?string $templateSlug = null,
        array $placeholders = [],
        ?NotificationMetadataDTO $metadata = null
    ): NotificationRequestDTO {
        return new NotificationRequestDTO(
            recipient: $recipient,
            accountUuid: $accountUuid,
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: $metadata ?? new NotificationMetadataDTO(),
        );
    }

    /**
     * Validate a legacy identifier format without sending a notification
     *
     * @param string $identifier The identifier to validate (recipient_accountUuid)
     * @return bool True if the identifier is valid
     */
    public function validateLegacyIdentifier(string $identifier): bool
    {
        try {
            NotificationPayloadGenerator::parseIdentifier($identifier);
            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Parse a legacy identifier and return its components
     *
     * @param string $identifier The identifier to parse (recipient_accountUuid)
     * @return array{recipient: string, accountUuid: string} The parsed components
     */
    public function parseLegacyIdentifier(string $identifier): array
    {
        return NotificationPayloadGenerator::parseIdentifier($identifier);
    }
}