<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\DTO;

/**
 * Data Transfer Object for message metadata
 */
final readonly class MessageMetadataDTO
{
    /**
     * @param array<string, mixed> $data Additional metadata
     * @param string|null $category message category
     * @param string|null $senderName Sender name override
     * @param string|null $subject Subject for email messages
     * @param string|null $sendAt ISO 8601 datetime (Y-m-d\TH:i:sP) when the message should be sent (e.g. 2025-03-19T10:00:00-07:00)
     */
    public function __construct(
        public array $data = [],
        public ?string $category = null,
        public ?string $senderName = null,
        public ?string $subject = null,
        public ?string $sendAt = null,
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
            data: $data['data'] ?? [],
            category: $data['category'] ?? null,
            senderName: $data['sender_name'] ?? null,
            subject: $data['subject'] ?? null,
            sendAt: $data['send_at'] ?? null,
        );
    }

    /**
     * Convert to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = $this->data;

        if ($this->category !== null) {
            $result['category'] = $this->category;
        }

        if ($this->senderName !== null) {
            $result['sender_name'] = $this->senderName;
        }

        if ($this->subject !== null) {
            $result['subject'] = $this->subject;
        }

        if ($this->sendAt !== null) {
            $result['send_at'] = $this->sendAt;
        }

        return $result;
    }

    /**
     * Merge with another metadata DTO
     *
     * @param MessageMetadataDTO $other
     * @return self
     */
    public function merge(MessageMetadataDTO $other): self
    {
        return new self(
            data: array_merge($this->data, $other->data),
            category: $other->category ?? $this->category,
            senderName: $other->senderName ?? $this->senderName,
            subject: $other->subject ?? $this->subject,
            sendAt: $other->sendAt ?? $this->sendAt,
        );
    }
}