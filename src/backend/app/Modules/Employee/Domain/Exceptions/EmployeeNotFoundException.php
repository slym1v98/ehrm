<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class EmployeeNotFoundException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('EMPLOYEE_NOT_FOUND', "Employee not found: {$id}");
    }

    public function getHttpStatus(): int
    {
        return 404;
    }
}
