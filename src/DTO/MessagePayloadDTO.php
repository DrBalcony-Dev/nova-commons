<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\DTO;

use DrBalcony\NovaCommon\Utils\MessageAttachmentSanitizer;

/**
 * Data Transfer Object for message payload
 */
final readonly class MessagePayloadDTO
{
    public array $attachments;

    /**
     * @param string $accountId Account identifier (UUID)
     * @param string $recipient Recipient identifier (email/phone)
     * @param MessageMetadataDTO $metadata message metadata
     * @param string|null $content Direct content. Used when template is null (content-based).
     *                             When template is set, content is ignored.
     * @param string|null $template Template slug. Null = use content instead of template.
     * @param array<string, mixed> $placeholders Template placeholders (only when template is set)
     * @param mixed $attachments Raw attachments (array, null, or other); will be sanitized
     */
    public function __construct(
        public string             $accountId,
        public string             $recipient,
        public MessageMetadataDTO $metadata,
        public ?string            $content = null,
        public ?string            $template = null,
        public array              $placeholders = [],
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
            accountId: (string) $data['account_id'],
            recipient: (string) $data['to'],
            metadata: isset($data['metadata'])
                ? MessageMetadataDTO::fromArray($data['metadata'])
                : new MessageMetadataDTO(),
            content: $data['content'] ?? null,
            template: $data['template'] ?? null,
            placeholders: $data['placeholders'] ?? [],
            attachments: $data['attachments'] ?? [],
        );
    }

    /**
     * Convert to array for RabbitMQ payload.
     * When template is null, outputs content and category (content-based).
     * When template is set, outputs template and placeholders (template-based).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'account_id' => $this->accountId,
            'to' => $this->recipient,
            'metadata' => $this->metadata->toArray(),
            'attachments' => $this->attachments,
        ];

        if ($this->template !== null) {
            $payload['template'] = $this->template;
            if (!empty($this->placeholders)) {
                $payload['placeholders'] = $this->placeholders;
            }
        } else {
            $payload['content'] = $this->content ?? '';
            $payload['category'] = $this->metadata->category ?? 'system-alert';
        }

        return $payload;
    }

    /**
     * Check if this payload has any attachments
     *
     * @return bool
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Check if this is a template-based message
     *
     * @return bool
     */
    public function isTemplateBased(): bool
    {
        return $this->template !== null;
    }
}
