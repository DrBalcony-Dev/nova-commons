<?php

namespace DrBalcony\NovaCommon\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when notification identifier format is invalid
 */
final class InvalidNotificationIdentifierException extends InvalidArgumentException
{
    public function __construct(string $identifier, string $expectedFormat = 'recipient_accountId')
    {
        parent::__construct(
            "Invalid notification identifier format: '{$identifier}'. Expected format: '{$expectedFormat}'"
        );
    }
}