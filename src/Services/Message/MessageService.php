<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message;

use DrBalcony\NovaCommon\DTO\MessageMetadataDTO;
use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use DrBalcony\NovaCommon\Enums\MessageChannelEnum;
use DrBalcony\NovaCommon\Exceptions\InvalidMessageIdentifierException;
use DrBalcony\NovaCommon\Exceptions\UnsupportedMessageMethodException;
use DrBalcony\NovaCommon\Services\Message\Factories\MessageDeliveryFactory;
use DrBalcony\NovaCommon\Services\Message\Generators\MessagePayloadGenerator;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Main message service orchestrator
 *
 * This service provides a high-level interface for sending messages
 * across different channels (email, SMS, call) using the strategy pattern.
 */
final class MessageService
{
    private LoggerInterface $logger;
    private MessageDeliveryFactory $deliveryFactory;

    public function __construct(
        ?MessageDeliveryFactory $deliveryFactory = null,
        ?LoggerInterface        $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->deliveryFactory = $deliveryFactory ?? new MessageDeliveryFactory($this->logger);
    }

    /**
     * Send a message via the specified channel
     *
     * @param MessageChannelEnum $channel The message channel
     * @param MessageRequestDTO $requestDTO The message request
     * @return bool Success status of the delivery attempt
     *
     * @throws UnsupportedMessageMethodException If the channel is not supported
     * @throws Exception If the delivery fails
     */
    public function send(MessageChannelEnum $channel, MessageRequestDTO $requestDTO): bool
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
     * @param MessageRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendEmail(MessageRequestDTO $requestDTO): bool
    {
        return $this->send(MessageChannelEnum::EMAIL, $requestDTO);
    }

    /**
     * Send an SMS notification
     *
     * @param MessageRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendSms(MessageRequestDTO $requestDTO): bool
    {
        return $this->send(MessageChannelEnum::SMS, $requestDTO);
    }

    /**
     * Send a call notification
     *
     * @param MessageRequestDTO $requestDTO The notification request
     * @return bool Success status of the delivery attempt
     * @throws Exception
     */
    public function sendCall(MessageRequestDTO $requestDTO): bool
    {
        return $this->send(MessageChannelEnum::CALL, $requestDTO);
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
     * @throws UnsupportedMessageMethodException If the channel is not supported
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
        if (!MessageChannelEnum::isSupported($channel)) {
            throw new UnsupportedMessageMethodException(
                $channel,
                MessageChannelEnum::getAvailableChannels()
            );
        }

        // Create request DTO from legacy parameters
        $requestDTO = MessagePayloadGenerator::createRequest(
            $recipient,
            $accountUuid,
            $content,
            $templateSlug,
            $placeholders,
            $metadata
        );

        // Send using the modern method
        return $this->send(MessageChannelEnum::from($channel), $requestDTO);
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
     * @throws UnsupportedMessageMethodException If the channel is not supported
     * @throws InvalidMessageIdentifierException If identifier format is invalid
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
        if (!MessageChannelEnum::isSupported($channel)) {
            throw new UnsupportedMessageMethodException(
                $channel,
                MessageChannelEnum::getAvailableChannels()
            );
        }

        // Create request DTO from legacy identifier
        $requestDTO = MessagePayloadGenerator::createFromLegacyIdentifier(
            $identifier,
            $content,
            $templateSlug,
            $placeholders,
            $metadata
        );

        // Send using the modern method
        return $this->send(MessageChannelEnum::from($channel), $requestDTO);
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
     * @return MessageRequestDTO The created request DTO
     */
    public function createRequestFromArray(array $data): MessageRequestDTO
    {
        return MessageRequestDTO::fromArray($data);
    }

    /**
     * Create a notification request DTO with metadata
     *
     * @param string $recipient Recipient identifier (email/phone)
     * @param string|null $accountUuid Account UUID (if null, uses config default)
     * @param string $content Content for the notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param MessageMetadataDTO|null $metadata Additional metadata
     * @param array<string> $attachments Array of attachment URL strings (e.g. for flyer/contract PDF)
     * @return MessageRequestDTO The created request DTO
     */
    public function createRequest(
        string $recipient,
        ?string $accountUuid = null,
        string $content = '',
        ?string $templateSlug = null,
        array $placeholders = [],
        ?MessageMetadataDTO $metadata = null,
        array $attachments = []
    ): MessageRequestDTO {
        $attachments = array_values(array_filter(array_map('strval', $attachments)));

        return new MessageRequestDTO(
            recipient: $recipient,
            accountUuid: $accountUuid,
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: $metadata ?? new MessageMetadataDTO(),
            attachments: $attachments,
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
            MessagePayloadGenerator::parseIdentifier($identifier);
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
        return MessagePayloadGenerator::parseIdentifier($identifier);
    }
}