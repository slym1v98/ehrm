<?php

namespace App\Modules\Employee\Domain\Exceptions;

use App\Modules\Shared\Exceptions\AppException;

class EmployeeDocumentExpiredException extends AppException
{
    public function __construct(string $id = '')
    {
        parent::__construct('EMPLOYEE_DOCUMENT_EXPIRED', "Employee document expired: {$id}");
    }

    public function getHttpStatus(): int
    {
        return 422;
    }
}
