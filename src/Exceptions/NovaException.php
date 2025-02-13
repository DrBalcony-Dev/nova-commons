<?php

namespace DrBalcony\NovaCommon\Exceptions;

use Exception;
use Psr\Log\LogLevel;

class NovaException extends Exception
{
    protected string $level = LogLevel::ERROR;
    protected bool $shouldReport = true;

    public function __construct(
        string     $message = "",
        int        $code = 0,
        ?Exception $previous = null,
        string     $level = LogLevel::ERROR,
        bool       $shouldReport = true
    )
    {
        parent::__construct($message, $code, $previous);
        $this->level = $level;
        $this->shouldReport = $shouldReport;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function shouldReport(): bool
    {
        return $this->shouldReport;
    }
}
