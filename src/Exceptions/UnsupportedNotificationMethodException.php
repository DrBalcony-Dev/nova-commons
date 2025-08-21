<?php

namespace DrBalcony\NovaCommon\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when an unsupported notification method is requested
 */
final class UnsupportedNotificationMethodException extends InvalidArgumentException
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
            "Unsupported notification method: '{$method}'.{$supportedMethodsList}"
        );
    }
}
