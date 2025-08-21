<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Enums;

/**
 * Enumeration of supported notification delivery channels
 */
enum NotificationChannelEnum: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case CALL = 'call';

    /**
     * Get all available notification channels
     *
     * @return array<string>
     */
    public static function getAvailableChannels(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a channel is supported
     *
     * @param string $channel The channel to check
     * @return bool True if the channel is supported
     */
    public static function isSupported(string $channel): bool
    {
        return in_array($channel, self::getAvailableChannels(), true);
    }
}