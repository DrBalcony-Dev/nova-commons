<?php

namespace DrBalcony\NovaCommon\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when message identifier format is invalid
 */
final class InvalidMessageIdentifierException extends InvalidArgumentException
{
    public function __construct(string $identifier, string $expectedFormat = 'recipient_accountId')
    {
        parent::__construct(
            "Invalid message identifier format: '{$identifier}'. Expected format: '{$expectedFormat}'"
        );
    }
}