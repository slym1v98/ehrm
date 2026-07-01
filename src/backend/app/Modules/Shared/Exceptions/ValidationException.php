<?php

namespace App\Modules\Shared\Exceptions;

class ValidationException extends AppException
{
    public function __construct(
        array $details = [],
        string $message = 'Validation failed',
        string $errorCode = 'VALIDATION_ERROR',
    ) {
        parent::__construct($errorCode, $message, $details);
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
