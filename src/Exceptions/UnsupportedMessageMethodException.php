<?php

namespace DrBalcony\NovaCommon\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when an unsupported message method is requested
 */
final class UnsupportedMessageMethodException extends InvalidArgumentException
{
    /**
     * @param string $method The unsupported method
     * @param array<string> $supportedMethods List of supported methods
     */
    public function __construct(string $method, array $supportedMethods = [])
    {
        $supportedMethodsList = empty($supportedMethods)
            ? ''
            : ' Supported methods: ' . implode(', ', $supportedMethods);

        parent::__construct(
            "Unsupported message method: '{$method}'.{$supportedMethodsList}"
        );
    }
}
