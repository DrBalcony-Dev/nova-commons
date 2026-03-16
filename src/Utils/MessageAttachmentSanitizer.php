<?php

declare(strict_types=1);

namespace DrBalcony\NovaCommon\Utils;

/**
 * Sanitizes attachment arrays for message payloads.
 *
 * Ensures attachments are a sequential array of non-empty string URLs.
 */
final class MessageAttachmentSanitizer
{
    /**
     * Sanitize attachments to a normalized array of string URLs.
     *
     * @param mixed $attachments Raw attachments (array, null, or other)
     * @return array<string> Sequential array of non-empty string URLs
     */
    public static function sanitize(mixed $attachments): array
    {
        if (! is_array($attachments)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $attachments)));
    }
}
