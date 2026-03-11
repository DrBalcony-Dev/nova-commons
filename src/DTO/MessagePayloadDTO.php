<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\DTO;

/**
 * Data Transfer Object for message payload
 */
final readonly class MessagePayloadDTO
{
    /**
     * @param string $accountId Account identifier (UUID)
     * @param string $recipient Recipient identifier (email/phone)
     * @param MessageMetadataDTO $metadata message metadata
     * @param string|null $content Direct content (used when template is null)
     * @param string|null $template Template slug
     * @param array<string, mixed> $placeholders Template placeholders
     * @param array<string> $attachments Array of attachment URL strings
     */
    public function __construct(
        public string             $accountId,
        public string             $recipient,
        public MessageMetadataDTO $metadata,
        public ?string            $content = null,
        public ?string            $template = null,
        public array              $placeholders = [],
        public array              $attachments = [],
    ) {}

    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $attachments = $data['attachments'] ?? [];
        if (! is_array($attachments)) {
            $attachments = [];
        }
        $attachments = array_values(array_filter(array_map('strval', $attachments)));

        return new self(
            accountId: (string) $data['account_id'],
            recipient: (string) $data['to'],
            metadata: isset($data['metadata'])
                ? MessageMetadataDTO::fromArray($data['metadata'])
                : new MessageMetadataDTO(),
            content: $data['content'] ?? null,
            template: $data['template'] ?? null,
            placeholders: $data['placeholders'] ?? [],
            attachments: $attachments,
        );
    }

    /**
     * Convert to array for RabbitMQ payload
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'account_id' => $this->accountId,
            'to' => $this->recipient,
            'metadata' => $this->metadata->toArray(),
        ];

        if (! empty($this->attachments)) {
            $payload['attachments'] = $this->attachments;
        }

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
     * Check if this is a template-based message
     *
     * @return bool
     */
    public function isTemplateBased(): bool
    {
        return $this->template !== null;
    }
}
