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
     */
    public function __construct(
        public string             $accountId,
        public string             $recipient,
        public MessageMetadataDTO $metadata,
        public ?string            $content = null,
        public ?string            $template = null,
        public array              $placeholders = [],
    ) {}

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
