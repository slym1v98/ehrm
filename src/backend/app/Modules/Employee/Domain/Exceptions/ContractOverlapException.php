<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class ContractOverlapException extends AppException
{
    public function __construct(string $employeeId = '')
    {
        parent::__construct('CONTRACT_OVERLAP', "Employee has overlapping active contract: {$employeeId}");
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
