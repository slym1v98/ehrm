<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class InvalidEmployeeStatusTransitionException extends AppException
{
    public function __construct(string $from, string $to)
    {
        parent::__construct('INVALID_EMPLOYEE_STATUS_TRANSITION', "Invalid employee status transition: {$from} -> {$to}");
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
