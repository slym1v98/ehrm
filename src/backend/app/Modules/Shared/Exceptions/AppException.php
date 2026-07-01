<?php

namespace App\Modules\Shared\Exceptions;

use Exception;

abstract class AppException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message = '',
        public readonly array $details = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    abstract public function getHttpStatus(): int;
}
