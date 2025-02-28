<?php

namespace DrBalcony\NovaCommon\Exceptions;

use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when authentication fails
 */
class AuthenticationException extends NovaException
{
    public function __construct(
        string      $message = 'Token is invalid',
        int         $code = Response::HTTP_FORBIDDEN,
        ?\Exception $previous = null,
        string      $level = LogLevel::WARNING,
        bool        $shouldReport = true
    )
    {
        parent::__construct($message, $code, $previous, $level, $shouldReport);
    }
}