<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class EmployeeCodeAlreadyExistsException extends AppException
{
    public function __construct(string $code)
    {
        parent::__construct('EMPLOYEE_CODE_ALREADY_EXISTS', "Employee code already exists: {$code}");
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}
