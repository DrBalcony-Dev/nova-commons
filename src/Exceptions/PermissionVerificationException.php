<?php

namespace DrBalcony\NovaCommon\Exceptions;

use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when permission verification fails
 */
class PermissionVerificationException extends NovaException
{
    public function __construct(
        string      $message = 'Permission verification failed',
        int         $code = Response::HTTP_FORBIDDEN,
        ?\Exception $previous = null,
        string      $level = LogLevel::WARNING,
        bool        $shouldReport = true
    )
    {
        parent::__construct($message, $code, $previous, $level, $shouldReport);
    }
}