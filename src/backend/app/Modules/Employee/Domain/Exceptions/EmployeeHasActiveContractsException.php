<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class EmployeeHasActiveContractsException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('EMPLOYEE_HAS_ACTIVE_CONTRACTS', "Employee has active contracts: {$id}");
    }

    public function getHttpStatus(): int
    {
        return 409;
    }
}
