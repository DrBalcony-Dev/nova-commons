<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\DTO;

use DrBalcony\NovaCommon\Utils\MessageAttachmentSanitizer;


/**
 * Data Transfer Object for message request
 */
final readonly class MessageRequestDTO
{
    public array $attachments;

    /**
     * @param string $recipient Recipient identifier (email/phone)
     * @param string|null $accountUuid Account UUID (if null, will be retrieved from config)
     * @param string $content Content for the message (used when templateSlug is null)
     * @param string|null $templateSlug Template identifier
     * @param array<string, mixed> $placeholders Template placeholders
     * @param MessageMetadataDTO $metadata Additional metadata
     * @param mixed $attachments Raw attachments (array, null, or other); will be sanitized
     */
    public function __construct(
        public string             $recipient,
        public ?string            $accountUuid = null,
        public string             $content = '',
        public ?string            $templateSlug = null,
        public array              $placeholders = [],
        public MessageMetadataDTO $metadata = new MessageMetadataDTO(),
        mixed                     $attachments = [],
    ) {
        $this->attachments = MessageAttachmentSanitizer::sanitize($attachments);
    }

    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            recipient: (string) $data['recipient'],
            accountUuid: $data['account_uuid'] ?? null,
            content: (string) ($data['content'] ?? ''),
            templateSlug: $data['template_slug'] ?? null,
            placeholders: $data['placeholders'] ?? [],
            metadata: isset($data['metadata'])
                ? MessageMetadataDTO::fromArray($data['metadata'])
                : new MessageMetadataDTO(),
            attachments: $data['attachments'] ?? [],
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'recipient' => $this->recipient,
            'account_uuid' => $this->accountUuid,
            'content' => $this->content,
            'template_slug' => $this->templateSlug,
            'placeholders' => $this->placeholders,
            'metadata' => $this->metadata->toArray(),
            'attachments' => $this->attachments,
        ];
    }

    /**
     * Check if this request has any attachments
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Check if this is a template-based request
     *
     * @return bool
     */
    public function isTemplateBased(): bool
    {
        return $this->templateSlug !== null;
    }

    /**
     * Get the account UUID, using config default if not set
     *
     * @return string The account UUID
     */
    public function getAccountUuid(): string
    {
        return $this->accountUuid ?? config('nova-common.default_account_uuid', '');
    }

    /**
     * Get the legacy identifier format for backward compatibility
     *
     * @return string The identifier in format: recipient_accountUuid
     */
    public function getLegacyIdentifier(): string
    {
        return $this->recipient . '_' . $this->getAccountUuid();
    }
}