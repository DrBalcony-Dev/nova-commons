<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Services\Notification\Generators;

use DrBalcony\NovaCommon\DTO\NotificationMetadataDTO;
use DrBalcony\NovaCommon\DTO\NotificationPayloadDTO;
use DrBalcony\NovaCommon\DTO\NotificationRequestDTO;

/**
 * Generator for notification payloads
 *
 * This class handles the creation of notification payloads from request DTOs
 * with clean separation of recipient and account UUID parameters.
 */
final class NotificationPayloadGenerator
{
    private const string DEFAULT_CATEGORY = 'system-alert';

    /**
     * Generate notification payload from request DTO
     *
     * @param NotificationRequestDTO $requestDTO The notification request
     * @param NotificationMetadataDTO|null $defaultMetadata Default metadata to merge
     * @return NotificationPayloadDTO The generated payload
     */
    public static function generate(
        NotificationRequestDTO $requestDTO,
        ?NotificationMetadataDTO $defaultMetadata = null
    ): NotificationPayloadDTO {
        // Merge metadata if default is provided
        $metadata = $defaultMetadata !== null
            ? $defaultMetadata->merge($requestDTO->metadata)
            : $requestDTO->metadata;

        // Ensure category is set for content-based notifications
        if (!$requestDTO->isTemplateBased() && $metadata->category === null) {
            $metadata = new NotificationMetadataDTO(
                data: $metadata->data,
                category: self::DEFAULT_CATEGORY,
                senderName: $metadata->senderName,
                subject: $metadata->subject,
            );
        }

        return new NotificationPayloadDTO(
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
     * @param string $content Content for notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return NotificationRequestDTO
     */
    public static function createRequest(
        string $recipient,
        ?string $accountUuid = null,
        string $content = '',
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): NotificationRequestDTO {
        return new NotificationRequestDTO(
            recipient: $recipient,
            accountUuid: $accountUuid,
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: NotificationMetadataDTO::fromArray($metadata),
        );
    }

    /**
     * Create request DTO from legacy identifier format for backward compatibility
     *
     * @param string $identifier Recipient and account UUID separated by underscore
     * @param string $content Content for notification
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string, mixed> $metadata Additional metadata
     * @return NotificationRequestDTO
     *
     * @throws InvalidNotificationIdentifierException If identifier format is invalid
     */
    public static function createFromLegacyIdentifier(
        string $identifier,
        string $content,
        ?string $templateSlug = null,
        array $placeholders = [],
        array $metadata = []
    ): NotificationRequestDTO {
        $parsed = self::parseIdentifier($identifier);

        return new NotificationRequestDTO(
            recipient: $parsed['recipient'],
            accountUuid: $parsed['accountUuid'],
            content: $content,
            templateSlug: $templateSlug,
            placeholders: $placeholders,
            metadata: NotificationMetadataDTO::fromArray($metadata),
        );
    }

    /**
     * Parse legacy identifier string (recipient_accountUuid)
     *
     * @param string $identifier Recipient and account UUID separated by underscore
     * @return array{recipient: string, accountUuid: string} Parsed components
     *
     * @throws InvalidNotificationIdentifierException If identifier format is invalid
     */
    public static function parseIdentifier(string $identifier): array
    {
        // Find the position of the last underscore
        $lastUnderscorePos = strrpos($identifier, '_');

        if ($lastUnderscorePos === false) {
            throw new InvalidNotificationIdentifierException($identifier);
        }

        // Extract recipient (everything before the last underscore)
        $recipient = substr($identifier, 0, $lastUnderscorePos);

        // Extract accountUuid (everything after the last underscore)
        $accountUuid = substr($identifier, $lastUnderscorePos + 1);

        if (empty($recipient) || empty($accountUuid)) {
            throw new InvalidNotificationIdentifierException(
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