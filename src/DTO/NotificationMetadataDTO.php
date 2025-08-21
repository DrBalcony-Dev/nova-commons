<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\DTO;

/**
 * Data Transfer Object for notification metadata
 */
final readonly class NotificationMetadataDTO
{
    /**
     * @param array<string, mixed> $data Additional metadata
     * @param string|null $category Notification category
     * @param string|null $senderName Sender name override
     * @param string|null $subject Subject for email notifications
     */
    public function __construct(
        public array $data = [],
        public ?string $category = null,
        public ?string $senderName = null,
        public ?string $subject = null,
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

        return $result;
    }

    /**
     * Merge with another metadata DTO
     *
     * @param NotificationMetadataDTO $other
     * @return self
     */
    public function merge(NotificationMetadataDTO $other): self
    {
        return new self(
            data: array_merge($this->data, $other->data),
            category: $other->category ?? $this->category,
            senderName: $other->senderName ?? $this->senderName,
            subject: $other->subject ?? $this->subject,
        );
    }
}