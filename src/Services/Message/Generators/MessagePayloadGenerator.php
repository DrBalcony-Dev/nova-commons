<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Message\Generators;

use DrBalcony\NovaCommon\DTO\MessageMetadataDTO;
use DrBalcony\NovaCommon\DTO\MessagePayloadDTO;
use DrBalcony\NovaCommon\DTO\MessageRequestDTO;
use DrBalcony\NovaCommon\Exceptions\InvalidMessageIdentifierException;

/**
 * Generator for message payloads
 *
 * This class handles the creation of message payloads from request DTOs
 * with clean separation of recipient and account UUID parameters.
 */
final class MessagePayloadGenerator
{
    private const string DEFAULT_CATEGORY = 'system-alert';

    /**
     * Generate message payload from request DTO
     *
     * @param MessageRequestDTO $requestDTO The message request
     * @param MessageMetadataDTO|null $defaultMetadata Default metadata to merge
     * @return MessagePayloadDTO The generated payload
     */
    public static function generate(
        MessageRequestDTO   $requestDTO,
        ?MessageMetadataDTO $defaultMetadata = null
    ): MessagePayloadDTO {
        // Merge metadata if default is provided
        $metadata = $defaultMetadata !== null
            ? $defaultMetadata->merge($requestDTO->metadata)
            : $requestDTO->metadata;

        // Ensure category is set for content-based messages
        if (!$requestDTO->isTemplateBased() && $metadata->category === null) {
            $metadata = new MessageMetadataDTO(
                data: $metadata->data,
                category: self::DEFAULT_CATEGORY,
                senderName: $metadata->senderName,
                subject: $metadata->subject,
            );
        }

        return new MessagePayloadDTO(
            accountId: $requestDTO->getAccountUuid(),
            recipient: $requestDTO->recipient,
            metadata: $metadata,
            content: $requestDTO->content,
            template: $requestDTO->templateSlug,
            placeholders: $requestDTO->placeholders,
        );
    }

    /**
     * Create request DTO from separate parameters
     *
     * @param string $recipient Recipient identifier (email/phone)
     * @param string|null $accountUuid Account UUID (if null, will use config default)
     * @param string $content Content for message
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return MessageRequestDTO
     */
    public static function createRequest(
        string $recipient,
        ?string $accountUuid = null,
        string $content = '',
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): MessageRequestDTO {
        return new MessageRequestDTO(
            recipient: $recipient,
            accountUuid: $accountUuid,
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: MessageMetadataDTO::fromArray($metadata),
        );
    }

    /**
     * Create request DTO from legacy identifier format for backward compatibility
     *
     * @param string $identifier Recipient and account UUID separated by underscore
     * @param string $content Content for message
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return MessageRequestDTO
     *
     * @throws InvalidMessageIdentifierException If identifier format is invalid
     */
    public static function createFromLegacyIdentifier(
        string $identifier,
        string $content,
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): MessageRequestDTO {
        $parsed = self::parseIdentifier($identifier);

        return new MessageRequestDTO(
            recipient: $parsed['recipient'],
            accountUuid: $parsed['accountUuid'],
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: MessageMetadataDTO::fromArray($metadata),
        );
    }

    /**
     * Parse legacy identifier string (recipient_accountUuid)
     *
     * @param string $identifier Recipient and account UUID separated by underscore
     * @return array{recipient: string, accountUuid: string} Parsed components
     *
     * @throws InvalidMessageIdentifierException If identifier format is invalid
     */
    public static function parseIdentifier(string $identifier): array
    {
        // Find the position of the last underscore
        $lastUnderscorePos = strrpos($identifier, '_');

        if ($lastUnderscorePos === false) {
            throw new InvalidMessageIdentifierException($identifier);
        }

        // Extract recipient (everything before the last underscore)
        $recipient = substr($identifier, 0, $lastUnderscorePos);

        // Extract accountUuid (everything after the last underscore)
        $accountUuid = substr($identifier, $lastUnderscorePos + 1);

        if (empty($recipient) || empty($accountUuid)) {
            throw new InvalidMessageIdentifierException(
                $identifier,
                'recipient_accountUuid (both parts must be non-empty)'
            );
        }

        return [
            'recipient' => $recipient,
            'accountUuid' => $accountUuid,
        ];
    }
}