<?php

namespace DrBalcony\NovaCommon\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when message validation fails
 */
final class MessageValidationException extends InvalidArgumentException
{
    public function __construct(string $field, string $value, string $reason = '')
    {
        $message = "Invalid {$field}: '{$value}'";
        if (!empty($reason)) {
            $message .= ". {$reason}";
        }

        parent::__construct($message);
    }
}
